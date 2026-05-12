<?php

namespace App\Models\Robocall;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RobocallFile extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function customers(): HasMany
    {
        return $this->hasMany(RobocallCustomer::class, 'robocall_file_id', 'id');
    }
}
