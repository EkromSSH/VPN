#!/bin/bash
# เปลี่ยนพอร์ต SSH WebSocket (แก้ /etc/websocket/tun.conf + restart ws.service)
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
PORT="$1"

# ตรวจสอบพอร์ตที่รับมา
if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ -z "$PORT" ]; then
    echo "FAIL: พอร์ตไม่ถูกต้อง"
    exit 1
fi

TUN="/etc/websocket/tun.conf"

# อ่านพอร์ตปัจจุบันจาก tun.conf
CURRENT=$(grep -oP 'listen_port:\s*\K[0-9]+' "$TUN" 2>/dev/null | head -1)
if [ -z "$CURRENT" ]; then
    echo "FAIL: ไม่พบ listen_port ใน $TUN"
    exit 1
fi

# พอร์ตเดียวกัน → ไม่ต้องทำ
if [ "$CURRENT" = "$PORT" ]; then
    echo "OK $PORT (พอร์ตเดิม)"
    exit 0
fi

# ตรวจว่าพอร์ตใหม่ถูกใช้อยู่หรือยัง
if ss -tln | grep -q ":$PORT "; then
    echo "FAIL: พอร์ต $PORT ถูกใช้อยู่แล้ว"
    exit 1
fi

# แก้พอร์ตใน tun.conf
sed -i "s/listen_port:\s*$CURRENT/listen_port: $PORT/" "$TUN" 2>/dev/null

# restart ws.service (ผ่าน sudo — กัน polkit ปฏิเสธจาก PHP-FPM)
if command -v sudo >/dev/null 2>&1; then
    sudo systemctl restart ws 2>/dev/null
else
    systemctl restart ws 2>/dev/null
fi
sleep 2

# ตรวจผล
if systemctl is-active ws >/dev/null 2>&1 && ss -tln | grep -q ":$PORT "; then
    echo "OK $PORT"
else
    # ย้อนกลับถ้า fail
    sed -i "s/listen_port:\s*$PORT/listen_port: $CURRENT/" "$TUN" 2>/dev/null
    if command -v sudo >/dev/null 2>&1; then
        sudo systemctl restart ws 2>/dev/null
    else
        systemctl restart ws 2>/dev/null
    fi
    echo "FAIL: เปลี่ยนไม่สำเร็จ (ย้อนกลับ $CURRENT)"
    exit 1
fi
