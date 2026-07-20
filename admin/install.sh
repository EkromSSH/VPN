#!/bin/bash
# EkromSSH VPN - Auto Install Web Admin Panel
# ใช้: wget -qO- https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh | bash

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================${NC}"
echo -e "${GREEN}  EkromSSH VPN Admin Panel${NC}"
echo -e "${BLUE}================================${NC}"

# Check root
if [[ $EUID -ne 0 ]]; then
   echo "❌ ต้องรันด้วย root"
   exit 1
fi

# 1. Install dependencies
echo -e "${GREEN}[1/5]📦 ติดตั้ง Nginx + PHP...${NC}"
apt update -y
apt install -y nginx php-fpm php-cli curl sudo

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP $PHP_VER"

# 2. Download admin panel from GitHub
echo -e "${GREEN}[2/5]📥 ดาวน์โหลด Admin Panel...${NC}"
mkdir -p /var/www/admin
wget -q -O /var/www/admin/index.php \
  "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/index.php"
echo "✅ Admin Panel downloaded"

# 3. Download ssh-admin wrapper
echo -e "${GREEN}[3/5]🔧 ติดตั้ง SSH Admin Wrapper...${NC}"
wget -q -O /usr/local/bin/ssh-admin \
  "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/ssh-admin"
chmod +x /usr/local/bin/ssh-admin

# 4. Setup Nginx + Sudo
echo -e "${GREEN}[4/5]⚙️ ตั้งค่า Nginx + Sudo...${NC}"
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

# Fix fastcgi path based on PHP version
PHP_SOCK=$(ls /var/run/php/php*-fpm.sock 2>/dev/null | head -1)
if [[ -n "$PHP_SOCK" ]]; then
    SOCK_NAME=$(basename "$PHP_SOCK")
    sed -i "s|php-fpm.sock|$SOCK_NAME|" /etc/nginx/conf.d/admin.conf
fi

# Sudoers for www-data
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ssh-admin" > /etc/sudoers.d/ssh-admin
chmod 440 /etc/sudoers.d/ssh-admin

# Sudo for chage
echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/chage" > /etc/sudoers.d/chage
chmod 440 /etc/sudoers.d/chage

# 5. Restart services
echo -e "${GREEN}[5/5]🔄 รีสตาร์ทบริการ...${NC}"
systemctl restart nginx
systemctl restart php*-fpm 2>/dev/null

# Clean setup
rm -f /usr/sbin/add-ws /usr/sbin/add-vless 2>/dev/null

# Get IP
IP=$(curl -s ipv4.icanhazip.com)

echo ""
echo -e "${BLUE}================================${NC}"
echo -e "${GREEN}  ✅ ติดตั้งสำเร็จ!${NC}"
echo -e "${BLUE}================================${NC}"
echo ""
echo -e "  📍 URL: ${GREEN}http://$IP:8888/${NC}"
echo -e "  🔑 รหัส: ${GREEN}admin123${NC}"
echo ""
echo -e "  หมายเหตุ:"
echo -e "  - เปลี่ยนรหัสผ่านได้ที่ /var/www/admin/index.php บรรทัด 2"
echo -e "  - ต้องมี SSH DB: /etc/ssh/.ssh.db (สร้างอัตโนมัติ)"
echo ""
