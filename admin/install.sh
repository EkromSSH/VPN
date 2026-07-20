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
apt install -y nginx php-fpm php-cli curl sudo jq uuid-runtime xz-utils wget screen git

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP $PHP_VER"

# Create directories
echo -e "\n${GREEN}[2/6]📁 สร้างโฟลเดอร์...${NC}"
mkdir -p /var/www/admin /etc/ssh /etc/vmess /etc/vless /etc/xray

# Download scripts
echo -e "\n${GREEN}[3/6]📥 ดาวน์โหลดสคริปต์ระบบ...${NC}"
for f in menu vmess vless add-ws add-vless del-ws del-vless renew-ws renew-vless cek-ws cek-vless; do
    wget -q -O /usr/sbin/$f "$GH/admin/$f.sh" 2>/dev/null && chmod +x /usr/sbin/$f && echo "  ✅ $f" || echo "  ⚠️ $f (optional)"
done

# Download main components
echo -e "\n${GREEN}[4/6]📥 ดาวน์โหลด Admin Panel...${NC}"
wget -q -O /var/www/admin/index.php "$GH/admin/index.php"
echo "  ✅ Admin Panel"

wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin"
chmod +x /usr/local/bin/ssh-admin
echo "  ✅ SSH Admin Wrapper"

# Setup Nginx
echo -e "\n${GREEN}[5/6]⚙️ ตั้งค่าระบบ...${NC}"
PHP_SOCK=$(ls /var/run/php/php*-fpm.sock 2>/dev/null | head -1)
SOCK_NAME=$(basename "$PHP_SOCK" 2>/dev/null || echo "php${PHP_VER}-fpm.sock")

cat > /etc/nginx/conf.d/admin.conf << NGINX
server {
    listen 8888;
    root /var/www/admin;
    index index.php;
    server_name _;
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/$SOCK_NAME;
    }
}
NGINX

# Sudoers
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ssh-admin" > /etc/sudoers.d/ssh-admin
chmod 440 /etc/sudoers.d/ssh-admin
echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/chage" > /etc/sudoers.d/chage
chmod 440 /etc/sudoers.d/chage

# SSH DB
touch /etc/ssh/.ssh.db
touch /etc/vmess/.vmess.db
touch /etc/vless/.vless.db

# Restart services
echo -e "\n${GREEN}[6/6]🔄 รีสตาร์ทบริการ...${NC}"
systemctl restart nginx
systemctl restart php*-fpm 2>/dev/null

echo ""
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ ติดตั้งสำเร็จ!${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo ""
echo -e "  📍 Web Admin: ${GREEN}http://$IP:8888/${NC}"
echo -e "  🔑 รหัส: ${GREEN}admin123${NC}"
echo -e ""
echo -e "  📝 เมนู SSH: ${GREEN}menu${NC}"
echo -e ""
echo -e "  ⚠️  เปลี่ยนรหัส admin ได้ที่:"
echo -e "     /var/www/admin/index.php (บรรทัด 2)"
echo ""

# Auto-update
echo -e "\n${GREEN}[7/7]🔄 ตั้งค่า Auto-Update...${NC}"
wget -q -O /usr/local/bin/ekrom-update "$GH/admin/auto-update.sh"
chmod +x /usr/local/bin/ekrom-update
(crontab -l 2>/dev/null; echo "0 */6 * * * /usr/local/bin/ekrom-update >/dev/null 2>&1") | crontab -
echo "  ✅ Auto-update ทุก 6 ชม."
