#!/bin/bash
# EkromSSH VPN - Full Installer (Xray + Menu + Web Admin)
# ใช้: bash <(curl -s https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/full-install.sh)

GREEN='\033[0;32m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"
IP=$(curl -s ipv4.icanhazip.com)

echo -e "${BLUE}══════════════════════════════════════════${NC}"
echo -e "${GREEN}     EKROMSSH VPN - Full Installer${NC}"
echo -e "${BLUE}══════════════════════════════════════════${NC}"
[[ $EUID -ne 0 ]] && { echo -e "${RED}❌ ต้องรัน root${NC}"; exit 1; }

# 1. Base packages
echo -e "\n${GREEN}[1/8]📦 ติดตั้งแพ็กเกจ...${NC}"
apt update -y
apt install -y nginx php-fpm php-cli curl sudo jq uuid-runtime wget screen netcat-openbsd python3

# 2. Install Xray
echo -e "\n${GREEN}[2/7]📥 ติดตั้ง Xray...${NC}"
bash -c "$(curl -L https://github.com/XTLS/Xray-install/raw/main/install-release.sh)" @ install 2>/dev/null
echo "✅ Xray installed"

# 4. Generate SSL
echo -e "\n${GREEN}[3/7]🔐 สร้าง SSL...${NC}"
apt install -y certbot python3-certbot-nginx 2>/dev/null
certbot certonly --standalone -d "$DOMAIN" --non-interactive --agree-tos --email admin@$DOMAIN 2>/dev/null || true
# Self-signed fallback
if [[ ! -f /etc/xray/xray.key ]]; then
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/xray/xray.key -out /etc/xray/xray.crt \
        -subj "/CN=$DOMAIN" 2>/dev/null
    echo "  ✅ Self-signed SSL"
fi

# 5. Download all scripts
echo -e "\n${GREEN}[4/7]📥 ดาวน์โหลดสคริปต์...${NC}"
for f in menu ssh add-ssh del-ssh renew-ssh cek-ssh change-port; do
    wget -q -O /usr/sbin/$f "$GH/admin/$f" && chmod +x /usr/sbin/$f && echo "  ✅ $f"
done
wget -q -O /usr/local/bin/ws-ssh.py "$GH/admin/ws-ssh.py" && chmod +x /usr/local/bin/ws-ssh.py && echo "  ✅ ws-ssh.py"
wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin && echo "  ✅ ssh-admin"

# 6. Web Admin Panel
echo -e "\n${GREEN}[5/7]🌐 ติดตั้ง Web Admin...${NC}"
mkdir -p /var/www/admin
wget -q -O /var/www/admin/index.php "$GH/admin/index.php" && echo "  ✅ Admin Panel"

# 7. Setup services
echo -e "\n${GREEN}[6/7]⚙️ ตั้งค่าบริการ...${NC}"
touch /etc/ssh/.ssh.db /etc/vmess/.vmess.db /etc/vless/.vless.db

# ws-ssh service
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

# Xray config
cat > /etc/xray/config.json << 'XEOF'
{
  "log": {"access": "/var/log/xray/access.log","error": "/var/log/xray/error.log","loglevel": "warning"},
  "inbounds": [
    {"port": 10000,"protocol": "vmess","settings": {"clients": []},"tag": "vmess-ws"},
    {"port": 10001,"protocol": "vless","settings": {"clients": []},"tag": "vless-ws"}
  ],
  "outbounds": [{"protocol": "freedom","tag": "direct"}]
}
XEOF
systemctl restart xray 2>/dev/null

# Nginx
PHP_SOCK=$(ls /var/run/php/php*-fpm.sock 2>/dev/null | head -1)
SOCK_NAME=$(basename "$PHP_SOCK" 2>/dev/null || echo "php$(php -r 'echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;')-fpm.sock")
cat > /etc/nginx/conf.d/admin.conf << NGINX
server { listen 8888; root /var/www/admin; index index.php;
    location / { try_files \$uri \$uri/ /index.php?\$args; }
    location ~ \.php\$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/var/run/php/$SOCK_NAME; }
}
NGINX
systemctl restart nginx

# Sudoers
echo "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/ssh-admin" > /etc/sudoers.d/ssh-admin
chmod 440 /etc/sudoers.d/ssh-admin

# 8. Auto-Update
echo -e "\n${GREEN}[7/7]🔄 Auto-Update...${NC}"
wget -q -O /usr/local/bin/ekrom-update "$GH/admin/auto-update.sh" && chmod +x /usr/local/bin/ekrom-update
(crontab -l 2>/dev/null | grep -v ekrom-update; echo "0 */6 * * * /usr/local/bin/ekrom-update >/dev/null 2>&1") | crontab -

echo ""
echo -e "${BLUE}══════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ ติดตั้งสำเร็จ!${NC}"
echo -e "${BLUE}══════════════════════════════════════════${NC}"
echo ""
echo -e "  📝 เมนู:       ${GREEN}menu${NC}"
echo -e "  🌐 Web Admin:  ${GREEN}http://$IP:8888/${NC}"
echo -e "  🔑 รหัส:      ${GREEN}admin123${NC}"
echo -e "  🔄 Auto-Up:   ${GREEN}ทุก 6 ชม.${NC}"
echo ""
