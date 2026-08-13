<?php

namespace App\Http\Controllers\Pds;

use App\Actions\Pds\SetupPdsAction;
use App\Http\Controllers\Controller;
use App\Models\Pds\Pds;
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
        $pdsList = $this->selectedPds($request, $id);
        $service = new TicketService();
        $statuses = $pdsList->flatMap(fn ($pds) => $service->getStatus(
            user()->company_id,
            $pds->marketing_campaign_id,
            $pds->id,
        ))->unique('id')->values();

        return response()->json([
            'data' => $statuses,
        ]);
    }

    public function options(Request $request, $id)
    {
        $pdsList = $this->selectedPds($request, $id);
        $service = new TicketService();
        $statuses = $pdsList->flatMap(fn ($pds) => $service->getStatus(
            user()->company_id,
            $pds->marketing_campaign_id,
            $pds->id,
        ))->unique('id')->values();
        $offices = $pdsList
            ->flatMap(fn ($pds) => $service->getOffices(
                user()->company_id,
                $pds->marketing_campaign_id,
                $pds->id,
            ))
            ->values();

        return response()->json([
            'statuses' => $statuses,
            'offices' => $offices,
            'selected_pds' => $pdsList->map(fn ($pds) => [
                'id' => $pds->id,
                'campaign_id' => $pds->marketing_campaign_id,
            ])->values(),
        ]);
    }

    private function selectedPds(Request $request, $id)
    {
        $requestedIds = $request->input('ids', [$id]);
        $requestedIds = is_array($requestedIds)
            ? $requestedIds
            : explode(',', (string) $requestedIds);

        $ids = collect($requestedIds)
            ->push($id)
            ->filter()
            ->unique()
            ->values();

        return Pds::where('company_id', user()->company_id)
            ->whereIn('id', $ids)
            ->where('is_running', 0)
            ->get();
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
