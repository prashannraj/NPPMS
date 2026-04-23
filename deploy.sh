#!/bin/bash

# NPPMS Docker Deployment Script
# Usage: ./deploy.sh [environment]

set -e

ENV=${1:-production}
COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE=".env.production"

echo "🚀 Starting NPPMS deployment for environment: $ENV"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Load environment variables
if [ -f "$ENV_FILE" ]; then
    echo "📁 Loading environment variables from $ENV_FILE"
    export $(grep -v '^#' "$ENV_FILE" | xargs)
else
    echo "⚠️  Warning: $ENV_FILE not found. Using default environment variables."
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p nginx/ssl nginx/logs
mkdir -p backend/storage/logs

# Generate SSL certificates if they don't exist
if [ ! -f "nginx/ssl/cert.pem" ] || [ ! -f "nginx/ssl/key.pem" ]; then
    echo "🔐 Generating self-signed SSL certificates..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout nginx/ssl/key.pem \
        -out nginx/ssl/cert.pem \
        -subj "/C=NP/ST=Bagmati/L=Kathmandu/O=NPPMS/CN=nppms.local" 2>/dev/null || \
    echo "⚠️  Could not generate SSL certificates. Please generate them manually."
fi

# Build and start containers
echo "🏗️  Building Docker images..."
docker-compose -f "$COMPOSE_FILE" build

echo "🚀 Starting containers..."
docker-compose -f "$COMPOSE_FILE" up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check service status
echo "📊 Checking service status..."
docker-compose -f "$COMPOSE_FILE" ps

# Run database migrations
echo "🗄️  Running database migrations..."
docker-compose -f "$COMPOSE_FILE" exec backend php artisan migrate --force

# Run database seeders
echo "🌱 Running database seeders..."
docker-compose -f "$COMPOSE_FILE" exec backend php artisan db:seed --force

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
docker-compose -f "$COMPOSE_FILE" exec backend php artisan optimize

# Create storage link
echo "🔗 Creating storage link..."
docker-compose -f "$COMPOSE_FILE" exec backend php artisan storage:link

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Services:"
echo "   - Frontend: http://localhost:3000"
echo "   - Backend API: http://localhost:8000"
echo "   - Nginx: http://localhost (or https://localhost if SSL configured)"
echo "   - MinIO Console: http://localhost:9001"
echo "   - Meilisearch: http://localhost:7700"
echo "   - MailHog: http://localhost:8025"
echo ""
echo "🔧 Management commands:"
echo "   - View logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "   - Stop services: docker-compose -f $COMPOSE_FILE down"
echo "   - Restart services: docker-compose -f $COMPOSE_FILE restart"
echo "   - Update deployment: ./deploy.sh"