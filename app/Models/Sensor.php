<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $fillable = ['plot_id', 'name', 'type', 'unit', 'status'];

    public function plot()    { return $this->belongsTo(Plot::class); }
    public function readings(){ return $this->hasMany(Reading::class); }

    public function latestReading()
    {
        return $this->hasOne(Reading::class)->latestOfMany();
    }
}
