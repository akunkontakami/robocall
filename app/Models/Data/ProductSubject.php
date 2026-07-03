<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSubject extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'company_product_subjects';
}
