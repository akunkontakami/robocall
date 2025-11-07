<?php

namespace App\Models\Pds;

use App\Models\Account\User;
use App\Models\Data\MarketingCampaign;
use App\Models\Data\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pds extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pds';
    protected $guarded = [];

    public function campaign(): HasOne
    {
        return $this->hasOne(MarketingCampaign::class, 'id', 'marketing_campaign_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(PdsAgent::class, 'pds_id', 'id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(PdsCustomer::class, 'pds_id', 'id');
    }

    public function spv(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'spv_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'marketing_campaign_id', 'marketing_campaign_id');
    }
}
