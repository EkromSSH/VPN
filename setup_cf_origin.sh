#!/bin/bash
# setup_cf_origin.sh — ติดตั้ง Cloudflare Origin Certificate (วิธี 3)
# วิธีใช้:
#   1) สร้าง Origin Cert ใน CF (SSL/TLS > Origin Server > Create Certificate)
#      Hostnames: idavpn.win, www.idavpn.win | Validity: 15 years | RSA 2048
#   2) รัน:  bash setup_cf_origin.sh
#   3) วาง Origin Certificate (กล่องบน) -> Enter -> วาง Private Key (กล่องล่าง) -> Ctrl+D
# สคริปต์จะเซ็ต nginx + reload ให้ จบในรันเดียว

set -e

DOMAIN="${1:-www.idavpn.win}"
WEBROOT="/var/www/shop"

echo ">>> วาง Origin Certificate (รวม -----BEGIN/END CERTIFICATE-----) แล้วกด Enter:"
CERT=$(cat)
echo ">>> วาง Private Key (รวม [REDACTED PRIVATE KEY]/END) แล้วกด Ctrl+D:"
KEY=$(cat)

echo "$CERT" > /etc/ssl/cf-origin.pem
echo "$KEY"  > /etc/ssl/cf-origin.key
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
echo "🎉 เสร็จ! ตั้ง CF SSL mode = Full (strict) แล้วเปิด https://$DOMAIN"
