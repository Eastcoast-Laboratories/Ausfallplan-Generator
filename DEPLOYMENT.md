# Production Deployment Guide

## Initial Setup on eclabs-vm06

### 1. Clone Repository
```bash
cd /var/kunden/webs/ruben/www/fairnestplan.z11.de
git clone git@github.com:Eastcoast-Laboratories/Ausfallplan-Generator.git .
```

### 2. Install Dependencies
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (if needed)
npm install
```

### 3. Configure Environment
```bash
# Copy .env.example to .env
cp .env.example .env

# Edit .env with production values
nano .env
```

**Production .env settings:**
```env
DEBUG=false
SECURITY_SALT=your-production-salt-here
DATABASE_URL=mysql://ausfallplan_generator:i1aeLZFUmoo7mWdy@localhost/ausfallplan_generator?encoding=utf8mb4&timezone=UTC&cacheMetadata=true&quoteIdentifiers=false&persistent=false
```

### 4. Database Setup
```bash
# Run migrations
bin/cake migrations migrate

# Verify database connection
bin/cake db status
```

### 5. Set Permissions
```bash
chmod -R 775 tmp logs
chmod +x deploy.sh
chown -R www-data:www-data tmp logs
```

### 6. Web Server Configuration

**Apache .htaccess** should already be in webroot/

For Apache, ensure mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 7. Test Deployment
```bash
curl http://fairnestplan.z11.de
```

## Regular Deployments

For regular deployments, simply run:
```bash
cd /var/kunden/webs/ruben/www/fairnestplan.z11.de
./deploy.sh
```

## Troubleshooting

### Check logs
```bash
tail -f logs/error.log
tail -f logs/debug.log
```

### Clear cache manually
```bash
rm -rf tmp/cache/*
```

### Check file permissions
```bash
ls -la tmp logs
```
