#!/bin/bash
# setup_https_auto.sh — ทำ HTTPS อัตโนมัติ ง่ายที่สุด (ไม่ต้อง Cloudflare)
# ลูกค้ารันครั้งเดียว จบ ไม่ต้องสร้าง Cert เอง
# วิธีใช้:
#   sudo -i
#   curl -sL https://raw.githubusercontent.com/EkromSSH/VPN/main/setup_https_auto.sh -o s.sh
#   bash s.sh www.โดเมนของคุณ.com
set -e
DOMAIN="${1:?ใส่โดเมนด้วย เช่น bash s.sh www.example.com}"
WEBROOT="/var/www/shop"
EMAIL="admin@$DOMAIN"

echo ">>> ติดตั้ง nginx + certbot"
apt update -y && apt install -y nginx certbot
systemctl enable --now nginx
mkdir -p "$WEBROOT"

echo ">>> เปิดพอร์ต 80 ให้ certbot ขอ cert"
cat > /etc/nginx/conf.d/acme.conf <<NGINX
server {
    listen 80;
    server_name $DOMAIN;
    location /.well-known/acme-challenge/ { root $WEBROOT; }
    location / { return 301 https://\$host\$request_uri; }
}
NGINX
nginx -t && systemctl reload nginx

echo ">>> ขอ cert ฟรีจาก Let's Encrypt (อัตโนมัติ)"
certbot certonly --webroot -w "$WEBROOT" -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL"

echo ">>> เปิด HTTPS"
cat > /etc/nginx/conf.d/https.conf <<NGINX
server {
    listen 443 ssl;
    server_name $DOMAIN;
    ssl_certificate     /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    root $WEBROOT;
    index index.html index.php;
    location /uploads/ { alias $WEBROOT/uploads/; }
    location /.well-known/acme-challenge/ { root $WEBROOT; }
    location / { try_files \$uri \$uri/ /index.php?\$args; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; }
    location ~ /\.ht { deny all; }
}
NGINX
nginx -t && systemctl reload nginx

echo "🎉 เสร็จ! เปิด https://$DOMAIN (ไม่ต้อง Cloudflare)"
