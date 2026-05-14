<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['user_id', 'plot_id', 'type', 'file_path', 'data_snapshot'];
    protected $casts    = ['data_snapshot' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function plot() { return $this->belongsTo(Plot::class); }
}
