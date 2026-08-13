<?php

namespace App\Http\Controllers\Pds;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Actions\Pds\SetupPdsAction;
use App\Http\Controllers\Controller;
use App\Services\Pds\SetupPdsService;
use App\Services\Data\CampaignService;
use App\Http\Resources\Pds\SetupPdsResource;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SetupPdsController extends Controller
{
    public function index()
    {
        return Inertia::render("Pds/Setup", [
            'campaigns' => (new CampaignService())->listCampaigns(),
            'ivr' => (new SetupPdsService())->getAllIvr(),
            'route' => (new SetupPdsService())->getAllRoute(),
        ]);
    }

    public function datatable()
    {
        return SetupPdsResource::collection(
            (new SetupPdsService())->get(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }

    public function store(Request $request, SetupPdsAction $action)
    {
        try {
            $action->execute($request);

            return to_route('pds.setup')->with('success', 'Successfully create PDS');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function start(Request $request, SetupPdsAction $action)
    {
        try {
            $action->start($request);

            return back()->with('success', 'Successfully start PDS');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function stop(Request $request, SetupPdsAction $action)
    {
        try {
            $stoppedCount = $action->stop($request);

            return back()->with(
                'success',
                $stoppedCount === 1
                    ? 'Successfully stop PDS'
                    : 'PDS stop queued successfully'
            );
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, SetupPdsAction $action)
    {
        try {
            $action->delete($request);

            return back()->with('success', 'Successfully deleted PDS');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function release(Request $request, SetupPdsAction $action)
    {
        try {
            $ids = $request->input('ids', $request->input('id'));
            $releasedCount = $action->release($request, $ids);

            return back()->with(
                'success',
                $releasedCount === 1
                    ? 'Successfully release customers'
                    : 'Customer release queued successfully'
            );
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }
}
