#!/bin/bash
grenbo="\e[92;1m"
NC='\033[0m'
clear
echo -e "\033[1;93m┌──────────────────────────────────────────┐\033[0m"
echo -e "\033[1;93m│$NC\033[42m            เมนูจัดการ SSH              $NC\033[1;93m│\033[0m"
echo -e "\033[1;93m└──────────────────────────────────────────┘\033[0m"
echo -e "\033[1;93m┌──────────────────────────────────────────┐\033[0m"
echo -e "\033[1;93m│  ${grenbo}1.${NC} \033[0;36mสร้างบัญชี SSH OVPN${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m│  ${grenbo}2.${NC} \033[0;36mลบบัญชี SSH OVPN${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m│  ${grenbo}3.${NC} \033[0;36mต่ออายุบัญชี SSH OVPN${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m│  ${grenbo}4.${NC} \033[0;36mตรวจสอบ SSH OVPN${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m│  ${grenbo}5.${NC} \033[0;36mเปลี่ยนพอร์ต SSH WS${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m│  ${grenbo}6.${NC} \033[0;36mติดตั้งเว็บ Panel${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m│  ${grenbo}7.${NC} \033[0;36mอัปเดตระบบ${NC}\033[1;93m│\033[0m"
echo -e "\033[1;93m└──────────────────────────────────────────┘\033[0m"
echo -e ""
read -p "เลือกจากตัวเลือก [ 1 - 7 ] : " menu
case $menu in
1) add-ssh ;;
2) del-ssh ;;
3) renew-ssh ;;
4) cek-ssh ;;
5) change-port ;;
6) wget -qO- "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh" | bash ;;
7) echo "🔄 กำลังอัปเดตระบบ..."; GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"; for f in menu ssh add-ssh del-ssh renew-ssh cek-ssh change-port; do wget -q -O /usr/sbin/$f "$GH/admin/$f" && chmod +x /usr/sbin/$f; done; wget -q -O /var/www/admin/index.php "$GH/admin/index.php"; wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin; echo "✅ อัปเดตเสร็จ!"; read -n 1 -s -r -p "Press any key"; ssh ;;
*) ssh ;;
esac
