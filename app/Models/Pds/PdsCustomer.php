<?php

namespace App\Models\Pds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PdsCustomer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pds_customers';
    protected $guarded = [];
}
