#!/bin/bash
# เปลี่ยนพอร์ต SSH WS (public port) v5 — 80/พอร์ต public = nginx (path-based)
# แก้ listen ใน /etc/nginx/conf.d/ssh-ws-80.conf + reload nginx
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
PORT="$1"

if ! [[ "$PORT" =~ ^[0-9]+$ ]]; then
    echo "FAIL: พอร์ตไม่ถูกต้อง"
    exit 1
fi
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "FAIL: พอร์ตต้องอยู่ระหว่าง 1-65535"
    exit 1
fi

NGINX_80="/etc/nginx/conf.d/ssh-ws-80.conf"
TUN_CFG="/etc/websocket/tun.conf"
WS_INT=$(grep -oP 'listen_port:\s*\K[0-9]+' "$TUN_CFG" 2>/dev/null | head -1)
[ -z "$WS_INT" ] && WS_INT=8081

# ── อ่านพอร์ต public ปัจจุบัน ──
PUBLIC_PORT=$(grep -oP 'listen \K[0-9]+(?=;)' "$NGINX_80" 2>/dev/null | head -1)
[ -z "$PUBLIC_PORT" ] && PUBLIC_PORT=80

# ── ตรวจพอร์ตเป้าหมายถูกใช้อยู่หรือยัง ──
if ss -tln | grep -q ":$PORT "; then
    # ถ้าถูก websocket เองใช้ (internal) → ย้าย internal ได้
    if ss -tlnp 2>/dev/null | grep ":$PORT " | grep -q websocket && [ "$PORT" = "$WS_INT" ]; then
        # หาพอร์ต internal ใหม่
        NEW_INT=$((WS_INT + 1))
        while ss -tln | grep -q ":$NEW_INT "; do NEW_INT=$((NEW_INT + 1)); done
        sed -i "s/listen_port:\s*$WS_INT/listen_port: $NEW_INT/" "$TUN_CFG" 2>/dev/null
        sed -i "s|127.0.0.1:$WS_INT|127.0.0.1:$NEW_INT|g" "$NGINX_80" 2>/dev/null
        sed -i "s|127.0.0.1:$WS_INT|127.0.0.1:$NEW_INT|g" /etc/nginx/conf.d/xray.conf 2>/dev/null
        sed -i "s|127.0.0.1:$WS_INT|127.0.0.1:$NEW_INT|g" /etc/haproxy/haproxy.cfg 2>/dev/null
        # เปลี่ยน listen
        sed -i "s/listen $PUBLIC_PORT;/listen $PORT;/" "$NGINX_80" 2>/dev/null
        systemctl restart ws
        nginx -t >/dev/null 2>&1 && systemctl reload nginx
        if ss -tln | grep -q ":$PORT " && ss -tln | grep -q ":$NEW_INT "; then
            echo "OK $PORT (websocket ภายในย้ายไป $NEW_INT)"
            exit 0
        fi
        # rollback
        sed -i "s/listen $PORT;/listen $PUBLIC_PORT;/" "$NGINX_80" 2>/dev/null
        sed -i "s/listen_port:\s*$NEW_INT/listen_port: $WS_INT/" "$TUN_CFG" 2>/dev/null
        echo "FAIL: เปลี่ยนไม่สำเร็จ (ย้อนกลับแล้ว)"
        exit 1
    else
        echo "FAIL: พอร์ต $PORT ถูกใช้อยู่"
        exit 1
    fi
fi

# ── ปกติ: เปลี่ยน listen อย่างเดียว (IPv4 + IPv6) ──
sed -i "s/listen $PUBLIC_PORT;/listen $PORT;/" "$NGINX_80" 2>/dev/null
sed -i "s/listen \[::\]:$PUBLIC_PORT;/listen [::]:$PORT;/" "$NGINX_80" 2>/dev/null
if nginx -t 2>&1; then
    systemctl reload nginx
    sleep 2
    if ss -tln | grep -q ":$PORT "; then
        echo "OK $PORT"
        exit 0
    fi
else
    echo "NGINXT_FAIL"
fi
# rollback
sed -i "s/listen $PORT;/listen $PUBLIC_PORT;/" "$NGINX_80" 2>/dev/null
sed -i "s/listen \[::\]:$PORT;/listen [::]:$PUBLIC_PORT;/" "$NGINX_80" 2>/dev/null
systemctl reload nginx 2>/dev/null
echo "FAIL: เปลี่ยนไม่สำเร็จ"
exit 1
