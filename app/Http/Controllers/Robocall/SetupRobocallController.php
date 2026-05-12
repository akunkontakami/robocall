<?php

namespace App\Http\Controllers\Robocall;

use App\Actions\Robocall\SetupRobocallAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Robocall\SetupRobocallResource;
use App\Services\Robocall\SetupRobocallService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SetupRobocallController extends Controller
{
    public function index()
    {
        return Inertia::render('Robocall/Setup', [
            'ivr' => (new SetupRobocallService())->getAllIvr(),
            'route' => (new SetupRobocallService())->getAllRoute(),
        ]);
    }

    public function datatable()
    {
        return SetupRobocallResource::collection(
            (new SetupRobocallService())->get(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }

    public function store(Request $request, SetupRobocallAction $action)
    {
        try {
            $action->execute($request);

            return to_route('robocall.setup')->with('success', 'Successfully create Robocall');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function start(Request $request, SetupRobocallAction $action)
    {
        try {
            $action->start($request);

            return to_route('robocall.setup')->with('success', 'Successfully start Robocall');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, SetupRobocallAction $action)
    {
        try {
            $action->delete($request);

            return to_route('robocall.setup')->with('success', 'Successfully deleted Robocall');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function stop(Request $request, SetupRobocallAction $action)
    {
        try {
            $action->stop($request);

            return to_route('robocall.setup')->with('success', 'Successfully stop Robocall');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function release(Request $request, SetupRobocallAction $action)
    {
        try {
            $action->release($request, $request->id);

            return to_route('robocall.setup')->with('success', 'Successfully release customers');
        } catch (BadRequestException $e) {
            return back()->with(['error' => $e->getMessage()]);
        }
    }
}
