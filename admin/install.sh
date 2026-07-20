#!/bin/bash
# EkromSSH VPN - Complete Auto Installer
# ใช้: wget -qO- https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh | bash

GREEN='\033[0;32m'; BLUE='\033[0;34m'; NC='\033[0m'
GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"
IP=$(curl -s ipv4.icanhazip.com)

echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}    EKROMSSH VPN - Auto Installer${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"

if [[ $EUID -ne 0 ]]; then echo "❌ ต้องรัน root"; exit 1; fi

# Base dependencies
echo -e "\n${GREEN}[1/6]📦 ติดตั้งแพ็กเกจ...${NC}"
apt update -y
apt install -y nginx php-fpm php-cli curl sudo jq uuid-runtime wget

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP $PHP_VER"

# Create directories
echo -e "\n${GREEN}[2/6]📁 สร้างโฟลเดอร์...${NC}"
mkdir -p /var/www/admin /etc/ssh

# Download main components
echo -e "\n${GREEN}[3/6]📥 ดาวน์โหลดไฟล์ระบบ...${NC}"

# Admin Panel
wget -q -O /var/www/admin/index.php "$GH/admin/index.php" && echo "  ✅ Admin Panel" || echo "  ❌ Admin Panel failed"

# SSH Admin Wrapper
wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin && echo "  ✅ SSH Admin Wrapper" || echo "  ❌ SSH Admin failed"

# SSH Menu
wget -q -O /usr/sbin/ssh "$GH/admin/ssh-menu.sh" && chmod +x /usr/sbin/ssh && echo "  ✅ SSH Menu" || echo "  ❌ SSH Menu failed"

# Auto-Update
wget -q -O /usr/local/bin/ekrom-update "$GH/admin/auto-update.sh" && chmod +x /usr/local/bin/ekrom-update && echo "  ✅ Auto-Update" || echo "  ❌ Auto-Update failed"

# Create DB files
touch /etc/ssh/.ssh.db

# Setup Nginx
echo -e "\n${GREEN}[4/6]⚙️ ตั้งค่า Nginx...${NC}"
PHP_SOCK=$(ls /var/run/php/php*-fpm.sock 2>/dev/null | head -1)
SOCK_NAME=$(basename "$PHP_SOCK" 2>/dev/null || echo "php${PHP_VER}-fpm.sock")

cat > /etc/nginx/conf.d/admin.conf << 'NGINX'
server {
    listen 8888;
    root /var/www/admin;
    index index.php;
    server_name _;
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }
}
NGINX

# Fix socket path
sed -i "s|php-fpm.sock|$SOCK_NAME|" /etc/nginx/conf.d/admin.conf

# Sudoers
echo -e "\n${GREEN}[5/6]🔑 ตั้งค่า Sudoers...${NC}"
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ssh-admin" > /etc/sudoers.d/ssh-admin
chmod 440 /etc/sudoers.d/ssh-admin
echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/chage" > /etc/sudoers.d/chage
chmod 440 /etc/sudoers.d/chage
echo "  ✅ Sudoers"

# Restart
echo -e "\n${GREEN}[6/6]🔄 รีสตาร์ทบริการ...${NC}"
systemctl restart nginx
systemctl restart php*-fpm 2>/dev/null

# Setup cron
(crontab -l 2>/dev/null; echo "0 */6 * * * /usr/local/bin/ekrom-update >/dev/null 2>&1") | crontab - 2>/dev/null
echo "  ✅ Cron Auto-Update"

echo ""
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ ติดตั้งสำเร็จ!${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo ""
echo -e "  📍 Web Admin: ${GREEN}http://$IP:8888/${NC}"
echo -e "  🔑 รหัส: ${GREEN}admin123${NC}"
echo -e "  📝 เมนู Terminal: ${GREEN}ssh${NC}"
echo -e "  🔄 Auto-Update: ${GREEN}ทุก 6 ชม.${NC}"
echo ""
