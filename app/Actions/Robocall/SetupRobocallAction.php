<?php

namespace App\Actions\Robocall;

use App\Helpers\Dialer;
use App\Models\Robocall\Robocall;
use App\Models\Robocall\RobocallCustomer;
use App\Models\Robocall\RobocallFile;
use App\Services\Data\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetupRobocallAction
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:robocalls,robocall_name,'.$id,
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $id) {
            $robocall = Robocall::where('id', $id)->first();
            $robocall->update([
                'robocall_name' => $request->name,
            ]);
        });
    }

    public function execute(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:robocalls,robocall_name',
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $user) {
            $robocall = Robocall::create([
                'company_id' => $user->company_id,
                'robocall_name' => $request->name,
                'is_running' => 0,
            ]);

            return [
                'robocall' => $robocall,
            ];
        });
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:robocalls,id',
        ]);

        $user = user();

        return DB::transaction(function () use ($request) {
            $robocall = Robocall::where('id', $request->id)->first();
            $robocall->delete();

            $dialer = Dialer::post('/customerCallblasterJson/releaseCustomer', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new \Exception($errorMessage);

                return;
            }

            $dialer = Dialer::post('/campaign-dialer/unsetup-callblaster', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new \Exception($errorMessage);

                return;
            }

            return [
                'robocall' => $robocall,
            ];
        });
    }

    public function assignCampaign(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            'marketing_campaign' => 'required',
        ]);

        $user = user();

        return DB::transaction(function () use ($request, $user, $id) {
            $robocall = Robocall::where('id', $id)->first();
            $customers = (new TicketService())->getCustByTicket($user->company_id, $request->marketing_campaign, $robocall->id, $request->status);

            foreach ($customers as $key => $value) {
                RobocallCustomer::create([
                    'company_id' => $user->company_id,
                    'robocall_id' => $robocall->id,
                    'customer_id' => $value['customer_id'],
                    'name' => $value['customer_name'],
                    'phone' => $value['phone'],
                ]);

                $dialer = Dialer::post('/customerCallblasterJson/uploadJsonPDS', [
                    'tenant_id' => $robocall->company_id,
                    'campaign_id' => $robocall->robocall_name,
                    'data' => $customers,
                ]);
            }

            $statuses = array_values(array_unique($request->status));

            $robocall->update([
                'data_type' => 'campaign',
                'status_campaigns' => $statuses,
                'marketing_campaign_id' => $request->marketing_campaign,
            ]);
        });
    }

    public function assignUpload(Request $request, $id)
    {
        $user = user();

        $request->validate([
            'file' => 'required',
        ]);

        return DB::transaction(function () use ($id, $user, $request) {
            $robocall = Robocall::where('id', $id)->first();

            if (!$request->id) {
                $robocallFile = RobocallFile::create([
                    'company_id' => $user->company_id,
                    'robocall_id' => $robocall->id,
                    'file_name' => $request->file('file')->getClientOriginalName(),
                ]);

                RobocallCustomer::where('robocall_id', $robocall->id)->where('company_id', $user->company_id)->delete();
            } else {
                $robocallFile = RobocallFile::where('id', $request->id)->first();
            }

            $file = fopen($request->file('file')->getRealPath(), 'r');

            $firstLine = fgets($file);

            rewind($file);

            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',')
                ? ';'
                : ',';

            $header = array_map(function ($item) {
                $item = preg_replace('/^\xEF\xBB\xBF/', '', $item);
                return strtolower(trim($item));
            }, fgetcsv($file, 0, $delimiter));


            $customers = [];

            while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
                if (count($header) !== count($row)) {
                    continue;
                }

                $data = array_combine($header, $row);

                $customerId = $data['customer_number']
                    ?? $data['customer_id']
                    ?? $data['Customer_Number']
                    ?? $data['CustomerNumber']
                    ?? '';

                $phone = $data['phone_number']
                    ?? $data['Phone_Number']
                    ?? $data['PhoneNumber']
                    ?? '';

                $name = $data['name']
                    ?? $data['Name']
                    ?? '';

                if (!$phone || !$customerId) {
                    continue;
                }

                $phone = preg_replace('/[^0-9]/', '', trim($phone));
                if (str_starts_with($phone, '62')) {
                    $phone = '0'.substr($phone, 2);
                } elseif (!str_starts_with($phone, '0')) {
                    $phone = '0'.$phone;
                }

                $exists = RobocallCustomer::where('company_id', $user->company_id)
                    ->where('robocall_id', $robocall->id)
                    ->where('customer_id', $customerId)
                    ->where('phone', $phone)
                    ->exists();

                if ($exists) {
                    continue;
                }

                RobocallCustomer::create([
                    'company_id' => $user->company_id,
                    'robocall_id' => $robocall->id,
                    'robocall_file_id' => $robocallFile->id,
                    'customer_id' => $customerId,
                    'phone' => $phone,
                    'name' => $name,
                ]);

                $customers[] = [
                    'customer_id' => $customerId,
                    'phone' => $phone,
                ];
            }

            fclose($file);

            if (count($customers) > 0) {
                Dialer::uploadCsvCallblast(
                    $robocall->company_id,
                    $robocall->robocall_name,
                    $request->file('file')->getRealPath()
                );
            }

            $robocall->update([
                'data_type' => 'upload',
                'status_campaigns' => [],
                'marketing_campaign_id' => null,
            ]);
        });
    }

    public function release(Request $request, $id)
    {
        $user = user();

        return DB::transaction(function () use ($user, $id) {
            $robocall = Robocall::where('id', $id)->first();
            RobocallCustomer::where('robocall_id', $robocall->id)->where('company_id', $user->company_id)->delete();

            $robocall->update([
                'data_type' => null,
                'status_campaigns' => [],
                'marketing_campaign_id' => null,
            ]);

            $dialer = Dialer::post('/customerCallblasterJson/releaseCustomer', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
            ]);
        });
    }

    public function deleteUpload(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:robocall_files,id',
        ]);

        $user = user();

        return DB::transaction(function () use ($request) {
            $robocallFile = RobocallFile::where('id', $request->id)->first();
            $robocallId = $robocallFile->robocall_id;
            $robocallFile->delete();
            $robocall = Robocall::where('id', $robocallId)->first();

            $files = RobocallFile::where('robocall_id', $robocallId)->count();

            if ($files === 0) {
                $robocall->update([
                    'data_type' => null,
                    'status_campaigns' => [],
                    'marketing_campaign_id' => null,
                ]);
            }

            $dialer = Dialer::post('/customerCallblasterJson/releaseCustomer', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
            ]);
        });
    }

    public function start(Request $request)
    {
        $request->validate([
            'trunk' => 'required',
            'ivr' => 'required|unique:robocalls,ivr',
            'call_limit' => 'required',
        ], [
            'ivr.required' => 'VDN is required',
            'ivr.unique' => 'VDN has been used',
        ]);

        $customers = RobocallCustomer::where('robocall_id', $request->id)->count();

        if ($customers == 0) {
            throw ValidationException::withMessages(['call_limit' => 'Please upload customer data before starting']);
        }

        $user = user();

        return DB::transaction(function () use ($request) {
            $robocall = Robocall::where('id', $request->id)->first();

            $robocall->update([
                'route' => $request->trunk,
                'ivr' => $request->ivr,
                'call_limit' => $request->call_limit,
                'is_running' => 1,
            ]);

            $dialer = Dialer::post('/callblaster-start', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
                'CallLimit' => $request->call_limit,
                'route_id' => $request->trunk,
                'vdn' => $request->vdn,
            ]);

            \Log::info("LOG START", [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
                'CallLimit' => $request->call_limit,
                'route_id' => $request->trunk,
                'vdn' => $request->vdn,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                $messages = [];
                $messages['call_limit'] = $errorMessage;

                throw ValidationException::withMessages($messages);
            }

            return [
                'robocall' => $robocall,
                // 'dialer_response' => $dialer,
            ];
        });
    }

    public function stop(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:robocalls,id',
        ]);

        $robocallStatus = Robocall::where('id', $request->id)->where('is_running', 0)->first();

        if ($robocallStatus) {
            throw new \Exception('Robocall already stop');

            return;
        }

        $user = user();

        return DB::transaction(function () use ($request) {
            $robocall = Robocall::where('id', $request->id)->first();

            $robocall->update([
                'is_running' => 0,
                'route' => null,
                'ivr' => null,
                'call_limit' => null,
            ]);

            $dialer = Dialer::post('/callblaster-stop', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new \Exception($errorMessage);

                return;
            }

            return [
                'robocall' => $robocall,
                // 'dialer_response' => $dialer,
            ];
        });
    }

    public function pause(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:robocalls,id',
        ]);

        $robocallStatus = Robocall::where('id', $request->id)->where('is_running', 2)->first();

        if ($robocallStatus) {
            throw new \Exception('Robocall already pause');

            return;
        }

        $user = user();

        return DB::transaction(function () use ($request) {
            $robocall = Robocall::where('id', $request->id)->first();

            $robocall->update([
                'is_running' => 2,
            ]);

            $dialer = Dialer::post('/callblaster-pause', [
                'tenant_id' => $robocall->company_id,
                'campaign_id' => $robocall->robocall_name,
            ]);

            if (!empty($dialer['errors']) && is_string($dialer['errors'])) {
                $errorMessage = $dialer['errors'];

                throw new \Exception($errorMessage);

                return;
            }

            return [
                'robocall' => $robocall,
                // 'dialer_response' => $dialer,
            ];
        });
    }
}
