# Deployment Guide: Misosi Kiganjani Food Ordering System
## Amazon Ubuntu Server Deployment

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [Installing Dependencies](#installing-dependencies)
4. [Database Configuration](#database-configuration)
5. [Application Deployment](#application-deployment)
6. [Web Server Configuration](#web-server-configuration)
7. [Security Hardening](#security-hardening)
8. [SSL/HTTPS Setup](#ssl-https-setup)
9. [Post-Deployment](#post-deployment)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### What You Need:
- AWS Account with EC2 access
- Domain name (optional but recommended)
- SSH client (PuTTY for Windows, Terminal for Mac/Linux)
- Your application files (current project)

### Recommended EC2 Instance:
- **Instance Type**: t2.micro (Free tier) or t2.small
- **OS**: Ubuntu 22.04 LTS or Ubuntu 20.04 LTS
- **Storage**: 20GB minimum
- **Security Group**: Allow ports 22 (SSH), 80 (HTTP), 443 (HTTPS)

---

## Step 1: Server Setup

### 1.1 Launch EC2 Instance

1. **Log in to AWS Console** → Navigate to EC2
2. **Click "Launch Instance"**
3. **Configure Instance**:
   - Name: `misosi-food-ordering`
   - AMI: Ubuntu Server 22.04 LTS
   - Instance Type: t2.micro (or t2.small for better performance)
   - Key Pair: Create new or use existing (download .pem file)
   - Network Settings:
     - Allow SSH (port 22) from your IP
     - Allow HTTP (port 80) from anywhere
     - Allow HTTPS (port 443) from anywhere

4. **Launch Instance** and wait for it to start

### 1.2 Connect to Your Server

**For Windows (using PuTTY):**
```bash
# Convert .pem to .ppk using PuTTYgen first
# Then connect using PuTTY with the .ppk file
```

**For Mac/Linux:**
```bash
# Set permissions for your key file
chmod 400 your-key.pem

# Connect to server
ssh -i your-key.pem ubuntu@your-ec2-public-ip
```

### 1.3 Update System
```bash
sudo apt update && sudo apt upgrade -y
```

---

## Step 2: Installing Dependencies

### 2.1 Install Apache Web Server
```bash
sudo apt install apache2 -y
sudo systemctl start apache2
sudo systemctl enable apache2
sudo systemctl status apache2
```

### 2.2 Install MySQL Server
```bash
sudo apt install mysql-server -y
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation
```

**MySQL Secure Installation Prompts:**
- Set root password: **YES** (choose a strong password)
- Remove anonymous users: **YES**
- Disallow root login remotely: **YES**
- Remove test database: **YES**
- Reload privilege tables: **YES**

### 2.3 Install PHP and Required Extensions
```bash
sudo apt install php libapache2-mod-php php-mysql php-cli php-curl php-gd php-mbstring php-xml php-zip -y

# Verify PHP installation
php -v
```

### 2.4 Install Additional Tools
```bash
# Install Git (for version control)
sudo apt install git -y

# Install unzip (for extracting files)
sudo apt install unzip -y
```

---

## Step 3: Database Configuration

### 3.1 Create Database and User

```bash
# Login to MySQL
sudo mysql -u root -p
```

**Inside MySQL prompt:**
```sql
-- Create database
CREATE DATABASE food_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user
CREATE USER 'food_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON food_ordering.* TO 'food_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 3.2 Import Database Schema

**First, create the schema file on your local machine:**

Create `database_schema.sql` with the following content:

```sql
-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'rider', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    rider_id INT DEFAULT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'accepted', 'delivered', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@misosi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

**Upload and import the schema:**

```bash
# Upload schema file to server (from your local machine)
scp -i your-key.pem database_schema.sql ubuntu@your-ec2-public-ip:/home/ubuntu/

# On the server, import the schema
mysql -u food_user -p food_ordering < /home/ubuntu/database_schema.sql
```

---

## Step 4: Application Deployment

### 4.1 Prepare Application Directory

```bash
# Navigate to web root
cd /var/www/html

# Remove default Apache page
sudo rm -f index.html

# Create application directory
sudo mkdir -p /var/www/html/food_ordering
cd /var/www/html/food_ordering
```

### 4.2 Upload Application Files

**Option A: Using SCP (from your local machine)**
```bash
# Navigate to your project folder on local machine
cd c:\xampp\htdocs\food_ordering

# Upload files to server
scp -i your-key.pem -r * ubuntu@your-ec2-public-ip:/home/ubuntu/food_ordering_temp/

# On server, move files to web directory
sudo mv /home/ubuntu/food_ordering_temp/* /var/www/html/food_ordering/
```

**Option B: Using Git (recommended)**
```bash
# On server
cd /var/www/html
sudo git clone https://github.com/yourusername/food_ordering.git
# Or upload as zip and extract
```

### 4.3 Configure Database Connection

```bash
# Edit the database configuration file
sudo nano /var/www/html/food_ordering/api/db.php
```

**Update with your database credentials:**
```php
<?php
$host = 'localhost';
$db = 'food_ordering';
$user = 'food_user';
$pass = 'your_strong_password_here';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
```

### 4.4 Set Proper Permissions

```bash
# Set ownership to Apache user
sudo chown -R www-data:www-data /var/www/html/food_ordering

# Set directory permissions
sudo find /var/www/html/food_ordering -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/food_ordering -type f -exec chmod 644 {} \;

# Make uploads directory writable
sudo mkdir -p /var/www/html/food_ordering/uploads
sudo chmod 775 /var/www/html/food_ordering/uploads
sudo chown -R www-data:www-data /var/www/html/food_ordering/uploads
```

---

## Step 5: Web Server Configuration

### 5.1 Configure Apache Virtual Host

```bash
# Create virtual host configuration
sudo nano /etc/apache2/sites-available/food_ordering.conf
```

**Add the following configuration:**
```apache
<VirtualHost *:80>
    ServerAdmin admin@yourdomain.com
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/food_ordering

    <Directory /var/www/html/food_ordering>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Enable PHP
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/food_ordering_error.log
    CustomLog ${APACHE_LOG_DIR}/food_ordering_access.log combined
</VirtualHost>
```

**If you don't have a domain, use IP-based configuration:**
```apache
<VirtualHost *:80>
    ServerAdmin admin@localhost
    DocumentRoot /var/www/html/food_ordering

    <Directory /var/www/html/food_ordering>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/food_ordering_error.log
    CustomLog ${APACHE_LOG_DIR}/food_ordering_access.log combined
</VirtualHost>
```

### 5.2 Enable Site and Modules

```bash
# Enable the site
sudo a2ensite food_ordering.conf

# Disable default site
sudo a2dissite 000-default.conf

# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod php8.1

# Test Apache configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### 5.3 Configure PHP

```bash
# Edit PHP configuration
sudo nano /etc/php/8.1/apache2/php.ini
```

**Update these settings:**
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

**Create PHP log directory:**
```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

**Restart Apache:**
```bash
sudo systemctl restart apache2
```

---

## Step 6: Security Hardening

### 6.1 Configure Firewall (UFW)

```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Check status
sudo ufw status
```

### 6.2 Secure MySQL

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

**Add/verify these settings:**
```ini
[mysqld]
bind-address = 127.0.0.1
skip-networking = 0
max_connections = 100
```

**Restart MySQL:**
```bash
sudo systemctl restart mysql
```

### 6.3 Disable Directory Listing

```bash
# Edit Apache security configuration
sudo nano /etc/apache2/conf-available/security.conf
```

**Update:**
```apache
ServerTokens Prod
ServerSignature Off
TraceEnable Off
```

**Enable and restart:**
```bash
sudo a2enconf security
sudo systemctl restart apache2
```

### 6.4 Set Up Fail2Ban (Optional but Recommended)

```bash
# Install Fail2Ban
sudo apt install fail2ban -y

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit configuration
sudo nano /etc/fail2ban/jail.local
```

**Enable SSH protection:**
```ini
[sshd]
enabled = true
port = 22
maxretry = 3
bantime = 3600
```

**Start Fail2Ban:**
```bash
sudo systemctl start fail2ban
sudo systemctl enable fail2ban
```

---

## Step 7: SSL/HTTPS Setup

### 7.1 Install Certbot (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y
```

### 7.2 Obtain SSL Certificate

**If you have a domain:**
```bash
# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Follow the prompts:
# - Enter email address
# - Agree to terms
# - Choose to redirect HTTP to HTTPS (recommended)
```

**Test auto-renewal:**
```bash
sudo certbot renew --dry-run
```

### 7.3 Configure Auto-Renewal

```bash
# Certbot auto-renewal is set up automatically
# Verify cron job
sudo systemctl status certbot.timer
```

---

## Step 8: Post-Deployment

### 8.1 Test the Application

1. **Access your application:**
   - With domain: `http://yourdomain.com` or `https://yourdomain.com`
   - Without domain: `http://your-ec2-public-ip`

2. **Test login:**
   - Email: `admin@misosi.com`
   - Password: `admin123`

3. **Test features:**
   - ✅ User registration
   - ✅ Product management
   - ✅ Order placement
   - ✅ Rider assignment
   - ✅ Sales reports

### 8.2 Create Backup Script

```bash
# Create backup directory
sudo mkdir -p /home/ubuntu/backups

# Create backup script
sudo nano /home/ubuntu/backup.sh
```

**Add backup script:**
```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/home/ubuntu/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="food_ordering"
DB_USER="food_user"
DB_PASS="your_strong_password_here"

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/food_ordering

# Remove backups older than 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

**Make executable and schedule:**
```bash
# Make executable
sudo chmod +x /home/ubuntu/backup.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
```

**Add this line:**
```cron
0 2 * * * /home/ubuntu/backup.sh >> /var/log/backup.log 2>&1
```

### 8.3 Set Up Monitoring

```bash
# Install monitoring tools
sudo apt install htop iotop nethogs -y

# Check system resources
htop

# Monitor Apache logs
sudo tail -f /var/log/apache2/food_ordering_access.log
sudo tail -f /var/log/apache2/food_ordering_error.log
```

---

## Step 9: Troubleshooting

### Common Issues and Solutions

#### 1. **500 Internal Server Error**
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check PHP error logs
sudo tail -f /var/log/php/error.log

# Verify file permissions
sudo chown -R www-data:www-data /var/www/html/food_ordering
```

#### 2. **Database Connection Failed**
```bash
# Test MySQL connection
mysql -u food_user -p food_ordering

# Check MySQL status
sudo systemctl status mysql

# Verify credentials in api/db.php
```

#### 3. **File Upload Not Working**
```bash
# Check uploads directory permissions
sudo chmod 775 /var/www/html/food_ordering/uploads
sudo chown -R www-data:www-data /var/www/html/food_ordering/uploads

# Check PHP upload settings
php -i | grep upload_max_filesize
```

#### 4. **Apache Not Starting**
```bash
# Check configuration syntax
sudo apache2ctl configtest

# Check for port conflicts
sudo netstat -tulpn | grep :80

# View detailed error
sudo systemctl status apache2 -l
```

#### 5. **Session Issues**
```bash
# Check session directory permissions
sudo chmod 1733 /var/lib/php/sessions
sudo chown root:root /var/lib/php/sessions
```

### Useful Commands

```bash
# Restart all services
sudo systemctl restart apache2
sudo systemctl restart mysql

# Check service status
sudo systemctl status apache2
sudo systemctl status mysql

# View real-time logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/mysql/error.log

# Check disk space
df -h

# Check memory usage
free -h

# Check running processes
ps aux | grep apache
ps aux | grep mysql
```

---

## Step 10: Maintenance

### Regular Maintenance Tasks

**Weekly:**
- Check error logs
- Monitor disk space
- Review access logs for suspicious activity

**Monthly:**
- Update system packages: `sudo apt update && sudo apt upgrade`
- Review and optimize database
- Test backups

**Quarterly:**
- Security audit
- Performance optimization
- Review and update SSL certificates

### Performance Optimization

```bash
# Enable Apache caching
sudo a2enmod expires
sudo a2enmod headers

# Add to .htaccess
sudo nano /var/www/html/food_ordering/.htaccess
```

**Add caching rules:**
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## Support and Resources

### Useful Links
- Apache Documentation: https://httpd.apache.org/docs/
- MySQL Documentation: https://dev.mysql.com/doc/
- PHP Documentation: https://www.php.net/docs.php
- Let's Encrypt: https://letsencrypt.org/
- AWS EC2 Documentation: https://docs.aws.amazon.com/ec2/

### Emergency Contacts
- Server Admin: [Your Email]
- Database Admin: [Your Email]
- Technical Support: [Your Email]

---

## Deployment Checklist

- [ ] EC2 instance launched and accessible
- [ ] System packages updated
- [ ] Apache installed and running
- [ ] MySQL installed and secured
- [ ] PHP installed with required extensions
- [ ] Database created and schema imported
- [ ] Application files uploaded
- [ ] File permissions set correctly
- [ ] Virtual host configured
- [ ] SSL certificate installed (if using domain)
- [ ] Firewall configured
- [ ] Backup script created and scheduled
- [ ] Application tested and working
- [ ] Admin account accessible
- [ ] Monitoring set up
- [ ] Documentation updated

---

**Deployment Date**: _________________

**Deployed By**: _________________

**Server IP**: _________________

**Domain**: _________________

**Admin Credentials**: (Store securely, not in this document)

---

*End of Deployment Guide*
