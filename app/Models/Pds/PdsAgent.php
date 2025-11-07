<?php

namespace App\Models\Pds;

use App\Models\Data\Ticket;
use App\Models\Account\User;
use App\Models\Account\CompanyUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PdsAgent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pds_agents';
    protected $guarded = [];

    public function companyUser(): HasOne
    {
        return $this->hasOne(CompanyUser::class, 'user_id', 'user_id');
    }

    public function pds(): BelongsTo
    {
        return $this->belongsTo(Pds::class, 'pds_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tickets()
    {
        return $this->hasManyThrough(
            Ticket::class,
            Pds::class,
            'id',
            'marketing_campaign_id',
            'pds_id',
            'marketing_campaign_id'
        );
    }
}
