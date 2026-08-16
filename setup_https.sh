#!/bin/bash
# setup_https.sh — ทำ HTTPS พอร์ต 81+443 รองรับทุกโดเมน/IP (เครื่องลูกค้า 62)
# รัน: sudo -i แล้ว  bash setup_https.sh "idavpn.win" "admin@idavpn.win"
set -e
DOMAIN="${1:-idavpn.win}"
EMAIL="${2:-admin@$DOMAIN}"
WWW="www.$DOMAIN"

# 1) ติดตั้ง
apt update -y && apt install -y nginx certbot
systemctl enable --now nginx

# 2) โฟลเดอร์เว็บ
mkdir -p /var/www/shop

# 3) config nginx พอร์ต 81(redirect) + 443(ssl) รองรับทุกโดเมน
cat > /etc/nginx/conf.d/all.conf <<NGINX
server {
    listen 81 default_server;
    listen [::]:81 default_server;
    server_name _;
    root /var/www/shop;
    index index.html index.php;
    location /.well-known/acme-challenge/ { root /var/www/shop; }
    location / { return 301 https://\$host\$request_uri; }
}
server {
    listen 443 ssl default_server;
    listen [::]:443 ssl default_server;
    server_name _;
    ssl_certificate     /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    root /var/www/shop;
    index index.html index.php;
    location / { try_files \$uri \$uri/ /index.php?\$args; }
}
NGINX

# เช็คพอร์ต 443 ว่าง (เตือนถ้า xray กินอยู่)
if ss -tlnp 2>/dev/null | grep -q ":443 "; then
  echo "⚠️ พอร์ต 443 ถูกใช้งานอยู่ (อาจเป็น xray) — ย้าย xray ก่อนรัน nginx"
fi

nginx -t && systemctl reload nginx

# 4) ขอ cert (webroot ผ่านพอร์ต 81)
certbot --webroot -w /var/www/shop -d "$WWW" -d "$DOMAIN" \
  --non-interactive --agree-tos -m "$EMAIL" --redirect || \
certbot certonly --webroot -w /var/www/shop -d "$WWW" -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL"

# 5) โหลด nginx ใหม่ (หลังได้ cert)
nginx -t && systemctl reload nginx

echo "🎉 เสร็จ! เข้า https://$WWW (พอร์ต 443)"
echo "💡 ถ้าเพิ่มโดเมนใหม่: ชี้ DNS มา IP นี้ แล้วรัน certbot เลย"
