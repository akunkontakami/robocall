<?php

namespace App\Models\Account;

use App\Enum\StatusEnum;
use App\Models\Account\CompanyProfile;
use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyUser extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'company_users';

    protected $guarded = [];

    public $casts = [
        'status' => StatusEnum::class
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'company_id', 'company_id');
    }

}