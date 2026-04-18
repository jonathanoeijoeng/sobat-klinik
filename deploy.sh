#!/bin/bash

set -e

# Ambil argumen
ACTION=$1   # Contoh: "seed" atau "deploy"
MODE=$2     # Contoh: "local" atau "docker" (default ke docker jika kosong)

echo "---------------------------------------------------"
echo "🚀 Sobat Klinik Auto-Deploy: $(date)"
echo "Mode: ${MODE:-docker}"
echo "---------------------------------------------------"

# 1. Ambil update terbaru
echo "📥 Pulling latest code..."
git pull origin main

# 2. Fungsi Helper untuk menjalankan command
# Ini akan mengecek apakah MODE == "local", jika tidak maka pakai docker
run_cmd() {
    if [ "$MODE" == "local" ]; then
        # Jalankan langsung di host
        $@
    else
        # Jalankan di dalam container docker
        docker exec sobat-klinik-app $@
    fi
}

# 3. Update dependencies
echo "📦 Updating dependencies..."

if [ "$MODE" == "local" ]; then
    echo "Running composer and npm locally..."
    composer install
    npm install
    npm run build
else
    echo "Running composer and npm inside Docker container..."
    docker exec sobat-klinik-app composer install --no-dev --optimize-autoloader
    docker exec sobat-klinik-app npm install
    docker exec sobat-klinik-app npm run build
fi

# 4. Database & Cache
echo "🗄️ Running migrations & clearing cache..."

if [ "$ACTION" == "seed" ]; then
    echo "⚠️  WARNING: Running Fresh Migration & Seeding..."
    if [ "$MODE" == "local" ]; then
        php artisan migrate:fresh --seed --force
    else
        docker exec -e APP_ENV=local sobat-klinik-app php artisan migrate:fresh --seed --force
    fi
else
    run_cmd php artisan migrate --force
fi

echo "🧹 Cleaning up caches..."
run_cmd php artisan optimize:clear
run_cmd php artisan optimize

# 5. Restart Reverb
echo "🔄 Restarting Reverb server..."
if [ "$MODE" == "local" ]; then
    # Jika local, biasanya menggunakan pm2 atau systemd, sesuaikan di sini
    # Contoh: pm2 restart reverb-app
    echo "Skipping docker restart in local mode..."
else
    docker compose restart sobat-klinik-reverb
fi

echo "---------------------------------------------------"
echo "✅ DEPLOY SUCCESSFUL!"
echo "---------------------------------------------------"