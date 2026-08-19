<?php

namespace App\Http\Controllers\Pds;

use App\Actions\Pds\SetupPdsAction;
use App\Http\Controllers\Controller;
use App\Services\Data\CampaignService;
use App\Services\Data\TicketService;
use App\Services\Pds\SetupPdsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PdsDetailController extends Controller
{
    public function index($id)
    {
        return Inertia::render('PdsDetail/Index', [
            'data' => (new SetupPdsService())->find(user()->company_id, $id, [0]),
            'campaigns' => (new CampaignService())->listCampaigns(),
            'ivr' => (new SetupPdsService())->getAllIvr(),
            'routes' => (new SetupPdsService())->getAllRoute(),
            'id' => $id,
        ]);
    }

    public function campaign($id)
    {
        $data = (new SetupPdsService())->find(user()->company_id, $id, [0]);

        return Inertia::render('PdsDetail/Campaign', [
            'data' => $data,
            'id' => $id,
            'offices' => (new TicketService())->getOffices(user()->company_id, $data->marketing_campaign_id, $data->id),
        ]);
    }

    public function status(Request $request, $id)
    {
        $data = (new SetupPdsService())->find(user()->company_id, $id, [0]);

        return response()->json([
            'data' => (new TicketService())->getStatus(
                user()->company_id,
                $data->marketing_campaign_id,
                $data->id,
                $request->input('type'),
                $request->input('offices', []),
                $request->input('risk_criteria', []),
            ),
        ]);
    }

    public function options($id)
    {
        $data = (new SetupPdsService())->find(user()->company_id, $id, [0]);
        $service = new TicketService();

        return response()->json([
            'offices' => $service->getOffices(user()->company_id, $data->marketing_campaign_id, $data->id),
        ]);
    }

    public function spvAgent($id)
    {
        return Inertia::render('PdsDetail/SpvAgent', [
            'data' => (new SetupPdsService())->find(user()->company_id, $id, [0]),
            'id' => $id,
        ]);
    }

    public function update(Request $request, SetupPdsAction $action, $id)
    {
        try {
            $action->update($request, $id);

            return to_route('pds.detail', $id)->with('success', 'Successfully update PDS');
        } catch (\Throwable $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function assign(Request $request, SetupPdsAction $action, $id)
    {
        try {
            $action->assign($request, $id);

            return to_route('pds.detail.campaign', $id)->with('success', 'Successfully assign customers');
        } catch (\Throwable $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }
}
