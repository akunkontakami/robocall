<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TicketForm extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'escalation_1_shared_id' => 'array',
        'escalation_2_shared_id' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'key',
    ];

    public function getOptionsAttribute()
    {
        if (isset($this->attributes['options'])) {
            $options = $this->attributes['options'];

            // Decode the JSON string
            $decodedOptions = json_decode($options);

            // Check for JSON decoding errors
            if (json_last_error() === JSON_ERROR_NONE) {
                return is_string($decodedOptions) ? json_decode($decodedOptions) : $decodedOptions;
            } else {
                return []; // Return an empty array if decoding fails
            }
        }

        return []; // Return an empty array if 'options' attribute is not set
    }

    public function getKeyAttribute()
    {
        return Str::slug($this->id, '_');
    }
}
