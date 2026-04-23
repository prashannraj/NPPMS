#!/bin/bash

# Configuration validation script
# This script validates the Docker configuration without running containers

echo "🔍 Validating NPPMS Docker configuration..."

# Check if required files exist
required_files=(
    "docker-compose.prod.yml"
    "backend/Dockerfile"
    "frontend/Dockerfile"
    "nginx/nginx.prod.conf"
    ".env.production"
)

missing_files=0
for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file exists"
    else
        echo "❌ $file is missing"
        missing_files=$((missing_files + 1))
    fi
done

# Validate Docker Compose syntax (if docker-compose is available)
if command -v docker-compose &> /dev/null; then
    echo "📋 Validating docker-compose.prod.yml syntax..."
    docker-compose -f docker-compose.prod.yml config --quiet
    if [ $? -eq 0 ]; then
        echo "✅ docker-compose.prod.yml syntax is valid"
    else
        echo "❌ docker-compose.prod.yml has syntax errors"
        missing_files=$((missing_files + 1))
    fi
else
    echo "⚠️  docker-compose not available, skipping syntax validation"
fi

# Check for required directories
required_dirs=(
    "nginx/ssl"
    "nginx/logs"
    "backend/storage"
)

for dir in "${required_dirs[@]}"; do
    if [ -d "$dir" ]; then
        echo "✅ Directory $dir exists"
    else
        echo "⚠️  Directory $dir doesn't exist (will be created during deployment)"
    fi
done

# Validate environment variables in .env.production
echo "🔧 Checking required environment variables..."
required_vars=(
    "DB_PASSWORD"
    "APP_KEY"
    "APP_URL"
    "MEILISEARCH_KEY"
    "MINIO_ACCESS_KEY"
    "MINIO_SECRET_KEY"
)

if [ -f ".env.production" ]; then
    for var in "${required_vars[@]}"; do
        if grep -q "^$var=" ".env.production"; then
            echo "✅ $var is set"
        else
            echo "⚠️  $var is not set in .env.production"
        fi
    done
fi

# Summary
echo ""
echo "📊 Validation Summary:"
if [ $missing_files -eq 0 ]; then
    echo "✅ All required files are present"
    echo "🚀 Configuration is ready for deployment!"
else
    echo "⚠️  $missing_files issues found. Please fix them before deployment."
    exit 1
fi

echo ""
echo "📋 Deployment checklist:"
echo "   1. Update .env.production with your actual values"
echo "   2. Generate a secure APP_KEY: php artisan key:generate --show"
echo "   3. Place SSL certificates in nginx/ssl/ (or use Let's Encrypt)"
echo "   4. Run ./deploy.sh on your VPS"