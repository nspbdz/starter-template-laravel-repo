<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class token extends Model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    use HasFactory;
    protected $fillable = [
        'id','id_users','token_type','token','timestamp','created_at','updated_at'
    ];
    public $table = 'gadai_bpkb.token';
}
