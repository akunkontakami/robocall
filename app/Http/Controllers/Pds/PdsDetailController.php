<?php

namespace App\Http\Controllers\Pds;

use Inertia\Inertia;
use App\Models\Pds\Pds;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Pds\SetupPdsService;
use App\Services\Data\CampaignService;
use App\Services\Data\TicketService;

class PdsDetailController extends Controller
{
    public function index($id)
    {
        return Inertia::render("PdsDetail/Index", [
            'data' => (new SetupPdsService())->find(user()->company_id, $id, [0]),
            'campaigns' => (new CampaignService())->listCampaigns(),
            'ivr' => (new SetupPdsService())->getAllIvr(),
            'routes' => (new SetupPdsService())->getAllRoute(),
            "id" => $id
        ]);
    }

    public function campaign($id)
    {
        return Inertia::render("PdsDetail/Campaign", [
            'data' => (new SetupPdsService())->find(user()->company_id, $id, [0]),
            "id" => $id,
            "statuses" => (new TicketService())->getStatus(user()->company_id)
        ]);
    }

    public function spvAgent($id)
    {
        return Inertia::render("PdsDetail/SpvAgent", [
            'data' => (new SetupPdsService())->find(user()->company_id, $id, [0]),
            "id" => $id
        ]);
    }
}
