#!/bin/bash

# NPPMS VPS Setup Script
# Run this on your Ubuntu VPS to prepare for deployment

set -e

echo "🖥️  Setting up VPS for NPPMS deployment..."

# Update system
echo "🔄 Updating system packages..."
sudo apt-get update
sudo apt-get upgrade -y

# Install Docker
echo "🐳 Installing Docker..."
sudo apt-get install -y apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io

# Install Docker Compose
echo "📦 Installing Docker Compose..."
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add current user to docker group
echo "👥 Adding user to docker group..."
sudo usermod -aG docker $USER

# Install Git
echo "📚 Installing Git..."
sudo apt-get install -y git

# Create application directory
echo "📁 Creating application directory..."
sudo mkdir -p /opt/nppms
sudo chown -R $USER:$USER /opt/nppms

# Install Nginx (for reverse proxy if not using Docker Nginx)
echo "🌐 Installing Nginx (optional)..."
sudo apt-get install -y nginx

# Configure firewall
echo "🔥 Configuring firewall..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8000/tcp
sudo ufw allow 3000/tcp
sudo ufw --force enable

# Generate SSH key for deployment (optional)
echo "🔑 Generating SSH key for deployment..."
if [ ! -f ~/.ssh/id_rsa ]; then
    ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa -N ""
fi

echo ""
echo "✅ VPS setup completed!"
echo ""
echo "📋 Next steps:"
echo "   1. Log out and log back in for docker group changes to take effect"
echo "   2. Clone your NPPMS repository:"
echo "      cd /opt/nppms && git clone <your-repo-url> ."
echo "   3. Copy .env.production and configure it:"
echo "      cp .env.production .env"
echo "      nano .env"
echo "   4. Run the deployment script:"
echo "      chmod +x deploy.sh && ./deploy.sh"
echo ""
echo "🔧 Useful commands:"
echo "   - Check Docker: docker --version"
echo "   - Check Docker Compose: docker-compose --version"
echo "   - Check system status: systemctl status docker"