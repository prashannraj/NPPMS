# NPPMS Docker Deployment Guide

This guide explains how to deploy the NPPMS (Nepal Public Procurement Management System) application using Docker on your VPS.

## Prerequisites

- Ubuntu 22.04 VPS (the one you're logged into via PuTTY)
- SSH access to the VPS
- Domain name (optional, for SSL)

## Files Created for Deployment

1. **`docker-compose.prod.yml`** - Production Docker Compose configuration
2. **`backend/Dockerfile`** - Multi-stage Dockerfile for Laravel backend
3. **`frontend/Dockerfile`** - Multi-stage Dockerfile for Next.js frontend
4. **`nginx/nginx.prod.conf`** - Production Nginx configuration with SSL
5. **`.env.production`** - Environment variables template
6. **`deploy.sh`** - Automated deployment script
7. **`setup-vps.sh`** - VPS setup script
8. **`validate-config.sh`** - Configuration validation script

## Deployment Steps

### Step 1: Prepare Your VPS

1. **Transfer files to VPS**:
   ```bash
   # On your local machine, compress the project
   tar -czf nppms-deploy.tar.gz --exclude=node_modules --exclude=vendor --exclude=.next .
   
   # Transfer to VPS using SCP (from local machine)
   scp nppms-deploy.tar.gz appan@163.61.41.14:/home/appan/
   ```

2. **SSH into your VPS** (you're already logged in):
   ```bash
   ssh appan@163.61.41.14
   ```

3. **Extract and setup**:
   ```bash
   cd /home/appan
   tar -xzf nppms-deploy.tar.gz -C /opt/nppms
   cd /opt/nppms
   ```

### Step 2: Setup VPS Environment

Run the setup script to install Docker and dependencies:

```bash
chmod +x setup-vps.sh
./setup-vps.sh
```

**Important**: After running the setup script, you need to log out and log back in for Docker group changes to take effect:
```bash
exit
# Then SSH back in
ssh appan@163.61.41.14
cd /opt/nppms
```

### Step 3: Configure Environment Variables

1. Copy the environment template:
   ```bash
   cp .env.production .env
   ```

2. Edit the `.env` file with your actual values:
   ```bash
   nano .env
   ```

   **Critical values to update**:
   - `APP_KEY`: Generate with `php artisan key:generate --show` (run in backend container later)
   - `DB_PASSWORD`: Set a secure password
   - `APP_URL`: Your domain or VPS IP
   - `NEXT_PUBLIC_API_URL`: Your backend API URL
   - Email settings for production

### Step 4: Generate SSL Certificates (Optional but Recommended)

For production, you should use Let's Encrypt or purchase SSL certificates. For testing, self-signed certificates will be generated automatically.

If you have a domain, use Certbot:
```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot certonly --nginx -d your-domain.com
```

Then copy certificates to the right location:
```bash
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem nginx/ssl/key.pem
```

### Step 5: Deploy the Application

Run the deployment script:
```bash
chmod +x deploy.sh
./deploy.sh
```

The script will:
1. Build Docker images
2. Start all containers
3. Run database migrations
4. Seed the database
5. Optimize Laravel

### Step 6: Verify Deployment

Check if all services are running:
```bash
docker-compose -f docker-compose.prod.yml ps
```

View logs:
```bash
docker-compose -f docker-compose.prod.yml logs -f
```

### Step 7: Access the Application

- **Frontend**: http://your-vps-ip or https://your-domain.com
- **Backend API**: http://your-vps-ip:8000/api
- **MinIO Console**: http://your-vps-ip:9001
- **Meilisearch**: http://your-vps-ip:7700
- **MailHog** (email testing): http://your-vps-ip:8025

## Post-Deployment Tasks

### 1. Generate Application Key
If you didn't set `APP_KEY` in `.env`, generate it:
```bash
docker-compose -f docker-compose.prod.yml exec backend php artisan key:generate
```

### 2. Create Admin User
Create the first admin user:
```bash
docker-compose -f docker-compose.prod.yml exec backend php artisan tinker
# Then run:
# \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')])
```

### 3. Set Up Cron Jobs
For Laravel scheduler:
```bash
# Edit crontab
crontab -e
# Add:
* * * * * cd /opt/nppms && docker-compose -f docker-compose.prod.yml exec -T backend php artisan schedule:run >> /dev/null 2>&1
```

### 4. Backup Strategy
Set up regular backups:
```bash
# Create backup script
cat > /opt/nppms/backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/nppms/backups"
DATE=$(date +%Y%m%d_%H%M%S)
docker-compose -f docker-compose.prod.yml exec -T postgres pg_dump -U nppms_user nppms > $BACKUP_DIR/db_backup_$DATE.sql
tar -czf $BACKUP_DIR/full_backup_$DATE.tar.gz $BACKUP_DIR/db_backup_$DATE.sql nginx/ssl
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF
chmod +x /opt/nppms/backup.sh
```

## Maintenance

### Update Application
```bash
cd /opt/nppms
git pull  # if using git
./deploy.sh
```

### Stop Services
```bash
docker-compose -f docker-compose.prod.yml down
```

### Restart Services
```bash
docker-compose -f docker-compose.prod.yml restart
```

### View Resource Usage
```bash
docker stats
```

## Troubleshooting

### 1. Port Already in Use
If ports 80, 443, 8000, or 3000 are already in use:
```bash
sudo netstat -tulpn | grep :80
# Stop the conflicting service or change ports in docker-compose.prod.yml
```

### 2. Database Connection Issues
Check PostgreSQL logs:
```bash
docker-compose -f docker-compose.prod.yml logs postgres
```

### 3. Docker Permission Errors
Ensure user is in docker group:
```bash
sudo usermod -aG docker $USER
# Log out and log back in
```

### 4. Insufficient Disk Space
Check disk usage:
```bash
df -h
docker system df
# Clean up unused Docker resources
docker system prune -a
```

## Security Considerations

1. **Change default passwords** in `.env` file
2. **Use strong passwords** for database and services
3. **Enable firewall** (UFW is configured in setup-vps.sh)
4. **Regular updates**: Keep Docker images and system updated
5. **Monitor logs** for suspicious activity
6. **Use HTTPS** in production with valid SSL certificates

## Support

For issues with deployment, check:
- Docker logs: `docker-compose -f docker-compose.prod.yml logs`
- Application logs: `docker-compose -f docker-compose.prod.yml exec backend tail -f storage/logs/laravel.log`
- Nginx logs: `docker-compose -f docker-compose.prod.yml exec nginx tail -f /var/log/nginx/error.log`

The application is now deployed and ready for use!