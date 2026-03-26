<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasUuids;

    protected $table = 'tickets';
    protected $guarded = [];

    public function dataBucket()
    {
        return $this->hasOne(OutboundDataUploadBucket::class, 'id', 'outbound_data_upload_bucket_id');
    }

    public function additionalPhones(): HasMany
    {
        return $this->hasMany(TicketAdditionalPhone::class, 'customer_number', 'customer_number')->where('type', 'sip')->orderBy('created_at', 'asc');
    }
}
