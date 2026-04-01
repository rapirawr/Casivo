<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            // Living Room
            [
                'name' => 'Main Light',
                'type' => 'light',
                'icon' => 'lightbulb',
                'room' => 'Living Room',
                'is_on' => true,
                'brightness' => 80,
                'color' => '#f59e0b',
            ],
            [
                'name' => 'Ceiling Fan',
                'type' => 'fan',
                'icon' => 'fan',
                'room' => 'Living Room',
                'is_on' => true,
                'speed' => 3,
                'color' => '#06b6d4',
            ],
            [
                'name' => 'Smart TV',
                'type' => 'tv',
                'icon' => 'tv',
                'room' => 'Living Room',
                'is_on' => false,
                'color' => '#8b5cf6',
            ],
            [
                'name' => 'Air Conditioner',
                'type' => 'ac',
                'icon' => 'snowflake',
                'room' => 'Living Room',
                'is_on' => true,
                'temperature_setting' => 24,
                'color' => '#3b82f6',
            ],

            // Bedroom
            [
                'name' => 'Bedside Lamp',
                'type' => 'light',
                'icon' => 'lamp',
                'room' => 'Bedroom',
                'is_on' => false,
                'brightness' => 40,
                'color' => '#f97316',
            ],
            [
                'name' => 'Bedroom Fan',
                'type' => 'fan',
                'icon' => 'fan',
                'room' => 'Bedroom',
                'is_on' => false,
                'speed' => 2,
                'color' => '#06b6d4',
            ],
            [
                'name' => 'Bedroom AC',
                'type' => 'ac',
                'icon' => 'snowflake',
                'room' => 'Bedroom',
                'is_on' => false,
                'temperature_setting' => 22,
                'color' => '#3b82f6',
            ],
            [
                'name' => 'Smart Speaker',
                'type' => 'speaker',
                'icon' => 'speaker',
                'room' => 'Bedroom',
                'is_on' => false,
                'color' => '#10b981',
            ],

            // Kitchen
            [
                'name' => 'Kitchen Light',
                'type' => 'light',
                'icon' => 'lightbulb',
                'room' => 'Kitchen',
                'is_on' => true,
                'brightness' => 100,
                'color' => '#f59e0b',
            ],
            [
                'name' => 'Exhaust Fan',
                'type' => 'fan',
                'icon' => 'fan',
                'room' => 'Kitchen',
                'is_on' => false,
                'speed' => 1,
                'color' => '#06b6d4',
            ],

            // Bathroom
            [
                'name' => 'Bathroom Light',
                'type' => 'light',
                'icon' => 'lightbulb',
                'room' => 'Bathroom',
                'is_on' => false,
                'brightness' => 60,
                'color' => '#f59e0b',
            ],
            [
                'name' => 'Water Heater',
                'type' => 'heater',
                'icon' => 'flame',
                'room' => 'Bathroom',
                'is_on' => false,
                'color' => '#ef4444',
            ],
        ];

        foreach ($devices as $device) {
            Device::create($device);
        }
    }
}
