#!/bin/bash

# Pastikan script berhenti jika ada error
set -e

# Ambil argumen pertama (misal: "seed")
ACTION=$1

echo "---------------------------------------------------"
echo "🚀 Klinik Sehat Auto-Deploy: $(date)"
echo "---------------------------------------------------"

# 1. Ambil update terbaru
echo "📥 Pulling latest code..."
git pull origin main

# 2. Update dependencies (PHP & JS)
echo "📦 Updating dependencies..."
docker exec klinik-sehat-app composer install --no-dev --optimize-autoloader
# Jika di Intel NUC Anda sudah ada node_modules, npm install bisa dilewat jika tidak ada perubahan package.json
docker exec klinik-sehat-app npm install
docker exec klinik-sehat-app npm run build

# 3. Database & Cache
echo "🗄️ Running migrations & clearing cache..."

# Cek apakah user memasukkan argumen "seed"
if [ "$ACTION" == "seed" ]; then
    echo "⚠️  WARNING: Running Fresh Migration & Seeding..."
    # Kita paksa APP_ENV=local agar tidak ditolak Laravel, dan gunakan --force
    docker exec -e APP_ENV=local klinik-sehat-app php artisan migrate:fresh --seed --force
else
    # Jalankan migrasi standar jika tidak ada argumen seed
    docker exec klinik-sehat-app php artisan migrate --force
fi

echo "🧹 Cleaning up caches..."
docker exec klinik-sehat-app php artisan optimize:clear
docker exec klinik-sehat-app php artisan optimize

# 4. Restart Reverb (Krusial untuk Real-time dashboard QResta/Klinik)
echo "🔄 Restarting Reverb server..."
docker compose restart klinik-sehat-reverb

echo "---------------------------------------------------"
echo "✅ DEPLOY SUCCESSFUL!"
if [ "$ACTION" == "seed" ]; then echo "🔥 Database has been REFRESHED & SEEDED"; fi
echo "---------------------------------------------------"