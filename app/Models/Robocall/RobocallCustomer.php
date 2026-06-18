<?php

namespace App\Models\Robocall;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RobocallCustomer extends Model
{
    use HasUuids;

    protected $guarded = [];
}
