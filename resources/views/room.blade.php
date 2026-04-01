<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Casivo Dashboard - {{ $room->name }}">
    <title>Casivo - {{ $room->name }}</title>
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
                <a href="{{ route('dashboard') }}" class="logo" style="text-decoration: none; color: white;">
                    <i data-lucide="chevron-left"></i>
                </a>
                <div class="header-title">
                    <h1>{{ $room->name }}</h1>
                </div>
            </div>  

            <div class="header-right">
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

        <!-- ESP32 Device Info (Mini version) -->
        <div class="esp-device-bar" id="espDeviceBar" style="padding: 10px 20px; border-radius: var(--radius-lg);">
            <div class="esp-device-info">
                <div class="esp-device-icon" style="width:30px; height:30px;">
                    <i data-lucide="cpu" style="width:16px; height:16px;"></i>
                </div>
                <div class="esp-device-meta" style="margin-top:0;">
                    <span id="espLastUpdate" style="font-size:10px;">Checking system...</span>
                </div>
            </div>
            <div class="esp-device-actions">
                <span id="espUptime" style="font-size:10px; color: var(--text-muted); font-weight: 500;">--</span>
            </div>
        </div>

        <!-- Device Management -->
        <div class="section-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 10px;">
            <h2 style="font-size: 20px; font-weight: 600;">{{ $room->name }} Devices</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button onclick="openModal('addDeviceModal')" class="action-btn" style="background: rgba(48, 209, 88, 0.15); color: var(--accent-green); border: 1px solid rgba(48, 209, 88, 0.3); padding: 8px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s;"><i data-lucide="plus" style="width: 16px; height: 16px;"></i> Tambah Device</button>
                <button onclick="openModal('removeDeviceModal')" class="action-btn" style="background: rgba(255, 69, 58, 0.15); color: var(--accent-red); border: 1px solid rgba(255, 69, 58, 0.3); padding: 8px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s;"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> Hapus Device</button>
            </div>
        </div>

        <!-- Devices Grid -->
        <div class="devices-grid">
            @if($devices->count() == 0)
                <div class="device-card" style="grid-column: 1 / -1; min-height: 100px; justify-content: center; align-items: center;">
                    <p style="color: var(--text-muted);">Belum ada perangkat di ruangan ini.</p>
                </div>
            @endif
            @foreach($devices as $device)
            <div class="device-card {{ $device->is_on ? 'active' : '' }} {{ in_array($device->type, ['ac', 'fan']) ? 'span-2' : '' }}" 
                 id="device-{{ $device->id }}"
                 data-id="{{ $device->id }}"
                 data-type="{{ $device->type }}"
                 data-color="{{ $device->color }}"
                 style="{{ $device->is_on ? '--device-color: ' . $device->color : '--device-color: #ffffff' }}"
                 onclick="const cb = this.querySelector('input[type=\'checkbox\']'); if(cb) { cb.checked = !cb.checked; toggleDevice({{ $device->id }}, cb); }">
                
                <div class="device-card-top">
                    <div class="device-icon-wrapper" 
                         style="background: {{ $device->is_on ? $device->color . '25' : 'rgba(255,255,255,0.06)' }};">
                        <div class="glow" style="background: {{ $device->color }};"></div>
                        <span class="{{ $device->is_on && $device->type === 'fan' ? 'fan-spin speed-' . ($device->speed ?? 3) : '' }}"
                              style="color: {{ $device->is_on ? $device->color : 'var(--text-muted)' }}; transition: color 0.3s ease; display: flex; align-items: center; justify-content: center;">
                            @switch($device->icon)
                                @case('lightbulb') <i data-lucide="lightbulb"></i> @break
                                @case('fan') <i data-lucide="fan"></i> @break
                                @case('tv') <i data-lucide="monitor"></i> @break
                                @case('snowflake') <i data-lucide="snowflake"></i> @break
                                @case('lamp') <i data-lucide="lamp-desk"></i> @break
                                @case('speaker') <i data-lucide="volume-2"></i> @break
                                @case('flame') <i data-lucide="flame"></i> @break
                                @default <i data-lucide="plug"></i>
                            @endswitch
                        </span>
                    </div>

                    <label class="toggle-switch" onclick="event.stopPropagation();">
                        <input type="checkbox" 
                               aria-label="Toggle Status {{ $device->name }}"
                               {{ $device->is_on ? 'checked' : '' }}
                               onchange="toggleDevice({{ $device->id }}, this)"
                               style="--toggle-color: {{ $device->color }};">
                        <span class="toggle-slider" style="{{ $device->is_on ? 'background: ' . $device->color . '; box-shadow: 0 0 15px ' . $device->color . '40;' : '' }}"></span>
                    </label>
                </div>

                <div class="device-info" style="margin-top: auto; position: relative; z-index: 1;">
                    <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 4px; letter-spacing: -0.2px;">{{ $device->name }}</h3>
                    <div class="device-status {{ $device->is_on ? 'on' : 'off' }}" id="status-{{ $device->id }}">
                        <span class="device-status-dot"></span>
                        <span class="status-text">{{ $device->is_on ? 'Menyala' : 'Mati' }}</span>
                        @if($device->type === 'ac' && $device->is_on)
                            <span style="margin-left: 5px;">• {{ $device->temperature_setting }}°C</span>
                        @endif
                        @if($device->type === 'fan' && $device->is_on)
                            <span style="margin-left: 5px;">• Kecepatan {{ $device->speed }}</span>
                        @endif
                        @if($device->type === 'light' && $device->is_on)
                            <span style="margin-left: 5px;">• {{ $device->brightness }}%</span>
                        @endif
                    </div>
                </div>

                @if($device->type === 'light')
                <div class="device-control">
                    <div class="control-label">Kecerahan</div>
                    <input type="range" class="slider-control" min="0" max="100" 
                           aria-label="Atur Kecerahan {{ $device->name }}"
                           value="{{ $device->brightness ?? 50 }}"
                           onchange="updateDevice({{ $device->id }}, 'brightness', this.value)"
                           onclick="event.stopPropagation();"
                           style="accent-color: {{ $device->color }};">
                    <div class="slider-value"><span id="brightness-{{ $device->id }}">{{ $device->brightness ?? 50 }}</span>%</div>
                </div>
                @endif

                @if($device->type === 'fan')
                <div class="device-control">
                    <div class="control-label">Kecepatan</div>
                    <input type="range" class="slider-control" min="1" max="5" 
                           aria-label="Atur Kecepatan {{ $device->name }}"
                           value="{{ $device->speed ?? 3 }}"
                           onchange="updateDevice({{ $device->id }}, 'speed', this.value)"
                           onclick="event.stopPropagation();"
                           style="accent-color: {{ $device->color }};">
                    <div class="slider-value">Level <span id="speed-{{ $device->id }}">{{ $device->speed ?? 3 }}</span></div>
                </div>
                @endif

                @if($device->type === 'ac')
                <div class="device-control">
                    <div class="control-label">Suhu (°C)</div>
                    <input type="range" class="slider-control" min="16" max="30" 
                           aria-label="Atur Suhu {{ $device->name }}"
                           value="{{ $device->temperature_setting ?? 24 }}"
                           onchange="updateDevice({{ $device->id }}, 'temperature_setting', this.value)"
                           onclick="event.stopPropagation();"
                           style="accent-color: {{ $device->color }};">
                    <div class="slider-value"><span id="temp-{{ $device->id }}">{{ $device->temperature_setting ?? 24 }}</span>°C</div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Add Device Modal -->
        <div id="addDeviceModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Tambah Perangkat Baru</h2>
                    <button class="close-btn" onclick="closeModal('addDeviceModal')"><i data-lucide="x"></i></button>
                </div>
                <form id="addDeviceForm" onsubmit="addDevice(event)">
                    <div class="form-group">
                        <label>Nama Perangkat</label>
                        <input type="text" id="addDeviceName" required placeholder="Ex. Lampu Kamar">
                    </div>
                    <div class="form-group">
                        <label>Tipe</label>
                        <select id="addDeviceType" required>
                            <option value="light">Lampu</option>
                            <option value="fan">Kipas Angin</option>
                            <option value="ac">AC</option>
                            <option value="smart_plug">Smart Plug</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ruangan</label>
                        <select id="addDeviceRoom" required>
                            <option value="{{ $room->name }}" selected>{{ $room->name }}</option>
                        </select>
                    </div>
                    <div class="form-group custom-select-wrapper" style="position: relative;">
                        <label>Ikon</label>
                        <input type="hidden" id="addDeviceIcon" value="lightbulb">
                        <div class="custom-select-trigger" onclick="toggleIconSelect()" style="width: 100%; padding: 10px 14px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; color: var(--text-primary); font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: border-color 0.3s;">
                            <span id="selectedIconDisplay" style="display: flex; align-items: center; gap: 8px;"><i data-lucide="lightbulb" style="width:16px;height:16px;"></i> Lightbulb</span>
                            <i data-lucide="chevron-down" style="width:16px;height:16px;color:var(--text-muted)"></i>
                        </div>
                        <div class="custom-select-options" id="iconSelectOptions" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #1c1c1e; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; z-index: 99; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                            <div class="custom-option" onclick="pickIcon('lightbulb', 'Lightbulb')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="lightbulb" style="width:16px;height:16px;"></i> Lightbulb</div>
                            <div class="custom-option" onclick="pickIcon('fan', 'Fan')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="fan" style="width:16px;height:16px;"></i> Fan</div>
                            <div class="custom-option" onclick="pickIcon('snowflake', 'Snowflake')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="snowflake" style="width:16px;height:16px;"></i> Snowflake</div>
                            <div class="custom-option" onclick="pickIcon('tv', 'TV Monitor')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="monitor" style="width:16px;height:16px;"></i> TV Monitor</div>
                            <div class="custom-option" onclick="pickIcon('lamp', 'Desk Lamp')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="lamp-desk" style="width:16px;height:16px;"></i> Desk Lamp</div>
                            <div class="custom-option" onclick="pickIcon('speaker', 'Speaker')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="volume-2" style="width:16px;height:16px;"></i> Speaker</div>
                            <div class="custom-option" onclick="pickIcon('plug', 'Smart Plug')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="plug" style="width:16px;height:16px;"></i> Smart Plug</div>
                            <div class="custom-option" onclick="pickIcon('flame', 'Flame')" style="padding: 10px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: background 0.2s;"><i data-lucide="flame" style="width:16px;height:16px;"></i> Flame</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Warna Tema (Hex Code)</label>
                        <input type="color" id="addDeviceColor" value="#0A84FF" style="width: 100%; height: 40px; padding: 0; border: none; border-radius: 8px; cursor: pointer;">
                    </div>
                    <div class="modal-actions" style="margin-top: 20px;">
                        <button type="submit" class="action-btn" style="width: 100%; background: var(--accent-blue); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer;">Simpan Perangkat</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Remove Device Modal -->
        <div id="removeDeviceModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Hapus Perangkat di {{ $room->name }}</h2>
                    <button class="close-btn" onclick="closeModal('removeDeviceModal')"><i data-lucide="x"></i></button>
                </div>
                <div class="device-list-container">
                    @if($devices->count() == 0)
                        <p style="text-align: center; color: var(--text-muted); padding: 20px 0;">Tidak ada perangkat.</p>
                    @endif
                    @foreach($devices as $dev)
                        <div class="device-list-item" id="del-item-{{ $dev->id }}">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600; font-size: 14px;">{{ $dev->name }}</span>
                                <span style="font-size: 11px; color: var(--text-muted);">{{ $dev->type }}</span>
                            </div>
                            <button onclick="removeDevice({{ $dev->id }})" class="action-btn" style="background: rgba(255, 69, 58, 0.15); color: var(--accent-red); border: none; padding: 8px; border-radius: 8px; cursor: pointer;"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}?v={{ time() }}"></script>
</body>
</html>
