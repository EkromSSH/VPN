#!/bin/bash
# setup_cf_origin.sh — ติดตั้ง Cloudflare Origin Certificate (สำหรับลูกค้า ไม่ต้อง AI)
# วิธีใช้:
#   1) สร้าง Origin Cert ใน CF (SSL/TLS > Origin Server > Create)
#   2) เซฟเป็นไฟล์ 2 อันในโฟลเดอร์เดียวกันกับสคริปต์นี้:
#        cert.pem  (วาง Origin Certificate)
#        key.pem   (วาง Private Key)
#   3) รัน:  bash setup_cf_origin.sh www.โดเมนของคุณ.com
# สคริปต์อ่านจากไฟล์ (ไม่ต้องวางในจอ) เซ็ต nginx ให้จบ

set -e
DOMAIN="${1:-www.idavpn.win}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CERT_FILE="$SCRIPT_DIR/cert.pem"
KEY_FILE="$SCRIPT_DIR/key.pem"
WEBROOT="/var/www/shop"

if [ ! -f "$CERT_FILE" ]; then
  echo "❌ หาไฟล์ cert.pem ไม่เจอ — วาง Origin Certificate ลงไฟล์ชื่อ cert.pem ก่อน"
  exit 1
fi
if [ ! -f "$KEY_FILE" ]; then
  echo "❌ หาไฟล์ key.pem ไม่เจอ — วาง Private Key ลงไฟล์ชื่อ key.pem ก่อน"
  exit 1
fi

mkdir -p /etc/ssl
cp "$CERT_FILE" /etc/ssl/cf-origin.pem
cp "$KEY_FILE" /etc/ssl/cf-origin.key
chmod 600 /etc/ssl/cf-origin.key
chmod 644 /etc/ssl/cf-origin.pem

mkdir -p "$WEBROOT"

cat > /etc/nginx/conf.d/https.conf <<NGINX
server {
    listen 80;
    server_name $DOMAIN;
    location /.well-known/acme-challenge/ { root $WEBROOT; }
    location / { return 301 https://\$host\$request_uri; }
}
server {
    listen 443 ssl;
    server_name $DOMAIN;
    ssl_certificate     /etc/ssl/cf-origin.pem;
    ssl_certificate_key /etc/ssl/cf-origin.key;
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
echo "🎉 เสร็จ! ตั้ง CF SSL = Full (strict) แล้วเปิด https://$DOMAIN"
