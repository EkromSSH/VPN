#!/bin/bash
# เปลี่ยนพอร์ต SSH WebSocket
# ใช้: bash change-ws-port.sh <พอร์ตใหม่>

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

# ตรวจสอบว่ามี ws-ssh.py หรือไม่
if [ -f /usr/local/bin/ws-ssh.py ]; then
    WS_FILE="/usr/local/bin/ws-ssh.py"
    SERVICE="ws-ssh"
elif [ -f /usr/local/bin/ws-python.py ]; then
    WS_FILE="/usr/local/bin/ws-python.py"
    SERVICE="ws-ssh"
else
    echo -e "${RED}❌ ไม่พบ WS handler (ws-ssh.py)${NC}"
    exit 1
fi

CURRENT_PORT=$(grep -oP 'bind\("0\.0\.0\.0", \K[0-9]+' "$WS_FILE" 2>/dev/null)

if [ -z "$1" ]; then
    echo -e "🔵 พอร์ตปัจจุบัน: ${GREEN}$CURRENT_PORT${NC}"
    read -p "➜ ใส่พอร์ตใหม่: " NEW_PORT
else
    NEW_PORT="$1"
fi

# ตรวจสอบว่าพอร์ตซ้ำ
if ss -tulpn | grep -q ":$NEW_PORT "; then
    echo -e "${RED}❌ พอร์ต $NEW_PORT มีคนใช้แล้ว${NC}"
    ss -tulpn | grep ":$NEW_PORT "
    exit 1
fi

# เปลี่ยนพอร์ต
sed -i "s/bind((\"0.0.0.0\", $CURRENT_PORT)/bind((\"0.0.0.0\", $NEW_PORT)/" "$WS_FILE"
systemctl restart "$SERVICE" 2>/dev/null
sleep 1

if systemctl is-active "$SERVICE" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ เปลี่ยนพอร์ต $CURRENT_PORT → $NEW_PORT สำเร็จ${NC}"
    echo -e "📌 ใช้: ssh -p $NEW_PORT aa@ip"
else
    echo -e "${RED}❌ เปลี่ยนไม่สำเร็จ กำลังย้อนกลับ${NC}"
    sed -i "s/bind((\"0.0.0.0\", $NEW_PORT)/bind((\"0.0.0.0\", $CURRENT_PORT)/" "$WS_FILE"
    systemctl restart "$SERVICE" 2>/dev/null
fi
