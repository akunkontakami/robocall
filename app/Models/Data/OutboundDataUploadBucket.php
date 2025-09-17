<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OutboundDataUploadBucket extends Model
{
    use HasUuids;

    protected $table = "outbound_data_upload_buckets";
}
