#!/bin/bash
grenbo="\e[92;1m"
NC='\033[0m'
clear
PORT=$(grep bind /usr/local/bin/ws-ssh.py 2>/dev/null | grep -oP "\d{4,5}" | head -1 || echo "?")
echo -e "\033[1;93m+--------------------------------------------------+\033[0m"
echo -e "\033[1;93m|\033[42m            เมนูจัดการ SSH          Port: $PORT     \033[1;93m|\033[0m"
echo -e "\033[1;93m+--------------------------------------------------+\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}1.${NC} \033[0;36mสร้างบัญชี SSH OVPN\033[0m                          \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}2.${NC} \033[0;36mลบบัญชี SSH OVPN\033[0m                            \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}3.${NC} \033[0;36mต่ออายุบัญชี SSH OVPN\033[0m                          \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}4.${NC} \033[0;36mตรวจสอบ SSH OVPN\033[0m                            \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}5.${NC} \033[0;36mเปลี่ยนพอร์ต SSH WS\033[0m                           \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}6.${NC} \033[0;36mติดตั้งเว็บ Panel\033[0m                            \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}7.${NC} \033[0;36mอัปเดตระบบ\033[0m                                  \033[1;93m|\033[0m"
echo -e "\033[1;93m+--------------------------------------------------+\033[0m"
read -p "เลือกจากตัวเลือก [ 1 - 7 ] : " menu
case $menu in
1) add-ssh ;; 2) del-ssh ;; 3) renew-ssh ;; 4) cek-ssh ;;
5) change-port ;;
6) wget -qO- "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh" | bash; echo ""; read -n 1 -s -r -p "Press any key"; ssh ;;
7) echo "🔄 กำลังอัปเดตระบบ..."; GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"; for f in menu ssh add-ssh del-ssh renew-ssh cek-ssh change-port; do wget -q -O /usr/sbin/$f "$GH/admin/$f" && chmod +x /usr/sbin/$f; done; wget -q -O /var/www/admin/index.php "$GH/admin/index.php"; wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin; echo "✅ อัปเดตเสร็จ!"; read -n 1 -s -r -p "Press any key"; menu ;;
*) ssh ;;
esac
