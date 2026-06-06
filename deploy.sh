#!/bin/bash

# YouCast Deployment Script
# This script is intended to be run on the production server.

set -e # Exit immediately if a command exits with a non-zero status.

echo "🚀 Starting deployment..."

# 1. Pull the latest code
echo "📥 Pulling latest changes from git..."
git pull origin main

# 2. Install PHP dependencies
echo "🐘 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Update the YouTube extraction binary
echo "📹 Updating yt-dlp binary..."
php artisan youcast:update-bin --no-interaction

# 4. Build Frontend Assets
# We install dev dependencies temporarily to run the build, then clean them up.
echo "📦 Building frontend assets..."
if [ -f "package.json" ]; then
    npm ci --include=dev
    npm run build
    echo "🧹 Cleaning up Node modules..."
    rm -rf node_modules
fi

# 5. Run Database Migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 6. Optimize Laravel
echo "⚡ Optimizing Laravel configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Set Permissions
# Ensure the web server can write to necessary directories
echo "🔑 Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache public/build bin/yt-dlp

echo "✅ Deployment completed successfully!"
