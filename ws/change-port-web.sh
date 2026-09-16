#!/bin/bash
# EKROM SSH VPN - Script เปลี่ยนพอร์ต SSH WebSocket (Public Port)
# รองรับพอร์ต 80 (แชร์กับ Shop ด้วย default_server), 8080, 8880 และพอร์ตอื่นๆ 1-65535
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
PORT="$1"

if ! [[ "$PORT" =~ ^[0-9]+$ ]]; then
    echo "FAIL: พอร์ตไม่ถูกต้อง (ต้องเป็นตัวเลข)"
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
PUBLIC_PORT=$(grep -oP 'listen\s+\K[0-9]+' "$NGINX_80" 2>/dev/null | head -1)
[ -z "$PUBLIC_PORT" ] && PUBLIC_PORT=80

if [ "$PORT" = "$PUBLIC_PORT" ]; then
    echo "OK $PORT (ใช้งานพอร์ตนี้อยู่แล้ว)"
    exit 0
fi

# ── ตรวจสอบพอร์ตที่ห้ามใช้เด็ดขาด (พอร์ตระบบของ VPS) ──
case "$PORT" in
    8888|8889)
        echo "FAIL: พอร์ต $PORT เป็นพอร์ตของแผงควบคุมระบบ (Admin Panel)"
        exit 1
        ;;
    443)
        echo "FAIL: พอร์ต 443 ใช้งานโดยระบบ SSL/TLS (HTTPS) อยู่แล้ว"
        exit 1
        ;;
    18020|10030|10040|1000[0-9]|10010)
        echo "FAIL: พอร์ต $PORT เป็นพอร์ตภายในของระบบ Xray"
        exit 1
        ;;
    8000)
        echo "FAIL: พอร์ต 8000 เป็นพอร์ตระบบร้านค้า (Backend Service)"
        exit 1
        ;;
esac

# ── ตรวจสอบกรณีชนกับ WebSocket ภายใน (Internal Port เช่น 8081) ──
if [ "$PORT" = "$WS_INT" ] || (ss -tlnp 2>/dev/null | grep -E "[: ]$PORT\s+" | grep -q "websocket"); then
    NEW_INT=$((WS_INT + 1))
    while ss -tln | grep -q ":$NEW_INT "; do NEW_INT=$((NEW_INT + 1)); done
    sed -i "s/listen_port:\s*$WS_INT/listen_port: $NEW_INT/" "$TUN_CFG" 2>/dev/null
    sed -i "s|127.0.0.1:$WS_INT|127.0.0.1:$NEW_INT|g" "$NGINX_80" 2>/dev/null
    sed -i "s|127.0.0.1:$WS_INT|127.0.0.1:$NEW_INT|g" /etc/nginx/conf.d/xray.conf 2>/dev/null
    sed -i "s|127.0.0.1:$WS_INT|127.0.0.1:$NEW_INT|g" /etc/haproxy/haproxy.cfg 2>/dev/null
    systemctl restart ws
    WS_INT=$NEW_INT
fi

# ── ตรวจสอบกรณีพอร์ตถูกโปรแกรมอื่นที่ *ไม่ใช่ Nginx* ใช้งานอยู่ ──
NON_NGINX_PROC=$(ss -tlnp 2>/dev/null | grep -E "[: ]$PORT\s+" | grep -v 'users:(("nginx"' | head -1)
if [ -n "$NON_NGINX_PROC" ]; then
    PROC_NAME=$(echo "$NON_NGINX_PROC" | grep -oP 'users:\(\("\K[^"]+' | head -1)
    [ -z "$PROC_NAME" ] && PROC_NAME="โปรแกรมอื่น"
    echo "FAIL: พอร์ต $PORT ถูกโปรแกรม '$PROC_NAME' ใช้งานอยู่ ไม่สามารถใช้พอร์ตนี้ได้"
    exit 1
fi

# ── ตรวจสอบกรณี Nginx ใช้พอร์ตนี้ในไฟล์คอนฟิกอื่น (ยกเว้นพอร์ต 80) ──
if [ "$PORT" != "80" ]; then
    NGINX_OTHER=$(grep -rnE "listen\s+.*[: ]$PORT(\s|;)" /etc/nginx/conf.d/ 2>/dev/null | grep -v "ssh-ws-80.conf")
    if [ -n "$NGINX_OTHER" ]; then
        echo "FAIL: พอร์ต $PORT มีการใช้งานอยู่ในระบบ Nginx ส่วนอื่นแล้ว"
        exit 1
    fi
fi

# ── สำรองไฟล์เดิมไว้ก่อน ──
cp "$NGINX_80" /tmp/ssh-ws-80.conf.bak 2>/dev/null

# ── แก้ไข listen ใน ssh-ws-80.conf (ใช้ default_server เพื่อดักจับทุกการเชื่อมต่อ) ──
sed -i -E "s/listen\s+[0-9]+(\s+default_server)?;/listen $PORT default_server;/" "$NGINX_80"
sed -i -E "s/listen\s+\[::\]:[0-9]+(\s+default_server)?;/listen [::]:$PORT default_server;/" "$NGINX_80"

# ── ทดสอบ Nginx Syntax & Reload ──
if nginx -t >/tmp/nginx_test.log 2>&1; then
    systemctl reload nginx
    sleep 1
    if ss -tln | grep -q ":$PORT "; then
        echo "OK $PORT"
        exit 0
    fi
fi

# ── กรณีไม่ผ่าน ให้ Rollback กลับไปค่าเดิม ──
cp /tmp/ssh-ws-80.conf.bak "$NGINX_80" 2>/dev/null
systemctl reload nginx 2>/dev/null
ERR=$(cat /tmp/nginx_test.log 2>/dev/null | head -2)
echo "FAIL: เปลี่ยนไม่สำเร็จ ($ERR)"
exit 1
