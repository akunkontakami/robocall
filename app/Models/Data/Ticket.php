<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ticket extends Model
{
    use HasUuids;

    protected $table = 'tickets';
    protected $guarded = [];

    public function dataBucket()
    {
        return $this->hasOne(OutboundDataUploadBucket::class, 'id', 'outbound_data_upload_bucket_id');
    }

}
