<?php

namespace App\Models\Pds;

use App\Models\Account\CompanyUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PdsAgent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pds_agents';
    protected $guarded = [];

    public function companyUser(): HasOne
    {
        return $this->hasOne(CompanyUser::class, 'user_id', 'user_id');
    }
}
