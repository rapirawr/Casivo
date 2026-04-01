<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $fillable = [
        'device_id',
        'suhu',
        'kelembaban',
        'kualitas_udara',
        'penggunaan_energi',
        'wifi_rssi',
        'wifi_ssid',
        'internal_temp',
        'uptime',
        'free_ram',
    ];

    protected $casts = [
        'suhu' => 'float',
        'kelembaban' => 'float',
        'kualitas_udara' => 'float',
        'penggunaan_energi' => 'float',
    ];

    /**
     * Get the latest sensor reading
     */
    public function scopeLatestReading($query, $deviceId = null)
    {
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        return $query->latest()->first();
    }
}
