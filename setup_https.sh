#!/bin/bash
# setup_https.sh — ทำ HTTPS พอร์ต 80+81+443 รองรับทุกโดเมน/IP (เครื่องลูกค้า)
# รัน: sudo -i  แล้ว  bash setup_https.sh
# จะถาม: ชื่อโดเมนเต็ม (เช่น www.idavpn.win)
# ไม่ต้องเพิ่ม TXT — ใช้ webroot พอร์ต 80 (เหมือน 133)

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

# 4) config nginx: พอร์ต 80 (ให้ LE เข้ามาทำ webroot) + 81 (redirect) + 443 (ssl)
cat > /etc/nginx/conf.d/all.conf <<NGINX
server {
    listen 80;
    server_name $DOMAIN;
    location /.well-known/acme-challenge/ { root /var/www/shop; }
    location / { return 301 https://\$host\$request_uri; }
}
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

nginx -t && systemctl reload nginx

# 5) ขอ cert ด้วย webroot (ไม่ต้อง TXT)
echo ">>> กำลังขอ cert ด้วย webroot พอร์ต 80..."
certbot certonly --webroot -w /var/www/shop -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL"

# 6) โหลด nginx ใหม่ (หลังได้ cert)
nginx -t && systemctl reload nginx

echo ""
echo "🎉 เสร็จ! เข้า https://$DOMAIN"
echo "💡 เพิ่มโดเมนใหม่: ชี้ DNS มา IP นี้ แล้วรัน certbot certonly --webroot -w /var/www/shop -d 'โดเมนใหม่' "
