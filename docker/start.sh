#!/bin/bash

# Wait for database to be ready
echo "Waiting for database connection..."
php artisan tinker --execute="DB::connection()->getPdo();" || sleep 10

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache

# Start Apache
echo "Starting Apache..."
apache2-foreground