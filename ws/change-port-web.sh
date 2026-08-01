#!/bin/bash
# เปลี่ยนพอร์ต SSH WS (public port) — แก้ haproxy.cfg + restart haproxy
# websocket internal (8080) คงเดิม
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
PORT="$1"

if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ -z "$PORT" ]; then
    echo "FAIL: พอร์ตไม่ถูกต้อง"
    exit 1
fi
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "FAIL: พอร์ตต้องอยู่ระหว่าง 1-65535"
    exit 1
fi

CFG="/etc/haproxy/haproxy.cfg"

# อ่านพอร์ตปัจจุบัน (bind *:PORT ใน frontend ssh_ws)
CURRENT=$(grep -oP 'bind \*:\K[0-9]+' "$CFG" 2>/dev/null | head -1)
if [ -z "$CURRENT" ]; then
    echo "FAIL: ไม่พบ bind ใน $CFG"
    exit 1
fi

if [ "$CURRENT" = "$PORT" ]; then
    echo "OK $PORT (พอร์ตเดิม)"
    exit 0
fi

# ตรวจพอร์ตใหม่ถูกใช้อยู่หรือยัง (ยกเว้น haproxy เอง)
if ss -tln | grep -q ":$PORT " && ! ss -tlnp | grep ":$PORT " | grep -q haproxy; then
    echo "FAIL: พอร์ต $PORT ถูกใช้อยู่แล้ว"
    exit 1
fi

# แก้พอร์ตใน haproxy.cfg
sed -i "s/bind \*:$CURRENT/bind *:$PORT/" "$CFG" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "FAIL: แก้ config ไม่สำเร็จ"
    exit 1
fi

# restart haproxy
if command -v sudo >/dev/null 2>&1; then
    sudo -n systemctl restart haproxy 2>/dev/null
else
    systemctl restart haproxy 2>/dev/null
fi
sleep 2

# ตรวจผล
if systemctl is-active haproxy >/dev/null 2>&1 && ss -tln | grep -q ":$PORT "; then
    echo "OK $PORT"
else
    # ย้อนกลับ
    sed -i "s/bind \*:$PORT/bind *:$CURRENT/" "$CFG" 2>/dev/null
    if command -v sudo >/dev/null 2>&1; then
        sudo -n systemctl restart haproxy 2>/dev/null
    else
        systemctl restart haproxy 2>/dev/null
    fi
    echo "FAIL: เปลี่ยนไม่สำเร็จ (ย้อนกลับ $CURRENT)"
    exit 1
fi
