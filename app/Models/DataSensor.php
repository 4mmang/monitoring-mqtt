<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSensor extends Model
{
    protected $table = 'data_sensor';
    
    protected $fillable = [
        'suhu',
        'kelembaban',
        'gas_amonia',
        'gerakan',
    ];
}
