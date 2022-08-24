<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_users',         
        'plate_no',
        'province_and_city',
        'brand_and_type',
        'vehicle_year',
        'bpkb',
        'rumah',
        'pajak'
    ];

}
