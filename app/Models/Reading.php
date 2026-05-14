<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    protected $fillable = ['sensor_id', 'value', 'unit', 'status'];

    public function sensor() { return $this->belongsTo(Sensor::class); }
}
