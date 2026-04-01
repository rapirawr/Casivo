"""
============================================
ESP32 Smart Home Sensor - MicroPython
============================================

Pin Configuration:
  - DHT11  → GPIO 23 (with pull-up)
  - Buzzer → GPIO 4  (PWM)
  - LED1   → GPIO 2  (built-in LED)
  - LED    → GPIO 19

Kirim data suhu & kelembaban ke Laravel API
via HTTP POST setiap 10 detik.
"""

import dht
import urequests
import ujson
import network
import time
import gc
import esp32
from machine import Pin, PWM

# ============================================
# KONFIGURASI - SESUAIKAN!
# ============================================

WIFI_SSID     = "RUANG TAMU"
WIFI_PASSWORD  = "ruangtamu123"

SERVER_URL = "http://192.168.1.11:8000/api/sensor" 

DEVICE_ID = "esp32-01"
SEND_INTERVAL = 5

# ============================================
# INISIALISASI PIN
# ============================================

sensor = dht.DHT11(Pin(23, Pin.IN, Pin.PULL_UP))
buzzer = PWM(Pin(4))
led1   = Pin(2, Pin.OUT)  
led    = Pin(19, Pin.OUT)

buzzer.duty(0)

# ============================================
# FUNGSI BUZZER
# ============================================

def beep(freq=1000, duration=100):
    """Bunyikan buzzer dengan frekuensi dan durasi tertentu"""
    buzzer.freq(freq)
    buzzer.duty(512)
    time.sleep_ms(duration)
    buzzer.duty(0)

def beep_success():
    """Double beep pendek = data berhasil dikirim"""
    beep(2000, 50)
    time.sleep_ms(50)
    beep(2500, 50)

def beep_error():
    """Beep panjang rendah = error"""
    beep(500, 300)

def beep_wifi_connected():
    """3 beep naik = WiFi connected"""
    beep(1000, 80)
    time.sleep_ms(50)
    beep(1500, 80)
    time.sleep_ms(50)
    beep(2000, 80)

def beep_startup():
    """Melody startup"""
    beep(1000, 100)
    time.sleep_ms(50)
    beep(1200, 100)
    time.sleep_ms(50)
    beep(1500, 150)

# ============================================
# FUNGSI LED
# ============================================

def blink(pin, times=1, on_ms=200, off_ms=200):
    """Blink LED beberapa kali"""
    for _ in range(times):
        pin.on()
        time.sleep_ms(on_ms)
        pin.off()
        time.sleep_ms(off_ms)

# ============================================
# CONNECT WIFI
# ============================================

def connect_wifi():
    """Sambungkan ke WiFi"""
    wlan = network.WLAN(network.STA_IF)

    wlan.active(False)
    time.sleep(1)
    wlan.active(True)
    time.sleep(1)

    if wlan.isconnected():
        print("[OK] Sudah terhubung ke WiFi")
        print("[OK] IP:", wlan.ifconfig()[0])
        return wlan

    print(f"[...] Menghubungkan ke WiFi: {WIFI_SSID}")

    try:
        wlan.disconnect()
    except:
        pass
    time.sleep(0.5)

    wlan.connect(WIFI_SSID, WIFI_PASSWORD)

    attempts = 0
    while not wlan.isconnected() and attempts < 30:
        led1.on()
        time.sleep_ms(250)
        led1.off()
        time.sleep_ms(250)
        attempts += 1
        print(".", end="")

    print()

    if wlan.isconnected():
        ip = wlan.ifconfig()[0]
        print(f"[OK] WiFi terhubung!")
        print(f"[OK] IP Address: {ip}")
        print(f"[OK] SSID: {WIFI_SSID}")
        led1.on()  # LED ON = connected
        beep_wifi_connected()
        return wlan
    else:
        print("[ERROR] Gagal terhubung ke WiFi!")
        print("[INFO] Cek SSID dan password, lalu restart ESP32")
        led1.off()
        beep_error()
        return None

# ============================================
# BACA SENSOR DHT11
# ============================================

def read_sensor():
    """Baca suhu dan kelembaban dari DHT11"""
    try:
        sensor.measure()
        suhu = sensor.temperature()
        kelembaban = sensor.humidity()

        print(f"[DATA] Suhu Ruangan: {suhu}°C")
        print(f"[DATA] Kelembaban: {kelembaban}%")

        if suhu > 35:
            print("[WARN] Suhu tinggi! Buzzer alert ON!")
            buzzer.freq(2000)
            buzzer.duty(512)  
            led.on()
        elif suhu > 30:
            print("[INFO] Suhu agak panas")
            buzzer.duty(0)    
            led.on()
        else:
            buzzer.duty(0)    
            led.off()

        return suhu, kelembaban

    except Exception as e:
        print(f"[ERROR] Gagal baca sensor: {e}")
        blink(led, 3, 100, 100) 
        return None, None

def get_diagnostics(wlan):
    """Ambil data diagnostik hardware ESP32"""
    rssi = wlan.status('rssi') if wlan and wlan.isconnected() else 0
    
    try:
        raw_temp = esp32.raw_temperature()
        internal_temp = (raw_temp - 32.0) / 1.8
    except:
        internal_temp = 0.0
        
    uptime = time.ticks_ms() // 1000
    free_ram = gc.mem_free()
    
    return {
        "wifi_rssi": rssi,
        "wifi_ssid": WIFI_SSID,
        "internal_temp": round(internal_temp, 1),
        "uptime": uptime,
        "free_ram": free_ram
    }

# ============================================
# KIRIM DATA KE LARAVEL
# ============================================

def send_data(suhu, kelembaban, diagnostics):
    """Kirim data sensor ke Laravel API via HTTP POST"""
    payload = ujson.dumps({
        "device_id": DEVICE_ID,
        "suhu": suhu,
        "kelembaban": kelembaban,
        "wifi_rssi": diagnostics["wifi_rssi"],
        "wifi_ssid": diagnostics["wifi_ssid"],
        "internal_temp": diagnostics["internal_temp"],
        "uptime": diagnostics["uptime"],
        "free_ram": diagnostics["free_ram"]
    })

    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
    }

    print(f"[SEND] Mengirim ke: {SERVER_URL}")
    print(f"[SEND] Payload: {payload}")

    try:
        response = urequests.post(SERVER_URL, data=payload, headers=headers)

        print(f"[HTTP] Status: {response.status_code}")
        print(f"[HTTP] Response: {response.text}")

        if response.status_code in (200, 201):
            print("[OK] Data berhasil dikirim!")
            blink(led1, 2, 50, 50)
            led1.on()  
            response.close()
            return True
        else:
            print(f"[WARN] Server merespon {response.status_code}")
            response.close()
            return False

    except Exception as e:
        print(f"[ERROR] Gagal kirim data: {e}")
        print("[INFO] Pastikan:")
        print("  1. Laravel server jalan (php artisan serve --host=0.0.0.0)")
        print("  2. IP address benar")
        print("  3. ESP32 dan laptop di WiFi yang sama")
        blink(led, 2, 300, 300)
        return False

# ============================================
# MAIN PROGRAM
# ============================================

def main():
    print()
    print("============================================")
    print("  ESP32 Smart Home Sensor v1.0")
    print("  MicroPython Edition")
    print("============================================")
    print()

    beep_startup()

    wlan = connect_wifi()
    if not wlan:
        print("[FATAL] Tidak bisa lanjut tanpa WiFi")
        return

    success_count = 0
    fail_count = 0

    print()
    print(f"[INFO] Mulai kirim data setiap {SEND_INTERVAL} detik...")
    print(f"[INFO] Server: {SERVER_URL}")
    print("============================================")
    print()

    while True:
        if not wlan.isconnected():
            print("[!] WiFi terputus, menyambung ulang...")
            led1.off()
            wlan = connect_wifi()
            if not wlan:
                time.sleep(5)
                continue

        suhu, kelembaban = read_sensor()

        if suhu is not None and kelembaban is not None:
            print("--------------------------------------------")
            diagnostics = get_diagnostics(wlan)
            ok = send_data(suhu, kelembaban, diagnostics)
            if ok:
                success_count += 1
            else:
                fail_count += 1

            print(f"[STATS] Sukses: {success_count} | Gagal: {fail_count}")
            print("--------------------------------------------")
            print()

        time.sleep(SEND_INTERVAL)

# ============================================
# RUN
# ============================================
main()
