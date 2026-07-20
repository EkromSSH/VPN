#!/bin/bash
# เปลี่ยนพอร์ต SSH WebSocket
RED='\033[0;31m'; GREEN='\033[0;32m'; NC='\033[0m'

CURRENT=$(grep -oP 'bind\("0\.0\.0\.0", \K[0-9]+' /usr/local/bin/ws-ssh.py 2>/dev/null || echo "8080")
echo -e "🔵 พอร์ต SSH WS ปัจจุบัน: ${GREEN}${CURRENT}${NC}"
read -p "➜ กรุณากรอกพอร์ตใหม่ (Enter = ยกเลิก): " NEW_PORT
[[ -z "$NEW_PORT" ]] && { echo -e "${RED}❌ ยกเลิก${NC}"; exit 0; }

if ss -tulpn | grep -q ":${NEW_PORT} " 2>/dev/null; then
    echo -e "${RED}❌ พอร์ต ${NEW_PORT} ถูกใช้อยู่แล้ว${NC}"
    exit 1
fi

sed -i "s/bind((\"0.0.0.0\", ${CURRENT})/bind((\"0.0.0.0\", ${NEW_PORT})/" /usr/local/bin/ws-ssh.py 2>/dev/null
systemctl restart ws-ssh 2>/dev/null
sleep 1

if systemctl is-active ws-ssh >/dev/null 2>&1; then
    echo -e "${GREEN}✅ เปลี่ยนพอร์ต ${CURRENT} → ${NEW_PORT} สำเร็จ${NC}"
    echo -e "📌 ใช้: ssh -p ${NEW_PORT} aa@$(curl -s ipv4.icanhazip.com)"
else
    echo -e "${RED}❌ เปลี่ยนไม่สำเร็จ กำลังย้อนกลับ${NC}"
    sed -i "s/bind((\"0.0.0.0\", ${NEW_PORT})/bind((\"0.0.0.0\", ${CURRENT})/" /usr/local/bin/ws-ssh.py 2>/dev/null
    systemctl restart ws-ssh 2>/dev/null
fi
