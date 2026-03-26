<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAdditionalPhone extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ticket_additional_phones';
    protected $guarded = [];
}
