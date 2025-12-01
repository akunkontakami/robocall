<?php

namespace App\Services\Data;

use App\Models\Data\MarketingCampaign;

class CampaignService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function listCampaigns($multipleSelect = false)
    {
        $user = user();

        return MarketingCampaign::whereHas("spv")
                ->select(!$multipleSelect ? [
                    "id as value",
                    "name as label",
                    "company_id",
                    "id"
                ] : [
                    "name as value",
                    "company_id",
                    "id"
                ])
                ->with([
                    'spv',
                    'spv.companyUser'
                ])
                ->where("company_id", $user->company_id)
                ->where("status", "active")
                ->orderBy("created_at", "desc")->get();
    }
}
