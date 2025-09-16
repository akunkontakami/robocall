<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ticket extends Model
{
    use HasUuids;

    protected $table = 'tickets';
    protected $guarded = [];

}
