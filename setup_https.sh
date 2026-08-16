#!/bin/bash
# setup_https.sh — ทำ HTTPS พอร์ต 81+443 รองรับทุกโดเมน/IP (เครื่องลูกค้า)
# รัน: sudo -i  แล้ว  bash setup_https.sh
# จะถาม: ชื่อโดเมนเต็ม (เช่น www.idavpn.win หรือ vpn.idavpn.win หรือ idavpn.win)

# 1) ถามโดเมน (รับค่าเต็ม ไม่เติมอะไรข้างหน้า)
read -p "ใส่โดเมนเต็ม (เช่น www.idavpn.win): " DOMAIN
if [ -z "$DOMAIN" ]; then
  echo "❌ ต้องใส่โดเมน"
  exit 1
fi

# 2) ถามอีเมล
read -p "ใส่อีเมลรับแจ้งเตือน cert (เช่น admin@$DOMAIN): " EMAIL
if [ -z "$EMAIL" ]; then
  EMAIL="admin@$DOMAIN"
  echo "ใช้ค่าเริ่มต้น: $EMAIL"
fi

# 3) ติดตั้ง
apt update -y && apt install -y nginx certbot
systemctl enable --now nginx
mkdir -p /var/www/shop

# 4) config nginx พอร์ต 81(redirect) + 443(ssl) รองรับทุกโดเมน
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

if ss -tlnp 2>/dev/null | grep -q ":443 "; then
  echo "⚠️ พอร์ต 443 ถูกใช้งานอยู่ (อาจเป็น xray) — ย้าย xray ก่อนรัน nginx"
fi

nginx -t && systemctl reload nginx

# 5) ขอ cert ด้วย DNS challenge
echo ">>> กำลังขอ cert ด้วย DNS challenge สำหรับ: $DOMAIN"
echo ">>> certbot จะให้เพิ่ม TXT record '_acme-challenge.$DOMAIN' ใน DNS"
echo ">>> เพิ่มเสร็จแล้วรอ 2-3 นาที ค่อยกด Enter"
certbot certonly --manual --preferred-challenges dns -d "$DOMAIN" --agree-tos -m "$EMAIL"

# 6) โหลด nginx ใหม่
nginx -t && systemctl reload nginx

echo ""
echo "🎉 เสร็จ! เข้า https://$DOMAIN"
echo "💡 เพิ่มโดเมนใหม่: ชี้ DNS มา IP นี้ แล้วรัน certbot certonly --manual --preferred-challenges dns -d 'โดเมนใหม่' "
