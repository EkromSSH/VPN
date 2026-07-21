#!/bin/bash
# SSH Management Menu - EkromSSH VPN
clear
grenbo="\e[92;1m"
NC='\033[0m'
echo -e "\033[1;93m┌──────────────────────────────────────────┐\033[0m"
echo -e "\033[1;93m│$NC\033[42m            เมนูจัดการ SSH              $NC"
echo -e "\033[1;93m└──────────────────────────────────────────┘\033[0m"
echo -e "\033[1;93m┌──────────────────────────────────────────┐\033[0m"
echo -e "\033[1;93m│  ${grenbo}1.${NC} \033[0;36mสร้างบัญชี SSH OVPN${NC}"
echo -e "\033[1;93m│  ${grenbo}2.${NC} \033[0;36mลบบัญชี SSH OVPN${NC}"
echo -e "\033[1;93m│  ${grenbo}3.${NC} \033[0;36mต่ออายุบัญชี SSH OVPN${NC}"
echo -e "\033[1;93m│  ${grenbo}4.${NC} \033[0;36mตรวจสอบ SSH OVPN${NC}"
echo -e "\033[1;93m│  ${grenbo}5.${NC} \033[0;36mเปลี่ยนพอร์ต SSH WS${NC}"
echo -e "\033[1;93m│  ${grenbo}6.${NC} \033[0;36mติดตั้งเว็บ Panel${NC}"
echo -e "\033[1;93m└──────────────────────────────────────────┘\033[0m"
echo -e ""
read -p "เลือกจากตัวเลือก [ 1 - 6 ] : " menu
case $menu in
1) add-ssh ;;
2) del-ssh ;;
3) renew-ssh ;;
4) cek-ssh ;;
5) change-port ;;
6) wget -qO- "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh" | bash ;;
*) ssh ;;
esac