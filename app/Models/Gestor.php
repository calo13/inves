<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestor extends Model
{
    protected $table = 'gestores';

    protected $guarded = [];

    protected $casts = ['activo' => 'boolean', 'capacidad_relativa' => 'float'];
}
