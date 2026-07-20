#!/bin/bash
# EkromSSH VPN - Complete One-Click Installer
# ใช้: bash <(curl -s https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/one-click.sh)

GREEN='\033[0;32m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"
IP=$(curl -s ipv4.icanhazip.com)

echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}   EKROMSSH VPN - One Click Install${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"
[[ $EUID -ne 0 ]] && { echo -e "${RED}❌ ต้องรัน root${NC}"; exit 1; }

# Step 1: Install NevermoreSSH (main.sh)
echo -e "\n${GREEN}[1/2]📥 กำลังติดตั้งระบบหลัก (NevermoreSSH)...${NC}"
wget -q -O /tmp/main.sh "$GH/main.sh" && chmod +x /tmp/main.sh
cd /tmp && bash main.sh

# Step 2: Install Web Admin Panel + Auto-Update
echo -e "\n${GREEN}[2/2]🌐 กำลังติดตั้ง Web Admin Panel...${NC}"

mkdir -p /var/www/admin /etc/ssh /usr/local/bin

# Download files
wget -q -O /var/www/admin/index.php "$GH/admin/index.php" && echo "  ✅ Admin Panel"
wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin && echo "  ✅ SSH Wrapper"

# Nginx config for :8888
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "8.1")
PHP_SOCK=$(ls /var/run/php/php*-fpm.sock 2>/dev/null | head -1)
SOCK_NAME=$(basename "$PHP_SOCK" 2>/dev/null || echo "php${PHP_VER}-fpm.sock")
cat > /etc/nginx/conf.d/admin.conf << NGINX
server { listen 8888; root /var/www/admin; index index.php;
    location / { try_files \$uri \$uri/ /index.php?\$args; }
    location ~ \.php\$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/var/run/php/$SOCK_NAME; }
}
NGINX

# Sudoers
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ssh-admin" > /etc/sudoers.d/ssh-admin
chmod 440 /etc/sudoers.d/ssh-admin
echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/chage" > /etc/sudoers.d/chage
chmod 440 /etc/sudoers.d/chage

# Auto-Update
wget -q -O /usr/local/bin/ekrom-update "$GH/admin/auto-update.sh" && chmod +x /usr/local/bin/ekrom-update
(crontab -l 2>/dev/null | grep -v ekrom-update; echo "0 */6 * * * /usr/local/bin/ekrom-update >/dev/null 2>&1") | crontab - 2>/dev/null

# Restart services
systemctl restart nginx 2>/dev/null
systemctl restart php*-fpm 2>/dev/null

echo ""
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ ติดตั้งเสร็จสมบูรณ์!${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo ""
echo -e "  📝 เมนู SSH:   ${GREEN}menu${NC}"
echo -e "  🌐 Web Admin:  ${GREEN}http://$IP:8888/${NC}"
echo -e "  🔑 รหัส:      ${GREEN}admin123${NC}"
echo -e "  🔄 Auto-Up:    ${GREEN}ทุก 6 ชม.${NC}"
echo ""
