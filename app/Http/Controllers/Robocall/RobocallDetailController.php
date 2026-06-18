<?php

namespace App\Http\Controllers\Robocall;

use App\Actions\Robocall\SetupRobocallAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Robocall\UploadRobocallResource;
use App\Services\Data\CampaignService;
use App\Services\Data\TicketService;
use App\Services\Robocall\SetupRobocallService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RobocallDetailController extends Controller
{
    public function index($id)
    {
        return Inertia::render('RobocallDetail/Index', [
            'data' => (new SetupRobocallService())->find(user()->company_id, $id, [0]),
            'id' => $id,
        ]);
    }

    public function update(Request $request, SetupRobocallAction $action, $id)
    {
        try {
            $action->update($request, $id);

            return to_route('robocall.detail', $id)->with('success', 'Successfully update Robocall');
        } catch (\Throwable $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function campaign($id)
    {
        $data = (new SetupRobocallService())->find(user()->company_id, $id, [0]);

        if ($data->data_type == 'upload') {
            abort(404);
        }

        return Inertia::render('RobocallDetail/Campaign', [
            'data' => $data,
            'id' => $id,
            'statuses' => (new TicketService())->getStatus(user()->company_id, $data->marketing_campaign_id, $data->id),
            'campaigns' => (new CampaignService())->listCampaignRobocalls(),
        ]);
    }

    public function assignCampaign(Request $request, SetupRobocallAction $action, $id)
    {
        try {
            $action->assignCampaign($request, $id);

            return to_route('robocall.detail.campaign', $id)->with('success', 'Successfully assign customers');
        } catch (\Throwable $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function status($id, $campaignId)
    {
        return response()->json([
            'statuses' => (new TicketService())->getStatus(user()->company_id, $campaignId, $id),
        ]);
    }

    public function upload($id)
    {
        $data = (new SetupRobocallService())->find(user()->company_id, $id, [0]);

        if ($data->data_type == 'campaign') {
            abort(404);
        }

        return Inertia::render('RobocallDetail/Upload', [
            'data' => $data,
            'id' => $id,
            'template' => asset('sample/robocall_template.csv'),
        ]);
    }

    public function assignUpload(Request $request, SetupRobocallAction $action, $id)
    {
        try {
            $action->assignUpload($request, $id);

            return to_route('robocall.detail.upload', $id)->with('success', 'Successfully assign customers');
        } catch (\Throwable $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function uploadDatatable($id)
    {
        return UploadRobocallResource::collection(
            (new SetupRobocallService())->getUploads(
                companyId: user()->company_id,
                id: $id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }

    public function deleteUpload(Request $request, SetupRobocallAction $action, $id)
    {
        try {
            $action->deleteUpload($request);

            return to_route('robocall.detail.upload', $id)->with('success', 'Successfully deleted data');
        } catch (\Throwable $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }
}
