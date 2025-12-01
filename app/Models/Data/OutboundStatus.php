<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundStatus extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'outbound_status_master';

    protected $guarded = [];

    public function sub()
    {
        return $this->hasMany(OutboundStatus::class, 'parent_id', 'id')->where("status", "active")->orderBy('sorting', 'asc');
    }
}
