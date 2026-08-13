<?php

namespace App\Actions\Pds;

use App\Helpers\Dialer;
use App\Jobs\ReleasePdsCustomersJob;
use App\Jobs\StartPdsJob;
use App\Jobs\StopPdsJob;
use App\Jobs\UploadPdsCustomersJob;
use App\Models\Data\ProductSubject;
use App\Models\Data\Ticket;
use App\Models\Data\TicketForm;
use App\Models\Data\TicketHistory;
use App\Models\Pds\Pds;
use App\Models\Pds\PdsAgent;
use App\Models\Pds\PdsCustomer;
use App\Services\Data\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SetupPdsAction
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    public function execute(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:pds,pds_name',
            // 'trunk' => 'required|unique:pds,route',
            'trunk' => 'required',
            'ivr' => 'required|unique:pds,ivr',
        ], [
            'ivr.required' => 'VDN is required',
            'ivr.unique' => 'VDN has been used',
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $user) {
            $pds = Pds::create([
                'company_id' => $user->company_id,
                'tenant_id' => $user->tenant_id,
                'pds_name' => $request->name,
                'route' => $request->trunk,
                'ivr' => $request->ivr,
                'marketing_campaign_id' => $request->marketing_campaign,
                'spv_id' => $request->spv,
                'is_running' => 0,
            ]);

            $dialer = Dialer::post('/campaign-dialer/setup-pds', [
                'tenant_id' => $user->tenant_id,
                'campaign_id' => $request->name,
                'route_id' => $request->trunk,
                'vdn' => $request->ivr,
            ]);

            Log::info('PAYLOAD SETUP PDS', [
                'tenant_id' => $user->tenant_id,
                'campaign_id' => $request->name,
                'route_id' => $request->trunk,
                'vdn' => $request->ivr,
            ]);
            logger(json_encode($dialer));

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                $messages = [];

                if (Str::contains(strtolower($errorMessage), 'vdn')) {
                    $messages['ivr'] = $errorMessage;
                }
                if (Str::contains(strtolower($errorMessage), 'route')) {
                    $messages['trunk'] = $errorMessage;
                }
                if (Str::contains(strtolower($errorMessage), 'campaign')) {
                    $messages['name'] = $errorMessage;
                }

                if (empty($messages)) {
                    $messages['ivr'] = $errorMessage;
                }

                throw ValidationException::withMessages($messages);
            }

            return [
                'pds' => $pds,
                'dialer_response' => $dialer,
            ];
        });
    }

    public function start(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:pds,id',
            'ids' => 'nullable|array',
            'ids.*' => 'exists:pds,id',
            'call_factor' => 'required',
            'call_wait' => 'required',
            'call_abandon_rate' => 'required',
            'call_limit' => 'required',
            'call_retry_after' => 'required',
            'call_retry_max' => 'required',
        ]);

        $user = user();
        $ids = collect($request->input('ids', [$request->input('id')]))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw new BadRequestException('PDS is required');
        }

        $pdsList = Pds::whereIn('id', $ids)
            ->where('company_id', $user->company_id)
            ->where('is_running', 0)
            ->withCount(['customers', 'agents'])
            ->get();

        if ($pdsList->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'ids' => 'One or more selected PDS are no longer available to start',
            ]);
        }

        foreach ($pdsList as $pds) {
            if ($pds->customers_count == 0) {
                throw ValidationException::withMessages([
                    'call_factor' => 'Please upload customer data for '.$pds->pds_name.' before starting',
                ]);
            }

            if ($pds->agents_count == 0) {
                throw ValidationException::withMessages([
                    'call_factor' => 'Please assign an active agent to '.$pds->pds_name.' before starting',
                ]);
            }
        }

        foreach ($pdsList as $pds) {
            StartPdsJob::dispatch(
                $pds->id,
                $user->company_id,
                $request->only([
                    'call_factor',
                    'call_wait',
                    'call_abandon_rate',
                    'call_limit',
                    'call_retry_after',
                    'call_retry_max',
                ]),
            )
                ->onQueue('dialer');
        }

        return $pdsList->count();
    }

    public function preparePdsStart(string $pdsId, string $companyId, array $settings): ?array
    {
        $pds = Pds::where('id', $pdsId)
            ->where('company_id', $companyId)
            ->where('is_running', 0)
            ->first();

        if (!$pds) {
            return null;
        }

        $payload = [
            'tenant_id' => $pds->tenant_id,
            'campaign_id' => $pds->pds_name,
            'CallFactor' => $settings['call_factor'],
            'CallWait' => $settings['call_wait'],
            'CallAbandonRate' => $settings['call_abandon_rate'],
            'CallLimit' => $settings['call_limit'],
            'CallRetryAfter' => $settings['call_retry_after'],
            'CallRetryMax' => $settings['call_retry_max'],
        ];

        Log::info('PAYLOAD START PDS', [
            ...$payload,
        ]);

        return $payload;
    }

    public function markPdsStarted(string $pdsId, string $companyId, array $settings): void
    {
        DB::transaction(function () use ($pdsId, $companyId, $settings) {
            Pds::where('id', $pdsId)
                ->where('company_id', $companyId)
                ->where('is_running', 0)
                ->update([
                    'call_factor' => $settings['call_factor'],
                    'call_wait' => $settings['call_wait'],
                    'call_abandon_rate' => $settings['call_abandon_rate'],
                    'call_limit' => $settings['call_limit'],
                    'call_retry_after' => $settings['call_retry_after'],
                    'call_retry_max' => $settings['call_retry_max'],
                    'is_running' => 1,
                ]);
        });
    }

    public function stop(Request $request)
    {
        $user = user();
        $ids = $request->input('ids', $request->input('id'));
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            throw new BadRequestException('PDS is required');
        }

        $isBulk = count($ids) > 1;
        $pdsQuery = Pds::whereIn('id', $ids)
            ->where('company_id', $user->company_id);

        if ($isBulk) {
            $pdsQuery->where('is_running', 1);
        }

        $pdsIds = $pdsQuery->pluck('id');

        if ($pdsIds->isEmpty()) {
            throw new BadRequestException($isBulk ? 'No running PDS selected' : 'PDS not found');
        }

        if (!$isBulk) {
            $dialerPayload = $this->stopPds($pdsIds->first(), $user->company_id);

            if ($dialerPayload) {
                $dialer = Dialer::post('/pds-stop', $dialerPayload, true);

                if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                    throw new BadRequestException($dialer['errors']);
                }
            }

            return 1;
        }

        foreach ($pdsIds as $pdsId) {
            StopPdsJob::dispatch($pdsId, $user->company_id)
                ->onQueue('dialer');
        }

        return $pdsIds->count();
    }

    public function stopPds(string $pdsId, string $companyId, bool $ignoreAlreadyStopped = false): ?array
    {
        return DB::transaction(function () use ($pdsId, $companyId, $ignoreAlreadyStopped) {
            $pds = Pds::where('id', $pdsId)
                ->where('company_id', $companyId)
                ->first();

            if (!$pds) {
                return null;
            }

            if (!$pds->is_running) {
                if ($ignoreAlreadyStopped) {
                    return null;
                }

                throw new BadRequestException('PDS already stop');
            }

            $pds->update([
                'is_running' => 0,
            ]);

            return [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
            ];
        });
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pds,id',
        ]);

        $user = user();

        return DB::transaction(function () use ($request) {
            $pds = Pds::where('id', $request->id)->first();

            $dialer = Dialer::post('/campaign-dialer/releaseCustomerPDS', [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);

                return;
            }

            $dialer = Dialer::post('/campaign-dialer/release-agent', [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);

                return;
            }

            $dialer = Dialer::post('/campaign-dialer/unsetup-pds', [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);

                return;
            }

            $pds->delete();

            return [
                'pds' => $pds,
                'dialer_response' => $dialer,
            ];
        });
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'marketing_campaign' => 'required',
            'spv' => 'required',
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $id) {
            $pds = Pds::where('id', $id)->first();
            $pds->update([
                'marketing_campaign_id' => $request->marketing_campaign,
                'spv_id' => $request->spv,
            ]);
        });
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
        ]);

        $user = user();
        $pds = Pds::where('id', $id)
            ->where('company_id', $user->company_id)
            ->where('is_running', 0)
            ->whereDoesntHave('customers')
            ->first();

        if (!$pds) {
            throw new BadRequestException('PDS is not available for customer assignment');
        }

        $customers = (new TicketService())->getCustByTicket(
            $user->company_id,
            $pds->marketing_campaign_id,
            $pds->id,
            $request->status,
            $request->mobile ?? [],
            $request->additional ?? [],
            $request->risk_criteria ?? [],
            $request->type,
            $request->offices ?? [],
        );

        if (empty($customers)) {
            throw ValidationException::withMessages([
                'status' => 'No customers match the selected filters',
            ]);
        }

        DB::transaction(function () use ($customers, $user, $pds) {
            foreach ($customers as $key => $value) {
                PdsCustomer::create([
                    'company_id' => $user->company_id,
                    'pds_id' => $pds->id,
                    'ticket_id' => $value['customer_id'],
                    'phone' => $value['phone'],
                ]);
            }
        });

        $this->queueCustomerUpload($pds, $customers);
    }

    public function bulkAssign(Request $request): int
    {
        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.pds_id' => 'required|distinct|exists:pds,id',
            'assignments.*.type' => 'required|in:HO,Branch',
            'assignments.*.status' => 'required|array|min:1',
            'assignments.*.offices' => 'nullable|array',
            'assignments.*.mobile' => 'nullable|array',
            'assignments.*.additional' => 'nullable|array',
            'assignments.*.risk_criteria' => 'nullable|array',
        ]);

        $user = user();
        $assignments = collect($validated['assignments']);
        $pdsList = Pds::whereIn('id', $assignments->pluck('pds_id'))
            ->where('company_id', $user->company_id)
            ->where('is_running', 0)
            ->whereDoesntHave('customers')
            ->get()
            ->keyBy('id');
        $prepared = [];
        $reservedCustomers = collect();

        foreach ($assignments as $index => $assignment) {
            $pds = $pdsList->get($assignment['pds_id']);

            if (!$pds) {
                throw ValidationException::withMessages([
                    "assignments.$index.pds_id" => 'PDS is not available for customer assignment',
                ]);
            }

            if ($assignment['type'] === 'Branch' && empty($assignment['offices'])) {
                throw ValidationException::withMessages([
                    "assignments.$index.offices" => 'Office is required for Branch type',
                ]);
            }

            if (empty($assignment['mobile']) && empty($assignment['additional'])) {
                throw ValidationException::withMessages([
                    "assignments.$index.mobile" => 'Select at least one phone field',
                ]);
            }

            $customers = (new TicketService())->getCustByTicket(
                $user->company_id,
                $pds->marketing_campaign_id,
                $pds->id,
                $assignment['status'],
                $assignment['mobile'] ?? [],
                $assignment['additional'] ?? [],
                $assignment['risk_criteria'] ?? [],
                $assignment['type'],
                $assignment['offices'] ?? [],
            );

            if (empty($customers)) {
                throw ValidationException::withMessages([
                    "assignments.$index.status" => 'No customers match the selected filters for '.$pds->pds_name,
                ]);
            }

            $customerKeys = collect($customers)
                ->map(fn (array $customer) => $customer['customer_id'].'|'.$customer['phone']);

            if ($customerKeys->intersect($reservedCustomers)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "assignments.$index.status" => 'The filters for '.$pds->pds_name.' overlap customers selected for an earlier PDS. Adjust its status, office, or criteria.',
                ]);
            }

            $reservedCustomers = $reservedCustomers
                ->merge($customerKeys)
                ->unique();

            $prepared[] = compact('pds', 'customers');
        }

        DB::transaction(function () use ($prepared, $user) {
            foreach ($prepared as $item) {
                foreach ($item['customers'] as $customer) {
                    PdsCustomer::create([
                        'company_id' => $user->company_id,
                        'pds_id' => $item['pds']->id,
                        'ticket_id' => $customer['customer_id'],
                        'phone' => $customer['phone'],
                    ]);
                }
            }
        });

        foreach ($prepared as $item) {
            $this->queueCustomerUpload($item['pds'], $item['customers']);
        }

        return count($prepared);
    }

    private function queueCustomerUpload(Pds $pds, array $customers): void
    {
        Log::info('QUEUE PAYLOAD PDS', [
            'tenant_id' => $pds->tenant_id,
            'campaign_id' => $pds->pds_name,
            'total_data' => count($customers),
        ]);

        UploadPdsCustomersJob::dispatch(
            $pds->id,
            $pds->tenant_id,
            $pds->pds_name,
        )
            ->onQueue('dialer');
    }

    public function release(Request $request, $ids)
    {
        $user = user();
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            throw new BadRequestException('PDS is required');
        }

        $pdsIds = Pds::whereIn('id', $ids)
            ->where('company_id', $user->company_id)
            ->pluck('id');

        if ($pdsIds->isEmpty()) {
            throw new BadRequestException('No valid PDS selected');
        }

        if ($pdsIds->count() === 1) {
            $dialerPayload = $this->releasePdsCustomers(
                $pdsIds->first(),
                $user->company_id
            );

            if ($dialerPayload) {
                $dialer = Dialer::post(
                    '/campaign-dialer/releaseCustomerPDS',
                    $dialerPayload,
                    true
                );

                if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                    throw new BadRequestException($dialer['errors']);
                }
            }

            return 1;
        }

        foreach ($pdsIds as $pdsId) {
            ReleasePdsCustomersJob::dispatch($pdsId, $user->company_id)
                ->onQueue('dialer');
        }

        return $pdsIds->count();
    }

    public function releasePdsCustomers(string $pdsId, string $companyId): ?array
    {
        return DB::transaction(function () use ($pdsId, $companyId) {
            $pds = Pds::where('id', $pdsId)
                ->where('company_id', $companyId)
                ->first();

            if (!$pds) {
                return null;
            }

            $ticketIds = PdsCustomer::where('pds_id', $pds->id)
                ->where('company_id', $companyId)
                ->pluck('ticket_id')
                ->filter()
                ->unique()
                ->values();

            $tickets = Ticket::whereIn('id', $ticketIds)
                ->get()
                ->groupBy(fn (Ticket $ticket) => filled($ticket->customer_number)
                    ? (string) $ticket->customer_number
                    : 'ticket:' . $ticket->id
                );

            $agentIds = PdsAgent::where('pds_id', $pds->id)
                ->where('company_id', $companyId)
                ->pluck('user_id')
                ->toArray();

            $totalAgents = count($agentIds);
            $agentIndex = 0;

            foreach ($tickets as $ticketGroup) {
                $assignedAgentId = $ticketGroup
                    ->pluck('current_agent_id')
                    ->filter()
                    ->first();

                if (!$assignedAgentId && !empty($agentIds)) {
                    $assignedAgentId = $agentIds[$agentIndex++ % $totalAgents];
                }

                foreach ($ticketGroup as $ticket) {
                    $wasUnassigned = is_null($ticket->current_agent_id);

                    if ($wasUnassigned) {
                        $updates = [
                            'status' => 'Uncontacted PDS',
                            'status_id' => null,
                        ];

                        if ($assignedAgentId) {
                            $updates['current_agent_id'] = $assignedAgentId;
                        }

                        $ticket->update($updates);

                        $subject = ProductSubject::where('id', $ticket->subject_id)->first();
                        $lastHistory = TicketHistory::where('ticket_id', $ticket->id)
                            ->orderBy('id', 'desc')
                            ->first();

                        $newHistory = TicketHistory::create([
                            'company_id' => $ticket->company_id,
                            'ticket_id' => $ticket->id,
                            'bucket_data' => $ticket->bucket,
                            'note' => '',
                            'remark' => '',
                            'status' => 'Uncontacted PDS',
                            'sla_priority' => $subject?->priority,
                        ]);

                        if ($lastHistory) {
                            $oldTicketForms = TicketForm::where('ticket_history_id', $lastHistory->id)->get();

                            foreach ($oldTicketForms as $oldForm) {
                                $newForm = $oldForm->replicate();
                                $newForm->ticket_history_id = $newHistory->id;
                                $newForm->save();
                            }
                        }
                    } elseif ($assignedAgentId && $ticket->current_agent_id != $assignedAgentId) {
                        $ticket->update([
                            'current_agent_id' => $assignedAgentId,
                        ]);
                    }
                }
            }

            PdsCustomer::where('pds_id', $pds->id)
                ->where('company_id', $companyId)
                ->delete();

            return [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
            ];
        });
    }
}
