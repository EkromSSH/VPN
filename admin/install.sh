#!/bin/bash
# EkromSSH VPN - Complete Auto Installer
# ใช้: curl -s https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh | bash

GREEN='\033[0;32m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"
IP=$(curl -s ipv4.icanhazip.com)

echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}    EKROMSSH VPN - Auto Installer${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo ""

[[ $EUID -ne 0 ]] && { echo -e "${RED}❌ ต้องรัน root${NC}"; exit 1; }

# 1. Install packages
echo -e "${GREEN}[1/4]📦 ติดตั้งแพ็กเกจ...${NC}"
apt update -y
apt install -y nginx php-fpm php-cli curl sudo jq uuid-runtime wget screen netcat-openbsd
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP $PHP_VER"

# 2. Create directories
echo -e "\n${GREEN}[2/4]📁 สร้างโฟลเดอร์...${NC}"
mkdir -p /var/www/admin /etc/ssh /etc/vmess /etc/vless /etc/xray /usr/local/bin

# 3. Download ALL scripts
echo -e "\n${GREEN}[3/4]📥 ดาวน์โหลดสคริปต์ทั้งหมด...${NC}"

# Menu system
wget -q -O /usr/sbin/menu "$GH/admin/menu" && chmod +x /usr/sbin/menu && echo "  ✅ menu"
wget -q -O /usr/sbin/ssh "$GH/admin/ssh" && chmod +x /usr/sbin/ssh && echo "  ✅ ssh"
wget -q -O /usr/sbin/add-ssh "$GH/admin/add-ssh" && chmod +x /usr/sbin/add-ssh && echo "  ✅ add-ssh"
wget -q -O /usr/sbin/del-ssh "$GH/admin/del-ssh" && chmod +x /usr/sbin/del-ssh && echo "  ✅ del-ssh"
wget -q -O /usr/sbin/renew-ssh "$GH/admin/renew-ssh" && chmod +x /usr/sbin/renew-ssh && echo "  ✅ renew-ssh"
wget -q -O /usr/sbin/cek-ssh "$GH/admin/cek-ssh" && chmod +x /usr/sbin/cek-ssh && echo "  ✅ cek-ssh"
wget -q -O /usr/sbin/change-port "$GH/admin/change-port" && chmod +x /usr/sbin/change-port && echo "  ✅ change-port"

# WebSocket handler
wget -q -O /usr/local/bin/ws-ssh.py "$GH/admin/ws-ssh.py" && chmod +x /usr/local/bin/ws-ssh.py && echo "  ✅ ws-ssh.py"

# Web Admin Panel
wget -q -O /var/www/admin/index.php "$GH/admin/index.php" && echo "  ✅ Admin Panel"

# SSH Admin Wrapper
wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin && echo "  ✅ ssh-admin"

# Auto-Update
wget -q -O /usr/local/bin/ekrom-update "$GH/admin/auto-update.sh" && chmod +x /usr/local/bin/ekrom-update && echo "  ✅ auto-update"

# Create DB files
touch /etc/ssh/.ssh.db

# Setup ws-ssh service
cat > /etc/systemd/system/ws-ssh.service << 'SEOF'
[Unit]
Description=SSH WebSocket Proxy
After=network.target
[Service]
Type=simple
ExecStart=/usr/bin/python3 /usr/local/bin/ws-ssh.py
Restart=always
RestartSec=3
[Install]
WantedBy=multi-user.target
SEOF
systemctl daemon-reload
systemctl enable ws-ssh 2>/dev/null
systemctl restart ws-ssh 2>/dev/null
echo "  ✅ ws-ssh service"

# 4. Setup Nginx + Sudoers + Cron
echo -e "\n${GREEN}[4/4]⚙️ ตั้งค่าระบบ...${NC}"

# Nginx
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

# Restart
systemctl restart nginx
systemctl restart php*-fpm 2>/dev/null

# Cron auto-update
(crontab -l 2>/dev/null | grep -v ekrom-update; echo "0 */6 * * * /usr/local/bin/ekrom-update >/dev/null 2>&1") | crontab - 2>/dev/null

echo ""
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ ติดตั้งสำเร็จ!${NC}"
echo -e "${BLUE}══════════════════════════════════════${NC}"
echo ""
echo -e "  📝 เมนู:       ${GREEN}menu${NC}"
echo -e "  🌐 Web Admin:  ${GREEN}http://$IP:8888/${NC}"
echo -e "  🔑 รหัส:      ${GREEN}admin123${NC}"
echo -e "  🔄 Auto-Up:    ${GREEN}ทุก 6 ชม.${NC}"
echo ""
echo -e "  ${BLUE}ลองพิมพ์: menu${NC}"
echo ""
