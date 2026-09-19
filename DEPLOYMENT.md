# Rinasa Catering Operations - VPS Deployment Guide

## Prerequisites
- Ubuntu 20.04+ or Debian 11+ VPS
- PHP 8.2+ with extensions: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml, curl, zip, gd
- MySQL 8.0+ or MariaDB 10.5+
- Composer
- Nginx or Apache
- SSL certificate (Let's Encrypt)

## 1. Server Setup

### Install Required Packages
```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath nginx mysql-server composer
```

### Create Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE rinasa_catering;
CREATE USER 'rinasa_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON rinasa_catering.* TO 'rinasa_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 2. Deploy Backend

### Clone/Copy Project
```bash
cd /var/www
git clone https://github.com/aloncaptg/backend.git /var/www/rinasa/backend
# OR copy files via SCP/rsync
cd /var/www/rinasa/backend
```

### Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### Configure Environment
```bash
cp .env.production .env
php artisan key:generate
```

Edit `.env` with your actual database credentials and settings.

### Run Migrations
```bash
php artisan migrate --force
php artisan db:seed --force  # If you have seeders
```

### Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/rinasa/backend
sudo chmod -R 775 /var/www/rinasa/backend/storage
sudo chmod -R 775 /var/www/rinasa/backend/bootstrap/cache
```

### Create Storage Link
```bash
php artisan storage:link
```

### Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Configure Nginx

### Create Nginx Config
```bash
sudo nano /etc/nginx/sites-available/rinasa
```

```nginx
server {
    listen 80;
    server_name server.rinasa.com;
    root /var/www/rinasa/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/rinasa /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Install SSL (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d server.rinasa.com
```

## 4. Test API

```bash
curl https://server.rinasa.com/api/items
```

## 5. Deploy Mobile App

The APK is located at:
```
mobile/build/app/outputs/flutter-apk/app-release.apk
```

### Install on Android
1. Transfer APK to device
2. Enable "Install from Unknown Sources" in device settings
3. Install the APK

### Or Publish to Play Store
1. Sign the APK with your keystore
2. Upload to Google Play Console

## 6. Troubleshooting

### Storage Issues
If photos aren't accessible:
```bash
# Check storage link exists
ls -la /var/www/rinasa/backend/public/storage

# Recreate if needed
cd /var/www/rinasa/backend
php artisan storage:link
```

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/rinasa/backend/storage
sudo chmod -R 775 /var/www/rinasa/backend/storage
```

### Database Connection
Check `.env` database settings and ensure MySQL is running:
```bash
sudo systemctl status mysql
```

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## 7. Monitoring

### View Logs
```bash
tail -f /var/www/rinasa/backend/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Check Services
```bash
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
sudo systemctl status mysql
```

## Notes
- The app uses SQLite for development, MySQL for production
- Storage disk is set to `public` for production (accessible via web)
- CORS may need configuration if frontend is on different domain
- Consider setting up queue workers for background jobs
