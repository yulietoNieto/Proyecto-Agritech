<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plot extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'area_hectares', 'location_description', 'user_id'];

    public function user()    { return $this->belongsTo(User::class); }
    public function sensors() { return $this->hasMany(Sensor::class); }
    public function reports() { return $this->hasMany(Report::class); }
}

// ── Sensor ────────────────────────────────────────────────────────────────────
// app/Models/Sensor.php — inline for brevity, split to separate files in prod.
