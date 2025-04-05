<?php

namespace App\Models\Account;


use App\Enum\Role;
use App\Models\Account\Company;
use App\Models\Account\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyAccount extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'company_account';

    protected $guarded = [];
    protected $hidden = ['password'];
    protected $casts = [
        'role' => Role::class
    ];

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
