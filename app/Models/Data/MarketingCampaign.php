<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use App\Models\Data\UserMarketingCampaign;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MarketingCampaign extends Model
{
    use HasUuids;

    protected $table = 'marketing_campaigns';
    protected $guarded = [];

    public function spv()
    {
        return $this->hasMany(UserMarketingCampaign::class, 'marketing_campaign_id', 'id')
                ->where('role', 'spv');
    }
}
