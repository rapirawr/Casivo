<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    /**
     * Display the dashboard with all rooms.
     */
    public function index()
    {
        $sensorInfo = $this->getSensorInfo();
        $sensorData = $sensorInfo['sensorData'];
        $espDevice = $sensorInfo['espDevice'];
        $availableRooms = \App\Models\Room::all();

        return view('dashboard', compact('sensorData', 'espDevice', 'availableRooms'));
    }

    /**
     * Display a specific room with its devices.
     */
    public function roomShow($name)
    {
        $room = \App\Models\Room::where('name', $name)->firstOrFail();
        $devices = Device::where('room', $name)->get();
        
        $sensorInfo = $this->getSensorInfo();
        $sensorData = $sensorInfo['sensorData'];
        $espDevice = $sensorInfo['espDevice'];
        $availableRooms = \App\Models\Room::all(); // For modal selects

        return view('room', compact('room', 'devices', 'sensorData', 'espDevice', 'availableRooms'));
    }

    /**
     * Helper to get sensor and ESP device info.
     */
    private function getSensorInfo()
    {
        $latestSensor = Sensor::latest()->first();

        if ($latestSensor) {
            $isConnected = $latestSensor->created_at->diffInSeconds(now()) < 30;

            $sensorData = [
                'temperature'  => round($latestSensor->suhu, 1),
                'humidity'     => round($latestSensor->kelembaban, 1),
                'air_quality'  => round($latestSensor->kualitas_udara ?? 0, 0),
                'energy_usage' => round($latestSensor->penggunaan_energi ?? 0, 1),
            ];
            $espDevice = [
                'device_id'      => $latestSensor->device_id,
                'updated_at'     => $latestSensor->created_at->diffForHumans(),
                'source'         => $isConnected ? 'esp32' : 'disconnected',
                'total_readings' => Sensor::where('device_id', $latestSensor->device_id)->count(),
                'wifi_rssi'      => $latestSensor->wifi_rssi ?? 0,
                'internal_temp'  => $latestSensor->internal_temp ?? 0,
                'uptime'         => $latestSensor->uptime ?? 0,
                'free_ram'       => $latestSensor->free_ram ?? 0,
                'wifi_ssid'      => $latestSensor->wifi_ssid ?? 'N/A',
            ];
        } else {
            $sensorData = [
                'temperature'  => 0,
                'humidity'     => 0,
                'air_quality'  => 0,
                'energy_usage' => 0,
            ];
            $espDevice = [
                'device_id'      => null,
                'updated_at'     => null,
                'source'         => 'disconnected',
                'total_readings' => 0,
                'wifi_rssi'      => 0,
                'internal_temp'  => 0,
                'uptime'         => 0,
                'free_ram'       => 0,
                'wifi_ssid'      => 'Disconnected',
            ];
        }

        return compact('sensorData', 'espDevice');
    }

    /**
     * Store a new device.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:light,fan,ac,smart_plug',
            'room' => 'required|string|max:255',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        $device = Device::create(array_merge($validated, [
            'is_on' => false,
            'brightness' => $validated['type'] === 'light' ? 50 : null,
            'speed' => $validated['type'] === 'fan' ? 3 : null,
            'temperature_setting' => $validated['type'] === 'ac' ? 24 : null,
        ]));

        return response()->json([
            'success' => true,
            'device' => $device,
            'message' => 'Device successfully added',
        ]);
    }

    /**
     * Toggle a device on/off.
     */
    public function toggle(Device $device): JsonResponse
    {
        $device->update(['is_on' => !$device->is_on]);

        return response()->json([
            'success' => true,
            'device' => $device->fresh(),
            'message' => $device->name . ' is now ' . ($device->is_on ? 'OFF' : 'ON'),
        ]);
    }

    /**
     * Update device settings (brightness, speed, temperature).
     */
    public function update(Request $request, Device $device): JsonResponse
    {
        $validated = $request->validate([
            'brightness' => 'nullable|integer|min:0|max:100',
            'speed' => 'nullable|integer|min:1|max:5',
            'temperature_setting' => 'nullable|numeric|min:16|max:30',
            'is_on' => 'nullable|boolean',
        ]);

        $device->update($validated);

        return response()->json([
            'success' => true,
            'device' => $device->fresh(),
        ]);
    }

    /**
     * Delete a device.
     */
    public function destroy(Device $device): JsonResponse
    {
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device successfully deleted',
        ]);
    }

    /**
     * Get sensor data (simulated).
     */
    public function sensorData(): JsonResponse
    {
        $latestSensor = Sensor::latest()->first();

        if ($latestSensor) {
            $isConnected = $latestSensor->created_at->diffInSeconds(now()) < 30;

            return response()->json([
                'temperature'  => round($latestSensor->suhu, 1),
                'humidity'     => round($latestSensor->kelembaban, 1),
                'air_quality'  => round($latestSensor->kualitas_udara ?? 0, 0),
                'energy_usage' => round($latestSensor->penggunaan_energi ?? 0, 1),
                'source'       => $isConnected ? 'esp32' : 'disconnected',
                'device_id'    => $latestSensor->device_id,
                'updated_at'   => $latestSensor->created_at->toIso8601String(),
                'wifi_rssi'    => $latestSensor->wifi_rssi ?? 0,
                'internal_temp'=> $latestSensor->internal_temp ?? 0,
                'uptime'       => $latestSensor->uptime ?? 0,
                'free_ram'     => $latestSensor->free_ram ?? 0,
                'wifi_ssid'    => $latestSensor->wifi_ssid ?? 'N/A',
            ]);
        }

        return response()->json([
            'temperature'  => 0,
            'humidity'     => 0,
            'air_quality'  => 0,
            'energy_usage' => 0,
            'source'       => 'disconnected',
            'wifi_rssi'    => 0,
            'internal_temp'=> 0,
            'uptime'       => 0,
            'free_ram'     => 0,
            'wifi_ssid'    => 'Disconnected',
        ]);
    }
}
