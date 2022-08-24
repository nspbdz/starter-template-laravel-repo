<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foto_unit extends Model
{
    use HasFactory;
    protected $fillable = [
        'id','id_users','name','path','sort'

    ];

    public $table ='foto_unit';

}
