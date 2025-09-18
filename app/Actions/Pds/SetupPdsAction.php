<?php

namespace App\Actions\Pds;

use App\Helpers\Dialer;
use App\Models\Pds\Pds;
use Illuminate\Support\Str;
use App\Models\Pds\PdsAgent;
use Illuminate\Http\Request;
use App\Models\Pds\PdsCustomer;
use Illuminate\Support\Facades\DB;
use App\Services\Data\TicketService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SetupPdsAction
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function execute(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:pds,pds_name',
            // 'trunk' => 'required|unique:pds,route',
            'trunk' => 'required',
            'ivr' => 'required|unique:pds,ivr'
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

            $dialer = Dialer::post("/campaign-dialer/setup-pds", [
                'tenant_id' => $user->tenant_id,
                'campaign_id' => $request->name,
                'route_id' => $request->trunk,
                'vdn' => $request->ivr,
            ]);

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
            'id' => 'required|exists:pds,id',
            'call_factor' => 'required',
            'call_wait' => 'required',
            'call_abandon_rate' => 'required',
            'call_limit' => 'required',
            'call_retry_after' => 'required',
            'call_retry_max' => 'required',
        ]);

        $customers = PdsCustomer::where("pds_id", $request->id)->count();

        if ($customers == 0) {
            throw ValidationException::withMessages([
                'call_factor' => 'Please upload customer data before starting'
            ]);
        }

        $agents = PdsAgent::where("pds_id", $request->id)->count();

        if ($agents == 0) {
            throw ValidationException::withMessages([
                'call_factor' => 'Please assign agent before starting'
            ]);
        }

        $user = user();

        return DB::transaction(function () use ($request, $user) {
            $pds = Pds::where("id", $request->id)->first();

            $pds->update([
                'call_factor' => $request->call_factor,
                'call_wait' => $request->call_wait,
                'call_abandon_rate' => $request->call_abandon_rate,
                'call_limit' => $request->call_limit,
                'call_retry_after' => $request->call_retry_after,
                'call_retry_max' => $request->call_retry_max,
                'is_running' => 1
            ]);


            $dialer = Dialer::post("/pds-start", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
                "CallFactor" => $request->call_factor,
                "CallWait" => $request->call_wait,
                "CallAbandonRate" => $request->call_abandon_rate,
                "CallLimit" => $request->call_limit,
                "CallRetryAfter" => $request->call_retry_after,
                "CallRetryMax" => $request->call_retry_max,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                $messages = [];
                $messages['call_factor'] = $errorMessage;

                throw ValidationException::withMessages($messages);
            }

            return [
                'pds' => $pds,
                'dialer_response' => $dialer,
            ];
        });
    }

    public function stop(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pds,id'
        ]);

        $pdsStatus = Pds::where("id", $request->id)->where("is_running", 0)->first();

        if ($pdsStatus) {
            throw new BadRequestException("PDS already stop");
            return;
        }

        $user = user();

        return DB::transaction(function () use ($request, $user) {
            $pds = Pds::where("id", $request->id)->first();

            $pds->update([
                'is_running' => 0
            ]);


            $dialer = Dialer::post("/pds-stop", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);
                return;
            }

            return [
                'pds' => $pds,
                'dialer_response' => $dialer,
            ];
        });
    }


    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pds,id'
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $user) {
            $pds = Pds::where("id", $request->id)->first();

            $dialer = Dialer::post("/campaign-dialer/releaseCustomerPDS", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);
                return;
            }

            $dialer = Dialer::post("/campaign-dialer/release-agent", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);
                return;
            }

            $dialer = Dialer::post("/campaign-dialer/unsetup-pds", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name
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
            'spv' => 'required'
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $user, $id) {
            $pds = Pds::where("id", $id)->first();
            $pds->update([
                'marketing_campaign_id' => $request->marketing_campaign,
                'spv_id' => $request->spv,
            ]);
        });
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'status' => 'required'
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $user, $id) {
            $pds = Pds::where("id", $id)->first();
            $customers = (new TicketService())->getCustByTicket($user->company_id, $pds->marketing_campaign_id, $pds->id, $request->status);

            foreach ($customers as $key => $value) {
                PdsCustomer::create([
                    'company_id' => $user->company_id,
                    'pds_id' => $pds->id,
                    'customer_id' => $value['customer_id'],
                    'phone' => $value['phone']
                ]);
            }

            $dialer = Dialer::post("/campaign-dialer/uploadJsonPDS", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name,
                "data" => $customers
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);
                return;
            }
        });
    }

    public function release(Request $request, $id)
    {
        $user = user();

        return DB::transaction(function () use ($request, $user, $id) {
            $pds = Pds::where("id", $id)->first();
            PdsCustomer::where("pds_id", $pds->id)->where("company_id", $user->company_id)->delete();

            $dialer = Dialer::post("/campaign-dialer/releaseCustomerPDS", [
                'tenant_id' => $pds->tenant_id,
                'campaign_id' => $pds->pds_name
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new BadRequestException($errorMessage);
                return;
            }
        });
    }
}
