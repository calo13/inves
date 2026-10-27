<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    protected $table = 'sesiones';

    protected $guarded = [];

    protected $casts = ['fecha' => 'date', 'es_critica' => 'boolean'];

    public $timestamps = false;

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
