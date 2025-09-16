<?php

namespace App\Models\Data;

use App\Models\Account\CompanyUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserMarketingCampaign extends Model
{
    use HasUuids;

    protected $table = 'user_marketing_campaigns';
    protected $guarded = [];

    public function companyUser()
    {
        return $this->belongsTo(CompanyUser::class, 'user_id', 'user_id');
    }
}
