<?php

namespace App\Models\Account;

use App\Enum\Role;
use App\Models\Account\CompanyUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password'];
    protected $casts = [
        'role' => Role::class
    ];


    public function companyUser()
    {
        return $this->hasOne(CompanyUser::class, 'user_id', 'id');
    }
}
