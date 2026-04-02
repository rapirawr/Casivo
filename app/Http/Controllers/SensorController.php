<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SensorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id'         => 'nullable|string|max:50',
            'suhu'              => 'required|numeric|min:-40|max:80',
            'kelembaban'        => 'required|numeric|min:0|max:100',
            'kualitas_udara'    => 'nullable|numeric|min:0|max:500',
            'penggunaan_energi' => 'nullable|numeric|min:0',
            'wifi_rssi'         => 'nullable|numeric',
            'wifi_ssid'         => 'nullable|string|max:100',
            'internal_temp'     => 'nullable|numeric',
            'uptime'            => 'nullable|numeric',
            'free_ram'          => 'nullable|numeric',
        ]);

        $deviceId = $validated['device_id'] ?? 'esp32-01';

        $sensor = Sensor::create([
            'device_id'         => $deviceId,
            'suhu'              => $validated['suhu'],
            'kelembaban'        => $validated['kelembaban'],
            'kualitas_udara'    => $validated['kualitas_udara'] ?? null,
            'penggunaan_energi' => $validated['penggunaan_energi'] ?? null,
            'wifi_rssi'         => $validated['wifi_rssi'] ?? null,
            'wifi_ssid'         => $validated['wifi_ssid'] ?? null,
            'internal_temp'     => $validated['internal_temp'] ?? null,
            'uptime'            => $validated['uptime'] ?? null,
            'free_ram'          => $validated['free_ram'] ?? null,
        ]);

        // Hapus semua data sebelumnya untuk device ini agar hanya tersisa data terbaru
        Sensor::where('device_id', $deviceId)
              ->where('id', '!=', $sensor->id)
              ->delete();

        return response()->json([
            'status'  => 'ok',
            'message' => 'Data sensor berhasil disimpan',
            'data'    => $sensor,
        ], 201);
    }

    /**
     * Get latest sensor reading.
     * GET /api/sensor/latest
     */
    public function latest(): JsonResponse
    {
        $latest = Sensor::latest()->first();

        if (!$latest) {
            return response()->json([
                'temperature'  => 0,
                'humidity'     => 0,
                'air_quality'  => 0,
                'energy_usage' => 0,
                'wifi_rssi'    => 0,
                'internal_temp'=> 0,
                'uptime'       => 0,
                'free_ram'     => 0,
                'source'       => 'no_data',
                'updated_at'   => null,
            ]);
        }

        return response()->json([
            'temperature'  => round($latest->suhu, 1),
            'humidity'     => round($latest->kelembaban, 1),
            'air_quality'  => round($latest->kualitas_udara ?? 0, 0),
            'energy_usage' => round($latest->penggunaan_energi ?? 0, 1),
            'wifi_rssi'    => $latest->wifi_rssi ?? 0,
            'internal_temp'=> $latest->internal_temp ?? 0,
            'uptime'       => $latest->uptime ?? 0,
            'free_ram'     => $latest->free_ram ?? 0,
            'source'       => 'esp32',
            'device_id'    => $latest->device_id,
            'updated_at'   => $latest->created_at->toIso8601String(),
        ]);
    }

    /**
     * Get sensor history (last N readings).
     * GET /api/sensor/history?limit=50
     */
    public function history(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 50), 200);

        $readings = Sensor::latest()
            ->take($limit)
            ->get()
            ->map(function ($reading) {
                return [
                    'id'           => $reading->id,
                    'device_id'    => $reading->device_id,
                    'temperature'  => round($reading->suhu, 1),
                    'humidity'     => round($reading->kelembaban, 1),
                    'air_quality'  => round($reading->kualitas_udara ?? 0, 0),
                    'energy_usage' => round($reading->penggunaan_energi ?? 0, 1),
                    'created_at'   => $reading->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => 'ok',
            'count'  => $readings->count(),
            'data'   => $readings,
        ]);
    }
}
