<?php

namespace App\Models\Robocall;

use App\Models\Data\MarketingCampaign;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Robocall extends Model
{
    use HasUuids;
    protected $guarded = [];

    protected $casts = [
        'status_campaigns' => 'array'
    ];

    public function campaign(): HasOne
    {
        return $this->hasOne(MarketingCampaign::class, 'id', 'marketing_campaign_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(RobocallCustomer::class, 'robocall_id', 'id');
    }
}
