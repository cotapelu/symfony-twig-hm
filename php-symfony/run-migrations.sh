#!/bin/bash

# Setup Database and Run Migrations for Symfony App

set -e

echo "=== Symfony Migration Runner ==="

# Check DATABASE_URL
if [ -z "$DATABASE_URL" ]; then
    echo "DATABASE_URL not set. Using values from .env..."
    export DATABASE_URL="postgresql://${DATABASE_USER:-db_user}:${DATABASE_PASSWORD:-db_password}@${DATABASE_HOST:-localhost}:${DATABASE_PORT:-5432}/${DATABASE_NAME:-community_db}"
    echo "Using DATABASE_URL: $DATABASE_URL"
fi

# Wait for database to be ready (if using Docker)
if command -v pg_isready &> /dev/null; then
    echo "Waiting for database..."
    pg_isready -h ${DATABASE_HOST:-localhost} -p ${DATABASE_PORT:-5432} -U ${DATABASE_USER:-db_user} -d ${DATABASE_NAME:-community_db}
fi

# Run migrations
echo "Running Doctrine Migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Migrations completed successfully!"

# Optional: Show migration status
php bin/console doctrine:migrations:status
