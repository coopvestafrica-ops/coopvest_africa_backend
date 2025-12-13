#!/bin/bash

# Coopvest Africa Backend - Environment Setup Script
# This script creates a .env file from .env.example with development defaults

set -e

ENV_FILE=".env"
ENV_EXAMPLE=".env.example"

if [ ! -f "$ENV_EXAMPLE" ]; then
    echo "❌ Error: $ENV_EXAMPLE not found!"
    exit 1
fi

if [ -f "$ENV_FILE" ]; then
    echo "⚠️  $ENV_FILE already exists. Skipping creation."
    exit 0
fi

echo "📝 Creating $ENV_FILE from $ENV_EXAMPLE..."

# Copy the example file
cp "$ENV_EXAMPLE" "$ENV_FILE"

# Generate a random APP_KEY for Laravel
APP_KEY=$(php -r 'echo base64_encode(random_bytes(32));')
sed -i "s|APP_KEY=|APP_KEY=base64:${APP_KEY}|g" "$ENV_FILE"

# Set development defaults
sed -i 's|your-project-id|coopvest-demo|g' "$ENV_FILE"
sed -i 's|your-api-key|AIzaSyDemoKey123456789|g' "$ENV_FILE"
sed -i 's|your-project.firebaseapp.com|coopvest-demo.firebaseapp.com|g' "$ENV_FILE"
sed -i 's|https://your-project.firebaseio.com|https://coopvest-demo.firebaseio.com|g' "$ENV_FILE"
sed -i 's|your-project.appspot.com|coopvest-demo.appspot.com|g' "$ENV_FILE"
sed -i 's|your-sender-id|123456789|g' "$ENV_FILE"
sed -i 's|your-app-id|1:123456789:web:abcdef123456|g' "$ENV_FILE"
sed -i 's|your-measurement-id|G-DEMO123456|g' "$ENV_FILE"

echo "✅ $ENV_FILE created successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Update Firebase credentials in $ENV_FILE with your actual Firebase project details"
echo "2. Configure database connection (DB_HOST, DB_USERNAME, DB_PASSWORD)"
echo "3. Run: composer install"
echo "4. Run: php artisan migrate"
echo "5. Run: php artisan serve"
