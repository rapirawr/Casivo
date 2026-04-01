<!DOCTYPE html>
<html>
<head>
    <title>Casivo - BLE Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-6">

    <h1 class="text-3xl font-bold mb-2">💡 Casivo BLE Control</h1>
    <p id="status" class="text-gray-400 mb-8">Belum terhubung</p>

    <button onclick="connectBLE()"
        class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-xl font-semibold mb-10">
        🔵 Connect ke CasivoHome
    </button>

    <!-- LED 19 -->
    <div class="bg-gray-800 rounded-2xl p-6 w-full max-w-sm mb-4">
        <h2 class="text-lg font-semibold mb-4">💡 LED 1 (GPIO 19)</h2>
        <div class="flex gap-4">
            <button onclick="sendCommand('LED19_ON')"
                class="flex-1 bg-green-600 hover:bg-green-700 py-3 rounded-xl font-bold">ON</button>
            <button onclick="sendCommand('LED19_OFF')"
                class="flex-1 bg-red-600 hover:bg-red-700 py-3 rounded-xl font-bold">OFF</button>
        </div>
    </div>

    <!-- LED 22 -->
    <div class="bg-gray-800 rounded-2xl p-6 w-full max-w-sm mb-4">
        <h2 class="text-lg font-semibold mb-4">💡 LED 2 (GPIO 22)</h2>
        <div class="flex gap-4">
            <button onclick="sendCommand('LED22_ON')"
                class="flex-1 bg-green-600 hover:bg-green-700 py-3 rounded-xl font-bold">ON</button>
            <button onclick="sendCommand('LED22_OFF')"
                class="flex-1 bg-red-600 hover:bg-red-700 py-3 rounded-xl font-bold">OFF</button>
        </div>
    </div>

    <!-- Semua LED -->
    <div class="bg-gray-800 rounded-2xl p-6 w-full max-w-sm mb-4">
        <h2 class="text-lg font-semibold mb-4">⚡ Semua LED</h2>
        <div class="flex gap-4">
            <button onclick="sendCommand('ALL_ON')"
                class="flex-1 bg-yellow-500 hover:bg-yellow-600 py-3 rounded-xl font-bold text-gray-900">Semua ON</button>
            <button onclick="sendCommand('ALL_OFF')"
                class="flex-1 bg-gray-600 hover:bg-gray-700 py-3 rounded-xl font-bold">Semua OFF</button>
        </div>
    </div>

    <!-- Buzzer -->
    <div class="bg-gray-800 rounded-2xl p-6 w-full max-w-sm mb-4">
        <h2 class="text-lg font-semibold mb-4">🔔 Buzzer (GPIO 4)</h2>
        <div class="flex gap-4 mb-3">
            <button onclick="sendCommand('BUZZER_ON')"
                class="flex-1 bg-green-600 hover:bg-green-700 py-3 rounded-xl font-bold">ON</button>
            <button onclick="sendCommand('BUZZER_OFF')"
                class="flex-1 bg-red-600 hover:bg-red-700 py-3 rounded-xl font-bold">OFF</button>
        </div>
        <button onclick="sendCommand('BUZZER_ALARM')"
            class="w-full bg-orange-500 hover:bg-orange-600 py-3 rounded-xl font-bold">
            🚨 Alarm
        </button>
    </div>

    <script>
        let characteristic = null;

        async function connectBLE() {
            try {
                document.getElementById('status').innerText = '🔍 Mencari ESP32...';
                const device = await navigator.bluetooth.requestDevice({
                    filters: [{ name: 'CasivoHome' }],
                    optionalServices: ['6e400001-b5a3-f393-e0a9-e50e24dcca9e']
                });
                device.addEventListener('gattserverdisconnected', () => {
                    document.getElementById('status').innerText = '❌ Terputus';
                    characteristic = null;
                });
                const server  = await device.gatt.connect();
                const service = await server.getPrimaryService('6e400001-b5a3-f393-e0a9-e50e24dcca9e');
                characteristic = await service.getCharacteristic('6e400002-b5a3-f393-e0a9-e50e24dcca9e');
                document.getElementById('status').innerText = '✅ Terhubung ke ' + device.name;
            } catch(e) {
                document.getElementById('status').innerText = '❌ Gagal: ' + e.message;
            }
        }

        async function sendCommand(cmd) {
            if (!characteristic) {
                alert('Konek dulu ke ESP32!');
                return;
            }
            try {
                const encoder = new TextEncoder();
                await characteristic.writeValue(encoder.encode(cmd));
                document.getElementById('status').innerText = '✅ Terkirim: ' + cmd;
            } catch(e) {
                document.getElementById('status').innerText = '❌ Gagal: ' + e.message;
            }
        }
    </script>

</body>
</html>