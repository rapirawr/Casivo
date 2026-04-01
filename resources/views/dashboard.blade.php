<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Casivo Dashboard - Control your home devices with ease">
    <title>Casivo Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-gradient">
        <div class="bg-blob"></div>
        <div class="bg-blob"></div>
        <div class="bg-blob"></div>
    </div>

    <div class="container" id="app">
        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>

        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div class="logo">
                    <i data-lucide="house"></i>
                </div>
                <div class="header-title">
                    <h1>Casivo</h1>
                </div>
            </div>  

            <div class="header-right">
                <!-- <div class="status-badge">
                    <span class="status-dot"></span>
                    <span>System Online</span>
                </div> -->
                <div class="wifi-widget">
                    <div class="wifi-icon">
                        <i data-lucide="wifi"></i>
                    </div>
                    <div class="wifi-info">
                        <span class="wifi-label" id="wifiLabel">{{ $espDevice['source'] === 'esp32' ? 'Connected to' : 'Disconnected' }}</span>
                        <span class="wifi-name" id="wifiNameDisplay">{{ $espDevice['source'] === 'esp32' ? ($espDevice['wifi_ssid'] ?: 'No WiFi Name') : '-' }}</span>
                    </div>
                </div>

                <div class="clock-widget">
                    <div class="clock-time" id="clock">--:--:--</div>
                    <div class="clock-date" id="clockDate">Loading...</div>
                </div>
            </div>
        </header>

        <!-- ESP32 Device Info -->
        <div class="esp-device-bar" id="espDeviceBar">
            <div class="esp-device-info">
                <div class="esp-device-icon">
                    <i data-lucide="cpu"></i>
                </div>
                <div class="esp-device-details">
                    <div class="esp-device-name">
                        @if($espDevice['source'] === 'esp32')
                            <span class="esp-source-badge connected">
                                <span class="esp-pulse-dot"></span>
                                Connected
                            </span>
                        @else
                            <span class="esp-source-badge not-connected">
                                <span class="esp-pulse-dot"></span>
                                Not Connected
                            </span>
                        @endif
                    </div>
                    <div class="esp-device-meta">
                        @if($espDevice['source'] === 'esp32' && $espDevice['updated_at'])
                            <span id="espLastUpdate"><i data-lucide="clock" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i>Update {{ $espDevice['updated_at'] }}</span>
                            <span class="esp-meta-divider">•</span>
                            <span id="espReadings"><i data-lucide="activity" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i>{{ $espDevice['total_readings'] }} readings</span>
                        @elseif($espDevice['updated_at'])
                            <span id="espLastUpdate"><i data-lucide="clock" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i>Terakhir online {{ $espDevice['updated_at'] }}</span>
                        @else
                            <span id="espLastUpdate">Belum ada data dari ESP32</span>
                        @endif
                    </div>
                    <div class="esp-diagnostics" id="espDiagnostics" style="display: flex; opacity: {{ $espDevice['source'] === 'esp32' ? '1' : '0.5' }}; align-items: center; gap: 8px; margin-top: 6px; font-size: 11px; color: var(--text-muted); font-weight: 500;">
                        <span title="WiFi Signal Strength"><i data-lucide="wifi" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i><span id="espWifiRssi">{{ $espDevice['wifi_rssi'] ?? '--' }}</span> dBm</span>
                        <span class="esp-meta-divider">•</span>
                        <span title="Suhu Internal ESP32"><i data-lucide="cpu" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i><span id="espInternalTemp">{{ $espDevice['internal_temp'] ?? '--' }}</span>°C</span>
                        <span class="esp-meta-divider">•</span>
                        <span title="Uptime"><i data-lucide="timer" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i><span id="espUptime">@php
                            $sec = $espDevice['uptime'] ?? null;
                            if(is_numeric($sec)) {
                                $d = floor($sec / 86400); $h = floor(($sec % 86400) / 3600); $m = floor(($sec % 3600) / 60);
                                $res = [];
                                if($d>0)$res[]="{$d}d"; if($h>0)$res[]="{$h}h"; if($m>0)$res[]="{$m}m";
                                if(empty($res))$res[]="{$sec}s";
                                echo implode(' ', $res);
                            } else { echo '--'; }
                        @endphp</span></span>
                        <span class="esp-meta-divider">•</span>
                        <span title="Sisa RAM"><i data-lucide="memory-stick" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i><span id="espFreeRam">{{ number_format(($espDevice['free_ram'] ?? 0) / 1024, 1) }}</span> KB</span>
                    </div>
                </div>
            </div>
            <div class="esp-device-actions">
                <span class="esp-signal {{ $espDevice['source'] === 'esp32' ? 'online' : 'offline' }}" id="espSignal" title="Data source">
                    <i data-lucide="{{ $espDevice['source'] === 'esp32' ? 'radio' : 'wifi-off' }}"></i>
                    <span id="espSourceLabel">{{ $espDevice['source'] === 'esp32' ? 'Online' : 'Offline' }}</span>
                </span>
            </div>
        </div>

        <!-- Sensor Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon temp">
                        <i data-lucide="thermometer"></i>
                    </div>
                    <span class="stat-trend down" id="tempTrend">Normal</span>
                </div>
                <div class="stat-value">
                    <span id="tempValue">{{ $sensorData['temperature'] }}</span><span class="unit">°C</span>
                </div>
                <div class="stat-label">Suhu Ruangan</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon humidity">
                        <i data-lucide="droplets"></i>
                    </div>
                    <span class="stat-trend up" id="humidityTrend">Baik</span>
                </div>
                <div class="stat-value">
                    <span id="humidityValue">{{ $sensorData['humidity'] }}</span><span class="unit">%</span>
                </div>
                <div class="stat-label">Kelembaban</div>
            </div>

            <!--
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon air">
                        <i data-lucide="wind"></i>
                    </div>
                    <span class="stat-trend up" id="airTrend">Baik</span>
                </div>
                <div class="stat-value">
                    <span id="airValue">{{ $sensorData['air_quality'] }}</span><span class="unit">AQI</span>
                </div>
                <div class="stat-label">Kualitas Udara</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon energy">
                        <i data-lucide="zap"></i>
                    </div>
                    <span class="stat-trend down" id="energyTrend">Hemat</span>
                </div>
                <div class="stat-value">
                    <span id="energyValue">{{ $sensorData['energy_usage'] }}</span><span class="unit">kWh</span>
                </div>
                <div class="stat-label">Penggunaan Energi</div>
            </div>
            -->
        </div>

        <!-- Room Navigation -->
        <div class="section-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 10px;">
            <h2 style="font-size: 20px; font-weight: 600;">Control Center</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button onclick="openModal('addRoomModal')" class="action-btn" style="background: rgba(10, 132, 255, 0.15); color: var(--accent-blue); border: 1px solid rgba(10, 132, 255, 0.3); padding: 8px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s;"><i data-lucide="layout-grid" style="width: 16px; height: 16px;"></i> Tambah Ruangan</button>
            </div>
        </div>

        <div class="devices-grid">
            @foreach($availableRooms as $rm)
            <a href="{{ route('rooms.show', $rm->name) }}" class="device-card" style="text-decoration: none; color: inherit;">
                <div class="device-card-top">
                    <div class="device-icon-wrapper" style="background: rgba(255,255,255,0.06);">
                        <i data-lucide="{{ $rm->icon ?: 'home' }}"></i>
                    </div>
                    <div style="background: rgba(48, 209, 88, 0.15); color: var(--accent-green); padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 600;">
                        Open Room
                    </div>
                </div>
                <div class="device-info" style="margin-top: auto; position: relative; z-index: 1;">
                    <h3 style="font-size: 17px; font-weight: 600; margin-bottom: 4px; letter-spacing: -0.2px;">{{ $rm->name }}</h3>
                    <div class="device-status off">
                        <span class="device-status-dot"></span>
                        <span class="status-text">Smart Room</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>



        <!-- Add Room Modal -->
        <div id="addRoomModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Tambah Ruangan Baru</h2>
                    <button class="close-btn" onclick="closeModal('addRoomModal')"><i data-lucide="x"></i></button>
                </div>
                <form id="addRoomForm" onsubmit="addRoom(event)">
                    <div class="form-group">
                        <label>Nama Ruangan</label>
                        <input type="text" id="addRoomName" required placeholder="Ex. Living Room">
                    </div>
                    <div class="form-group custom-select-wrapper" style="position: relative;">
                        <label>Ikon Ruangan</label>
                        <input type="hidden" id="addRoomIcon" value="home">
                        <div class="custom-select-trigger" onclick="toggleIconSelectRoom()" style="width: 100%; padding: 10px 14px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; color: var(--text-primary); font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: border-color 0.3s;">
                            <span id="selectedRoomIconDisplay" style="display: flex; align-items: center; gap: 8px;"><i data-lucide="home" style="width:16px;height:16px;"></i> Home</span>
                            <i data-lucide="chevron-down" style="width:16px;height:16px;color:var(--text-muted)"></i>
                        </div>
                        <div class="custom-select-options" id="roomIconSelectOptions" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #1c1c1e; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; z-index: 99; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                            <div class="custom-option" onclick="pickRoomIcon('home', 'Home')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="home" style="width:16px;height:16px;"></i> Home</div>
                            <div class="custom-option" onclick="pickRoomIcon('sofa', 'Living Room')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="sofa" style="width:16px;height:16px;"></i> Living Room</div>
                            <div class="custom-option" onclick="pickRoomIcon('bed-double', 'Bedroom')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="bed-double" style="width:16px;height:16px;"></i> Bedroom</div>
                            <div class="custom-option" onclick="pickRoomIcon('cooking-pot', 'Kitchen')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="cooking-pot" style="width:16px;height:16px;"></i> Kitchen</div>
                            <div class="custom-option" onclick="pickRoomIcon('shower-head', 'Bathroom')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="shower-head" style="width:16px;height:16px;"></i> Bathroom</div>
                            <div class="custom-option" onclick="pickRoomIcon('lamp-desk', 'Study Room')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="lamp-desk" style="width:16px;height:16px;"></i> Study Room</div>
                            <div class="custom-option" onclick="pickRoomIcon('car-front', 'Garage')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="car-front" style="width:16px;height:16px;"></i> Garage</div>
                            <div class="custom-option" onclick="pickRoomIcon('trees', 'Garden')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="trees" style="width:16px;height:16px;"></i> Garden</div>
                        </div>
                    </div>
                    <div class="modal-actions" style="margin-top: 20px;">
                        <button type="submit" class="action-btn" style="width: 100%; background: var(--accent-blue); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer;">Simpan Ruangan</button>
                    </div>
                </form>
            </div>
        </div>


    </div>

    <script src="{{ asset('js/dashboard.js') }}?v={{ time() }}"></script>
</body>
</html>
