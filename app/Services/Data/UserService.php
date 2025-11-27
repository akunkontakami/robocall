<?php

namespace App\Services\Data;

use App\Models\Account\CompanyUser;
use App\Models\Data\MarketingCampaign;

class UserService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function listSpv()
    {
        $user = user();

        return CompanyUser::whereHas("user")
                ->select([
                    "name as value",
                    "company_id",
                    "id"
                ])
                ->with([
                    'user'
                ])
                ->where("company_id", $user->company_id)
                ->where("role", "spv")
                ->where("status", "active")
                ->orderBy("created_at", "desc")->get();
    }

    public function listAgent()
    {
        $user = user();

        return CompanyUser::whereHas("user")
                ->select([
                    "name as value",
                    "company_id",
                    "id"
                ])
                ->with([
                    'user'
                ])
                ->where("company_id", $user->company_id)
                ->where("role", "agent")
                ->where("status", "active")
                ->orderBy("created_at", "desc")->get();
    }
}
