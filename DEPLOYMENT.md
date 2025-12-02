# Coopvest Africa - Deployment Guide

This guide covers deploying the Coopvest Africa Laravel backend to production environments.

## 📋 Pre-Deployment Checklist

### Security
- [ ] Change all default passwords and secrets
- [ ] Generate new `APP_KEY`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Configure HTTPS/SSL certificates
- [ ] Set up firewall rules
- [ ] Enable CORS for production domain only
- [ ] Configure rate limiting
- [ ] Set up DDoS protection

### Database
- [ ] Create production database
- [ ] Set strong database credentials
- [ ] Configure database backups
- [ ] Enable database encryption
- [ ] Set up database monitoring
- [ ] Test database connection
- [ ] Verify migrations run successfully

### File Storage
- [ ] Configure S3 or cloud storage for file uploads
- [ ] Set up file backup strategy
- [ ] Configure file permissions
- [ ] Set up CDN for static assets

### Email
- [ ] Configure email service (SendGrid, Mailgun, etc.)
- [ ] Set up email templates
- [ ] Test email delivery
- [ ] Configure bounce handling

### Monitoring & Logging
- [ ] Set up application monitoring (New Relic, Datadog, etc.)
- [ ] Configure centralized logging
- [ ] Set up error tracking (Sentry, Rollbar, etc.)
- [ ] Configure uptime monitoring
- [ ] Set up alerts

## 🚀 Deployment Steps

### 1. Server Setup

#### Ubuntu/Debian Server
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y php8.2 php8.2-{cli,fpm,mysql,redis,mbstring,xml,bcmath,curl,zip}

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Node.js (for frontend build if needed)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Clone Repository

```bash
cd /var/www
sudo git clone <repository-url> coopvest-backend
cd coopvest-backend
sudo chown -R www-data:www-data .
```

### 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit configuration
sudo nano .env
```

Update the following in `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=coopvest_prod
DB_USERNAME=coopvest_user
DB_PASSWORD=strong_password_here

FRONTEND_URL=https://yourdomain.com

MAIL_MAILER=sendgrid
MAIL_FROM_ADDRESS=noreply@yourdomain.com
SENDGRID_API_KEY=your_sendgrid_key

AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE coopvest_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'coopvest_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON coopvest_prod.* TO 'coopvest_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed
```

### 7. Storage & Permissions

```bash
# Create storage link
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 8. Nginx Configuration

Create `/etc/nginx/sites-available/coopvest-api`:

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;

    root /var/www/coopvest-backend/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css text/xml text/javascript 
               application/x-javascript application/xml+rss 
               application/json application/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/coopvest-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 9. SSL Certificate (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot certonly --nginx -d api.yourdomain.com
```

### 10. PHP-FPM Configuration

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 256M
max_execution_time = 300
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### 11. Supervisor Configuration

Create `/etc/supervisor/conf.d/coopvest-queue.conf`:

```ini
[program:coopvest-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/coopvest-backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/coopvest-queue.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start coopvest-queue:*
```

### 12. Cron Jobs

Add to crontab:
```bash
sudo crontab -e
```

Add:
```
* * * * * cd /var/www/coopvest-backend && php artisan schedule:run >> /dev/null 2>&1
```

### 13. Backup Strategy

Create backup script `/home/backup/backup-coopvest.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/backups/coopvest"
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -u coopvest_user -p$DB_PASSWORD coopvest_prod | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Application backup
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /var/www/coopvest-backend

# Storage backup
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/coopvest-backend/storage

# Keep only last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete
```

Add to crontab:
```
0 2 * * * /home/backup/backup-coopvest.sh
```

## 📊 Post-Deployment

### Verification
```bash
# Check application health
curl https://api.yourdomain.com/health

# Check logs
tail -f /var/log/nginx/error.log
tail -f /var/www/coopvest-backend/storage/logs/laravel.log

# Verify database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Monitoring Setup

1. **Application Monitoring**
   - Set up New Relic or Datadog
   - Configure performance alerts
   - Monitor API response times

2. **Error Tracking**
   - Set up Sentry
   - Configure error notifications
   - Monitor error rates

3. **Uptime Monitoring**
   - Set up UptimeRobot or similar
   - Configure alerting
   - Monitor critical endpoints

4. **Log Aggregation**
   - Set up ELK Stack or Datadog
   - Configure log retention
   - Set up log-based alerts

## 🔄 Maintenance

### Regular Tasks

**Daily:**
- Monitor error logs
- Check database size
- Verify backups

**Weekly:**
- Review performance metrics
- Check security alerts
- Update dependencies (if needed)

**Monthly:**
- Review audit logs
- Analyze usage patterns
- Plan capacity upgrades

### Updates & Patches

```bash
# Update dependencies
composer update

# Run tests
php artisan test

# Deploy
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
```

## 🚨 Troubleshooting

### Common Issues

**502 Bad Gateway**
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

**Database Connection Error**
```bash
# Check MySQL status
sudo systemctl status mysql

# Test connection
mysql -u coopvest_user -p -h localhost coopvest_prod
```

**Permission Denied**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/coopvest-backend
sudo chmod -R 755 /var/www/coopvest-backend
sudo chmod -R 775 /var/www/coopvest-backend/storage
```

**Out of Memory**
```bash
# Increase PHP memory limit
sudo nano /etc/php/8.2/fpm/php.ini
# Set: memory_limit = 512M
sudo systemctl restart php8.2-fpm
```

## 📞 Support

For deployment issues, contact your DevOps team or refer to Laravel documentation at https://laravel.com/docs
