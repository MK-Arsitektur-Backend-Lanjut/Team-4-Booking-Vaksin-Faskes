# Spike Test Setup (Queue & Booking)

Project ini sudah disiapkan dengan skenario load test berbasis K6 yang dijalankan melalui Docker, khusus untuk menguji antrean (booking) faskes. Berada di file:

- `loadtest/queue/k6-spike.js`
- `loadtest/queue/export-k6-summary-csv.ps1`

## 1) Start service dan siapkan data
```bash
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed
```

## 2) Opsional: Naikkan rate limit API saat stress test
Karena endpoint API memakai middleware `throttle:api`, default limit biasanya 60-120 request/menit/IP.
Untuk pengujian lonjakan tinggi, naikkan variabel ini di file `.env`:
```env
API_RATE_LIMIT_PER_MINUTE=50000
```
Lalu restart app container:
```bash
docker compose restart app
```

## 3) Jalankan Smoke Test (Cek script & konektivitas)
```bash
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=smoke grafana/k6 run /scripts/loadtest/queue/k6-spike.js
```

## 4) Jalankan Spike Test Utama
```bash
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=spike grafana/k6 run /scripts/loadtest/queue/k6-spike.js
```

## 5) Jalankan Profile Burst 10k (Simulasi Lonjakan Besar)
```bash
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=10000 -e BURST10K_HOLD=120s grafana/k6 run /scripts/loadtest/queue/k6-spike.js
```

> **Catatan:** Jalankan di mesin yang cukup kuat. Jika ingin trial cepat, gunakan target + durasi pendek berikut:
```bash
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=200 -e BURST10K_HOLD=5s -e BURST_RAMP_1=3s -e BURST_RAMP_2=3s -e BURST_RAMP_3=3s -e BURST_RAMP_DOWN_1=3s -e BURST_RAMP_DOWN_2=3s -e REQUEST_TIMEOUT=10s grafana/k6 run /scripts/loadtest/queue/k6-spike.js
```

## 6) Simpan Hasil Test ke JSON
Di PowerShell jalankan ini untuk membuat folder (jika belum ada) dan export JSON metrics:
```powershell
New-Item -ItemType Directory -Force -Path .\loadtest\queue\results | Out-Null
```

Lalu jalankan K6 dengan flag summary export:
```bash
docker run --rm -v ${PWD}:/scripts -e BASE_URL=http://host.docker.internal:8000 -e K6_PROFILE=burst10k -e BURST10K_TARGET=10000 -e BURST10K_HOLD=120s grafana/k6 run --summary-export=/scripts/loadtest/queue/results/summary.json --out json=/scripts/loadtest/queue/results/metrics.json /scripts/loadtest/queue/k6-spike.js
```

## 7) Konversi Summary JSON ke CSV
Gunakan script PowerShell yang sudah disediakan untuk mengekstrak data dari `summary.json` menjadi `.csv`:
```powershell
powershell -ExecutionPolicy Bypass -File .\loadtest\queue\export-k6-summary-csv.ps1 -InputPath .\loadtest\queue\results\summary.json -OutputPath .\loadtest\queue\results\summary.csv
```

## 8) Pantau Bottleneck Container
Selama test berlangsung, buka terminal baru dan pantau penggunaan resource:
```bash
docker stats faskes_app faskes_nginx faskes_db
```
*(Sesuaikan nama container dengan yang ada di docker-compose Anda).*
