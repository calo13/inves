<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Corrida extends Model
{
    protected $table = 'corridas_optimizacion';

    protected $guarded = [];

    protected $casts = ['parametros' => 'array', 'resultado' => 'array', 'antes' => 'array', 'aplicada_en' => 'datetime'];
}
