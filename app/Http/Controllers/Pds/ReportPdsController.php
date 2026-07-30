<?php

namespace App\Http\Controllers\Pds;

use App\Exports\PdsAgentExport;
use App\Exports\PdsCampaignExport;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Exports\PdsTrackingExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\Pds\SetupPdsService;
use App\Services\Pds\MonitoringPdsService;
use App\Http\Resources\Pds\MonitoringResource;
use App\Http\Resources\Pds\PdsHistoryResource;
use App\Http\Resources\Pds\ReportAgentResource;
use App\Http\Resources\Pds\ReportTrackResource;
use App\Http\Resources\Pds\ReportCampaignResource;
use App\Services\Data\CampaignService;
use App\Services\Data\TicketService;
use App\Services\Data\UserService;

class ReportPdsController extends Controller
{

    public function report()
    {
        return Inertia::render("Pds/Report", [
            "pds" => (new SetupPdsService())->listItems(),
            "campaigns" => (new CampaignService())->listCampaigns(true),
            "spv" => (new UserService())->listSpv(),
            "agents" => (new UserService())->listAgent(),
            "outbounds" => (new TicketService())->getOutboundStatus(user()->company_id)
        ]);
    }

    public function campaignDatatable()
    {
        $data = (new SetupPdsService())->sessionByCampaign(
            companyId: user()->company_id,
            start_date: request('created_start', now()->toDateString()),
            end_date: request('created_end', now()->toDateString()),
            campaignId: request('filter.campaigns.0'),
            search: request('search', ''),
            limit: request('limit', 10),
            page: request('page', 1),
        );

        return response()->json($data);
    }

    public function agentDatatable()
    {
        return ReportAgentResource::collection(
            (new SetupPdsService())->getByAgent(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }

    public function trackingDatatable()
    {
        return ReportTrackResource::collection(
            (new SetupPdsService())->getByCampaign(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );
    }

    public function trackingExport(Request $request)
    {
        $outbounds = (new TicketService())->getOutboundStatus(user()->company_id);
        $dataCollection = ReportTrackResource::collection(
            (new SetupPdsService())->getByCampaign(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: null
            )
        );

        $data = $dataCollection->toArray($request);

        $filename = 'pds_tracking_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PdsTrackingExport($data, $outbounds), $filename);
    }

    public function agentExport(Request $request)
    {
        $outbounds = (new TicketService())->getOutboundStatus(user()->company_id);
        $dataCollection = ReportAgentResource::collection(
            (new SetupPdsService())->getByAgent(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: null
            )
        );

        $data = $dataCollection->toArray($request);

        $filename = 'pds_agent_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PdsAgentExport($data, $outbounds), $filename);
    }

    public function campaignExport(Request $request)
    {
        $outbounds = (new TicketService())->getOutboundStatus(user()->company_id);
        $dataCollection = ReportCampaignResource::collection(
            (new SetupPdsService())->getByCampaign(
                companyId: user()->company_id,
                search: request('search', ''),
                filter: request('filter', []),
                limit: request('limit', 10),
            )
        );

        $data = $dataCollection->toArray($request);

        $filename = 'pds_campaign_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PdsCampaignExport($data, $outbounds), $filename);
    }
}
