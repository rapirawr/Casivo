// Initialize Lucide Icons
lucide.createIcons();

// =============================================
// Real-Time Clock
// =============================================
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    document.getElementById('clock').innerHTML = 
        `${hours}:${minutes}:<span class="clock-seconds">${seconds}</span>`;

    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const dayName = days[now.getDay()];
    const date = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear();

    document.getElementById('clockDate').textContent = `${dayName}, ${date} ${month} ${year}`;
}

setInterval(updateClock, 1000);
updateClock();

// =============================================
// CSRF Token
// =============================================
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// =============================================
// Spam Handler - Cooldown per device
// =============================================
const deviceCooldowns = new Map();
const COOLDOWN_MS = 2000; // 2 detik cooldown per device

function isSpamming(deviceId) {
    const now = Date.now();
    const lastAction = deviceCooldowns.get(deviceId);

    if (lastAction && (now - lastAction) < COOLDOWN_MS) {
        const remaining = Math.ceil((COOLDOWN_MS - (now - lastAction)) / 1000);
        showToast('warning', 'timer', `Tunggu ${remaining} detik sebelum mengubah lagi`);
        return true;
    }

    deviceCooldowns.set(deviceId, now);
    return false;
}

// =============================================
// Toggle Device (Optimistic UI)
// =============================================
async function toggleDevice(deviceId, checkbox) {
    // Spam check
    if (isSpamming(deviceId)) {
        checkbox.checked = !checkbox.checked; // revert checkbox
        return;
    }

    // Haptic feedback (getaran)
    if (navigator.vibrate) navigator.vibrate(40);

    const card = document.getElementById(`device-${deviceId}`);
    const isOn = checkbox.checked;
    const deviceColor = card.dataset.color;
    const deviceType = card.dataset.type;
    const deviceName = card.querySelector('.device-info h3')?.textContent || 'Device';

    // Optimistic: update UI immediately
    const optimisticDevice = {
        id: deviceId,
        is_on: isOn,
        color: deviceColor,
        type: deviceType,
        name: deviceName,
        speed: card.querySelector('[id^="speed-"]')?.textContent || 3,
        brightness: card.querySelector('[id^="brightness-"]')?.textContent || 50,
        temperature_setting: card.querySelector('[id^="temp-"]')?.textContent || 24,
    };
    updateDeviceUI(card, optimisticDevice);
    showToast(
        isOn ? 'success' : 'info',
        isOn ? 'check-circle' : 'power',
        `${deviceName} ${isOn ? 'menyala' : 'dimatikan'}`
    );

    // Send request in background
    try {
        const response = await fetch(`/devices/${deviceId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            // Sync with actual server state (in case of mismatch)
            updateDeviceUI(card, data.device);
        } else {
            // Revert on failure
            checkbox.checked = !isOn;
            optimisticDevice.is_on = !isOn;
            updateDeviceUI(card, optimisticDevice);
            showToast('error', 'alert-circle', 'Gagal mengubah status perangkat');
        }
    } catch (error) {
        // Revert on network error
        checkbox.checked = !isOn;
        optimisticDevice.is_on = !isOn;
        updateDeviceUI(card, optimisticDevice);
        showToast('error', 'alert-circle', 'Gagal mengubah status perangkat');
        console.error('Toggle error:', error);
    }
}

// =============================================
// Update Device Settings (Optimistic UI)
// =============================================
async function updateDevice(deviceId, field, value) {
    // Spam check for settings updates
    const throttleKey = `${deviceId}-${field}`;
    if (isSpamming(throttleKey)) {
        return;
    }

    // Haptic feedback (getaran)
    if (navigator.vibrate) navigator.vibrate(20);

    const card = document.getElementById(`device-${deviceId}`);

    // Optimistic: update display immediately
    const displayEl = document.getElementById(
        field === 'brightness' ? `brightness-${deviceId}` :
        field === 'speed' ? `speed-${deviceId}` :
        `temp-${deviceId}`
    );
    const previousValue = displayEl ? displayEl.textContent : value;
    if (displayEl) displayEl.textContent = value;

    // Update status text immediately
    const statusEl = document.getElementById(`status-${deviceId}`);
    if (statusEl) {
        const extraSpans = statusEl.querySelectorAll('span[style*="margin-left"]');
        extraSpans.forEach(s => {
            if (field === 'brightness') s.textContent = `• ${value}%`;
            if (field === 'speed') s.textContent = `• Kecepatan ${value}`;
            if (field === 'temperature_setting') s.textContent = `• ${value}°C`;
        });
    }

    // Update fan speed animation immediately
    if (field === 'speed') {
        const fanIcon = card.querySelector('.fan-spin, [class*="fan-spin"]');
        if (fanIcon) {
            fanIcon.className = `fan-spin speed-${value}`;
        }
    }

    // Send request in background
    try {
        const body = {};
        body[field] = Number(value);

        const response = await fetch(`/devices/${deviceId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });

        const data = await response.json();

        if (!data.success) {
            // Revert on failure
            if (displayEl) displayEl.textContent = previousValue;
            showToast('error', 'alert-circle', 'Gagal memperbarui pengaturan');
        }
    } catch (error) {
        // Revert on network error
        if (displayEl) displayEl.textContent = previousValue;
        showToast('error', 'alert-circle', 'Gagal memperbarui pengaturan');
        console.error('Update error:', error);
    }
}

// =============================================
// Update Device UI After Toggle
// =============================================
function updateDeviceUI(card, device) {
    const isOn = device.is_on;

    card.classList.toggle('active', isOn);

    const iconWrapper = card.querySelector('.device-icon-wrapper');
    if (iconWrapper) {
        iconWrapper.style.background = isOn ? device.color + '25' : 'rgba(255,255,255,0.06)';
    }

    const iconSpan = iconWrapper?.querySelector('span');
    if (iconSpan) {
        iconSpan.style.color = isOn ? device.color : 'var(--text-muted)';

        if (device.type === 'fan') {
            if (isOn) {
                iconSpan.classList.add('fan-spin', `speed-${device.speed || 3}`);
            } else {
                iconSpan.className = '';
                iconSpan.style.display = 'flex';
                iconSpan.style.alignItems = 'center';
                iconSpan.style.justifyContent = 'center';
            }
        }
    }

    const slider = card.querySelector('.toggle-slider');
    if (slider) {
        slider.style.background = isOn ? device.color : '';
        slider.style.boxShadow = isOn ? `0 0 15px ${device.color}40` : '';
    }

    const statusEl = document.getElementById(`status-${device.id}`);
    if (statusEl) {
        statusEl.className = `device-status ${isOn ? 'on' : 'off'}`;
        let statusHTML = `<span class="device-status-dot"></span><span class="status-text">${isOn ? 'Menyala' : 'Mati'}</span>`;
        if (isOn) {
            if (device.type === 'ac') statusHTML += `<span style="margin-left: 5px;">• ${device.temperature_setting}°C</span>`;
            if (device.type === 'fan') statusHTML += `<span style="margin-left: 5px;">• Kecepatan ${device.speed}</span>`;
            if (device.type === 'light') statusHTML += `<span style="margin-left: 5px;">• ${device.brightness}%</span>`;
        }
        statusEl.innerHTML = statusHTML;
    }

    updateRoomCounts();
}

// =============================================
// Update Room Active Counts
// =============================================
function updateRoomCounts() {
    document.querySelectorAll('.room-section').forEach(section => {
        const cards = section.querySelectorAll('.device-card');
        const activeCards = section.querySelectorAll('.device-card.active');
        const countEl = section.querySelector('.room-count');
        if (countEl) {
            countEl.textContent = `${activeCards.length}/${cards.length} aktif`;
        }
    });
}

// =============================================
// Toast Notification (Max 4 visible)
// =============================================
const MAX_TOASTS = 4;

function showToast(type, iconName, message) {
    const container = document.getElementById('toastContainer');

    // Enforce max toast limit — remove oldest if at capacity
    const existingToasts = container.querySelectorAll('.toast:not(.leaving)');
    if (existingToasts.length >= MAX_TOASTS) {
        const oldest = existingToasts[0];
        oldest.classList.add('leaving');
        setTimeout(() => oldest.remove(), 300);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
        <div class="toast-icon ${type}">
            <i data-lucide="${iconName}"></i>
        </div>
        <span class="toast-text">${message}</span>
    `;
    container.appendChild(toast);
    
    // Re-initialize lucide for new icons
    lucide.createIcons();

    setTimeout(() => {
        toast.classList.add('leaving');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// =============================================
// Sensor Data Polling
// =============================================
async function fetchSensorData() {
    try {
        const response = await fetch('/api/sensors');
        const data = await response.json();

        animateValue('tempValue', data.temperature);
        animateValue('humidityValue', data.humidity);
        animateValue('airValue', data.air_quality);
        animateValue('energyValue', data.energy_usage);

        const tempTrend = document.getElementById('tempTrend');
        if (data.temperature >= 35) {
            tempTrend.className = 'stat-trend danger';
            tempTrend.textContent = 'Sangat Panas';
        } else if (data.temperature >= 30) {
            tempTrend.className = 'stat-trend up';
            tempTrend.textContent = 'Panas';
        } else if (data.temperature >= 26) {
            tempTrend.className = 'stat-trend normal';
            tempTrend.textContent = 'Normal';
        } else if (data.temperature >= 20) {
            tempTrend.className = 'stat-trend down';
            tempTrend.textContent = 'Sejuk';
        } else {
            tempTrend.className = 'stat-trend cold';
            tempTrend.textContent = 'Dingin';
        }

        const humidityTrend = document.getElementById('humidityTrend');
        if (data.humidity >= 80) {
            humidityTrend.className = 'stat-trend danger';
            humidityTrend.textContent = 'Sangat Lembab';
        } else if (data.humidity >= 65) {
            humidityTrend.className = 'stat-trend up';
            humidityTrend.textContent = 'Lembab';
        } else if (data.humidity >= 40) {
            humidityTrend.className = 'stat-trend normal';
            humidityTrend.textContent = 'Normal';
        } else if (data.humidity >= 20) {
            humidityTrend.className = 'stat-trend down';
            humidityTrend.textContent = 'Kering';
        } else {
            humidityTrend.className = 'stat-trend cold';
            humidityTrend.textContent = 'Sangat Kering';
        }

        // Update ESP32 device info bar
        updateEspDeviceBar(data);

    } catch (error) {
        console.error('Sensor fetch error:', error);
    }
}

function formatUptime(sec) {
    if(sec === null || sec === undefined || isNaN(sec)) return '--';
    sec = parseInt(sec);
    if(sec < 0) return '--';
    const d = Math.floor(sec / 86400);
    const h = Math.floor((sec % 86400) / 3600);
    const m = Math.floor((sec % 3600) / 60);
    let res = [];
    if(d>0) res.push(`${d}d`);
    if(h>0) res.push(`${h}h`);
    if(m>0) res.push(`${m}m`);
    if(res.length === 0) res.push(`${sec}s`);
    return res.join(' ');
}

function updateEspDeviceBar(data) {
    const isLive = data.source === 'esp32';
    
    // Update device ID
    const deviceIdEl = document.getElementById('espDeviceId');
    if (deviceIdEl) {
        deviceIdEl.textContent = isLive ? (data.device_id || 'ESP32') : (data.device_id || 'ESP32');
    }

    // Update source badge
    const badgeEl = document.querySelector('.esp-source-badge');
    if (badgeEl) {
        badgeEl.className = `esp-source-badge ${isLive ? 'connected' : 'not-connected'}`;
        const badgeText = badgeEl.childNodes[badgeEl.childNodes.length - 1];
        if (badgeText) {
            badgeText.textContent = isLive ? ' Connected' : ' Not Connected';
        }
    }

    // Update signal badge
    const signalEl = document.getElementById('espSignal');
    if (signalEl) {
        signalEl.className = `esp-signal ${isLive ? 'online' : 'offline'}`;
    }
    const sourceLabelEl = document.getElementById('espSourceLabel');
    if (sourceLabelEl) {
        sourceLabelEl.textContent = isLive ? 'Online' : 'Offline';
    }

    // Update WiFi Name
    const wifiDisplay = document.getElementById('wifiNameDisplay');
    const wifiLabel = document.getElementById('wifiLabel');
    if (wifiDisplay) {
        wifiDisplay.textContent = isLive ? (data.wifi_ssid ? data.wifi_ssid : 'No WiFi Name') : '-';
    }
    if (wifiLabel) {
        wifiLabel.textContent = isLive ? 'Connected to' : 'Disconnected';
    }

    // Update last update time
    const lastUpdateEl = document.getElementById('espLastUpdate');
    if (lastUpdateEl && data.updated_at) {
        const updatedAt = new Date(data.updated_at);
        const now = new Date();
        const diffSec = Math.floor((now - updatedAt) / 1000);
        
        let timeAgo;
        if (diffSec < 10) timeAgo = 'baru saja';
        else if (diffSec < 60) timeAgo = `${diffSec} detik lalu`;
        else if (diffSec < 3600) timeAgo = `${Math.floor(diffSec / 60)} menit lalu`;
        else timeAgo = `${Math.floor(diffSec / 3600)} jam lalu`;
        
        lastUpdateEl.innerHTML = `<i data-lucide="clock" style="width:11px;height:11px;display:inline;vertical-align:-1px;margin-right:3px;"></i>${isLive ? 'Update' : 'Terakhir online'} ${timeAgo}`;
        lucide.createIcons();
    } else if (lastUpdateEl && !isLive) {
        lastUpdateEl.textContent = 'Belum ada data dari ESP32';
    }

    // Update Diagnostics
    const diagEl = document.getElementById('espDiagnostics');
    if (diagEl) {
        diagEl.style.opacity = isLive ? '1' : '0.5';
        if (isLive) {
            const rssiEl = document.getElementById('espWifiRssi');
            if(rssiEl) rssiEl.textContent = data.wifi_rssi || '--';
            
            const tempEl = document.getElementById('espInternalTemp');
            if(tempEl) tempEl.textContent = data.internal_temp || '--';
            
            const uptimeEl = document.getElementById('espUptime');
            if(uptimeEl) uptimeEl.textContent = formatUptime(data.uptime);
            
            const ramEl = document.getElementById('espFreeRam');
            if(ramEl) {
                const ramKb = ((data.free_ram || 0) / 1024).toFixed(1);
                ramEl.textContent = ramKb;
            }
        } else {
            const rssiEl = document.getElementById('espWifiRssi');
            if(rssiEl) rssiEl.textContent = '--';
            
            const tempEl = document.getElementById('espInternalTemp');
            if(tempEl) tempEl.textContent = '--';
            
            const uptimeEl = document.getElementById('espUptime');
            if(uptimeEl) uptimeEl.textContent = '--';
            
            const ramEl = document.getElementById('espFreeRam');
            if(ramEl) ramEl.textContent = '--';
        }
    }
}

function animateValue(elementId, newValue) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.textContent = newValue;
    el.style.transition = 'transform 0.3s ease';
    el.style.transform = 'scale(1.08)';
    setTimeout(() => el.style.transform = 'scale(1)',300);
}

// Gunakan chained setTimeout sebagai pengganti setInterval
// Ini menghindari antrean request menumpuk, dengan interval yang lebih aman (2000ms = 2 detik).
function scheduleNextFetch() {
    setTimeout(async () => {
        await fetchSensorData();
        scheduleNextFetch();
    }, 500);
}
// Mulai siklus pengambilan data
scheduleNextFetch();



// =============================================
// Initialize
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    // Staggered entrance animation
    const cards = document.querySelectorAll('.device-card, .stat-card, .esp-device-bar');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(16px) scale(0.96)';
        card.style.transition = `opacity 0.5s ease ${i * 0.04}s, transform 0.5s cubic-bezier(0.4, 0, 0.2, 1) ${i * 0.04}s`;
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
        }, 50);
    });
});



// =============================================
// Device Management
// =============================================
function openModal(id) {
    const modal = document.getElementById(id);
    if(modal) {
        modal.style.display = 'flex';
        // setTimeout to allow display block to take effect before opacity transition
        setTimeout(() => modal.classList.add('show'), 10);
    }
}

function toggleIconSelect() {
    const opts = document.getElementById('iconSelectOptions');
    if (opts.style.display === 'none') {
        opts.style.display = 'block';
    } else {
        opts.style.display = 'none';
    }
}

function pickIcon(val, text) {
    document.getElementById('addDeviceIcon').value = val;
    document.getElementById('selectedIconDisplay').innerHTML = `<i data-lucide="${val}" style="width:16px;height:16px;"></i> ${text}`;
    document.getElementById('iconSelectOptions').style.display = 'none';
    lucide.createIcons();
}

function toggleIconSelectRoom() {
    const opts = document.getElementById('roomIconSelectOptions');
    if (opts.style.display === 'none') {
        opts.style.display = 'block';
    } else {
        opts.style.display = 'none';
    }
}

function pickRoomIcon(val, text) {
    document.getElementById('addRoomIcon').value = val;
    document.getElementById('selectedRoomIconDisplay').innerHTML = `<i data-lucide="${val}" style="width:16px;height:16px;"></i> ${text}`;
    document.getElementById('roomIconSelectOptions').style.display = 'none';
    lucide.createIcons();
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if(modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }
}

// Close when clicking outside of modal or dropdown
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
    
    // Close dropdown if clicking outside
    const selectWrapper = e.target.closest('.custom-select-wrapper');
    if (!selectWrapper) {
        const dOpts = document.getElementById('iconSelectOptions');
        if (dOpts) dOpts.style.display = 'none';
        const rOpts = document.getElementById('roomIconSelectOptions');
        if (rOpts) rOpts.style.display = 'none';
    }
});

async function addDevice(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.textContent = 'Menyimpan...';
    btn.disabled = true;

    try {
        const payload = {
            name: document.getElementById('addDeviceName').value,
            type: document.getElementById('addDeviceType').value,
            room: document.getElementById('addDeviceRoom').value,
            icon: document.getElementById('addDeviceIcon').value,
            color: document.getElementById('addDeviceColor').value,
        };

        const res = await fetch('/devices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            window.location.reload();
        } else {
            alert('Gagal menambahkan perangkat.');
            btn.textContent = originalText;
            btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

async function addRoom(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.textContent = 'Menyimpan...';
    btn.disabled = true;

    try {
        const payload = {
            name: document.getElementById('addRoomName').value,
            icon: document.getElementById('addRoomIcon').value,
        };

        const res = await fetch('/rooms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            window.location.reload();
        } else {
            const errData = await res.json().catch(() => ({}));
            alert(errData.message || 'Gagal menambahkan ruangan. Pastikan nama belum terpakai.');
            btn.textContent = originalText;
            btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

async function removeDevice(id) {
    if(!confirm('Apakah Anda yakin ingin menghapus perangkat ini?')) return;
    
    // Optimistic UI Removal
    const listItem = document.getElementById('del-item-' + id);
    const gridCard = document.getElementById('device-' + id);
    if(listItem) listItem.style.display = 'none';
    if(gridCard) gridCard.style.display = 'none';

    try {
        const res = await fetch('/devices/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (res.ok) {
            // Force reload to completely reset UI state
            window.location.reload();
        } else {
            alert('Gagal menghapus perangkat.');
            if(listItem) listItem.style.display = 'flex';
            if(gridCard) gridCard.style.display = 'block';
        }
    } catch(err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
        if(listItem) listItem.style.display = 'flex';
        if(gridCard) gridCard.style.display = 'block';
    }
}
