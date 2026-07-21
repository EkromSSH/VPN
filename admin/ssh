#!/bin/bash
grenbo="\e[92;1m"
NC='\033[0m'
clear
echo -e "\033[1;93m+------------------------------------------+\033[0m"
echo -e "\033[1;93m|\033[42m            เมนูจัดการ SSH              \033[1;93m|\033[0m"
echo -e "\033[1;93m+------------------------------------------+\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}1.${NC} \033[0;36mสร้างบัญชี SSH OVPN\033[0m              \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}2.${NC} \033[0;36mลบบัญชี SSH OVPN\033[0m                \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}3.${NC} \033[0;36mต่ออายุบัญชี SSH OVPN\033[0m              \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}4.${NC} \033[0;36mตรวจสอบ SSH OVPN\033[0m                \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}5.${NC} \033[0;36mเปลี่ยนพอร์ต SSH WS\033[0m               \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}6.${NC} \033[0;36mติดตั้งเว็บ Panel\033[0m                \033[1;93m|\033[0m"
echo -e "\033[1;93m+------------------------------------------+\033[0m"
read -p "เลือกจากตัวเลือก [ 1 - 6 ] : " menu
case $menu in
1) add-ssh ;; 2) del-ssh ;; 3) renew-ssh ;; 4) cek-ssh ;;
5) change-port ;;
6) wget -qO- "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh" | bash; echo ""; read -n 1 -s -r -p "Press any key"; ssh ;;
*) ssh ;;
esac
