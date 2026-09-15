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

echo ">>> เปิด HTTPS และตั้งค่า Reverse Proxy ไปยัง EKROM Shop"
# ปิด default site ถ้ามีเพื่อไม่ให้ชนพอร์ต
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

cat > /etc/nginx/conf.d/https.conf <<NGINX
server {
    listen 443 ssl http2;
    server_name $DOMAIN;

    ssl_certificate     /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    client_max_body_size 100M;

    # Let's Encrypt renewal
    location /.well-known/acme-challenge/ {
        root $WEBROOT;
    }

    # ป้องกันไฟล์สำคัญ
    location ~* (\.sqlite|\.db|\.git|\.env|\.sh|README\.md)$ {
        return 404;
    }
    location ~ /\. {
        return 404;
    }

    # ส่งต่อไปยังเว็บร้านค้า EKROM Shop (พอร์ต 8000)
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}
NGINX

# เปิด Firewall พอร์ต 80, 443
if command -v ufw >/dev/null 2>&1; then
    ufw allow 80/tcp >/dev/null 2>&1 || true
    ufw allow 443/tcp >/dev/null 2>&1 || true
fi

nginx -t && systemctl reload nginx

echo "🎉 สำเร็จ! เว็บไซต์ของคุณเปิดใช้งาน HTTPS เรียบร้อยแล้ว: https://$DOMAIN"
