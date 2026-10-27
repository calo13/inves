<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $guarded = [];

    protected $casts = ['fecha_ingreso' => 'datetime', 'fecha_asignacion' => 'datetime', 'fecha_inicio' => 'date', 'fecha_fin' => 'date', 'docente_nuevo' => 'boolean', 'primera_vez' => 'boolean', 'tema_nuevo' => 'boolean'];

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }

    public function sesiones()
    {
        return $this->hasMany(Sesion::class);
    }

    public function critica(): bool
    {
        return $this->docente_nuevo || $this->primera_vez || $this->tema_nuevo;
    }
}
