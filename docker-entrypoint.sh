#!/bin/sh
set -e

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Function to cleanup child processes
cleanup() {
    echo "Cleaning up processes..."
    kill $(jobs -p)
    exit 0
}

# Setup signal trapping
trap cleanup INT TERM

# Start both processes in parallel
npm run dev & 
php artisan serve --host=0.0.0.0 --port=8000 &

# Wait for all background processes
wait