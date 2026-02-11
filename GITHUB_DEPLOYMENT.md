# GitHub Deployment Guide: Misosi Kiganjani
## Deploying from GitHub -> AWS Ubuntu Server

This guide focuses specifically on using **Git and GitHub** to deploy your application code to your AWS Ubuntu server. This is the modern, professional way to manage deployments.

---

## 1. Local Setup (Your Computer)

First, ensure your local project is a Git repository and pushed to GitHub.

### Step 1.1: Initialize Git
If you haven't already:
```bash
# In your project folder (c:\xampp\htdocs\food_ordering)
git init
```

### Step 1.2: Configure .gitignore
**CRITICAL:** Create a `.gitignore` file to prevent sensitive data (like database passwords) from being uploaded to GitHub.

Create a file named `.gitignore` with:
```
/api/db.php
/uploads/*
!/uploads/.gitkeep
.env
.DS_Store
```
*(Note: Since we are ignoring `api/db.php`, we will create it manually on the server later.)*

### Step 1.3: Commit and Push
```bash
git add .
git commit -m "Initial commit for deployment"

# Create a new repository on GitHub.com (e.g., 'misosi-food-ordering')
# Then link it:
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/misosi-food-ordering.git
git push -u origin main
```

---

## 2. Server Setup (AWS Ubuntu)

Connect to your EC2 instance via SSH:
```bash
ssh -i "your-key.pem" ubuntu@your-ec2-ip
```

### Step 2.1: Install Git
```bash
sudo apt update
sudo apt install git -y
```

### Step 2.2: Setup Deployment Directory
We will deploy the app to `/var/www/html/food_ordering`.

```bash
# Navigate to web root
cd /var/www/html

# Remove default index.html if it exists
sudo rm -f index.html
```

### Step 2.3: Clone the Repository

**Option A: Public Repository (Easiest)**
If your repository is public:
```bash
sudo git clone https://github.com/YOUR_USERNAME/misosi-food-ordering.git food_ordering
```

**Option B: Private Repository (Recommended)**
If private, you need to use a Personal Access Token (PAT) or SSH keys.

*Using HTTPS with Token:*
```bash
sudo git clone https://YOUR_USERNAME:YOUR_TOKEN@github.com/YOUR_USERNAME/misosi-food-ordering.git food_ordering
```

---

## 3. Configuration & Permissions

Since we ignored `api/db.php` (security best practice), we need to create it manually on the server with the **production database credentials**.

### Step 3.1: Create Production Config
```bash
cd /var/www/html/food_ordering
sudo cp api/db.php.example api/db.php  # If you have an example file
# OR create it from scratch
sudo nano api/db.php
```

**Paste your PRODUCTION database credentials:**
```php
<?php
$host = 'localhost';
$db = 'food_ordering';
$user = 'food_user';        // The MySQL user you created in step 3.1 of DEPLOYMENT_GUIDE.md
$pass = 'YOUR_STRONG_PASSWORD'; // The password you set for that user
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
?>
```
*Save and exit (Ctrl+O, Enter, Ctrl+X).*

### Step 3.2: Set File Permissions
Apache needs permission to read files and write to the `uploads` folder.

```bash
# Set ownership to Apache user (www-data)
sudo chown -R www-data:www-data /var/www/html/food_ordering

# Set standard directory permissions (755)
sudo find /var/www/html/food_ordering -type d -exec chmod 755 {} \;

# Set standard file permissions (644)
sudo find /var/www/html/food_ordering -type f -exec chmod 644 {} \;

# Make uploads writable
sudo chmod -R 775 /var/www/html/food_ordering/uploads
```

---

## 4. Workflow: Updating the App

When you make changes on your local computer and want to deploy them:

1.  **Local Computer:**
    ```bash
    git add .
    git commit -m "Added new feature"
    git push origin main
    ```

2.  **AWS Server:**
    ```bash
    # Connect
    ssh -i "your-key.pem" ubuntu@your-ec2-ip

    # Navigate to app folder
    cd /var/www/html/food_ordering

    # Pull latest changes (as root/sudo because of ownership)
    sudo git pull origin main

    # Re-apply permissions if new files were added
    sudo chown -R www-data:www-data .
    ```

---

## 5. Troubleshooting GitHub Deployment

**Issue: "Permission denied (publickey)"**
- Ensure you added your SSH public key to GitHub Settings -> SSH and GPG Keys.

**Issue: "Password authentication required"**
- GitHub removed password auth. You ALWAYS need a Personal Access Token (PAT) for HTTPS cloning of private repos.

**Issue: "Changes to tracked files overwritten by merge"**
- This happens if you edited files directly on the server.
- Solution: `sudo git stash` to hide server changes, then `sudo git pull`.

---
**Deployment Complete!** 🚀
Your app is now live and linked to GitHub for easy updates.
