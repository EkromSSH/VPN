#!/bin/bash
grenbo="\e[92;1m"
NC='\033[0m'
clear
echo -e "\033[1;93m+------------------------------------------+\033[0m"
echo -e "\033[1;93m|\033[42m            \u0e40\u0e21\u0e19\u0e39\u0e08\u0e31\u0e14\u0e01\u0e32\u0e23 SSH              \033[1;93m|\033[0m"
echo -e "\033[1;93m+------------------------------------------+\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}1.${NC} \033[0;36m\u0e2a\u0e23\u0e49\u0e32\u0e07\u0e1a\u0e31\u0e0d\u0e0a\u0e35 SSH OVPN\033[0m              \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}2.${NC} \033[0;36m\u0e25\u0e1a\u0e1a\u0e31\u0e0d\u0e0a\u0e35 SSH OVPN\033[0m                \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}3.${NC} \033[0;36m\u0e15\u0e48\u0e2d\u0e2d\u0e32\u0e22\u0e38\u0e1a\u0e31\u0e0d\u0e0a\u0e35 SSH OVPN\033[0m              \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}4.${NC} \033[0;36m\u0e15\u0e23\u0e27\u0e08\u0e2a\u0e2d\u0e1a SSH OVPN\033[0m                \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}5.${NC} \033[0;36m\u0e40\u0e1b\u0e25\u0e35\u0e48\u0e22\u0e19\u0e1e\u0e2d\u0e23\u0e4c\u0e15 SSH WS\033[0m               \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}6.${NC} \033[0;36m\u0e15\u0e34\u0e14\u0e15\u0e31\u0e49\u0e07\u0e40\u0e27\u0e47\u0e1a Panel\033[0m                \033[1;93m|\033[0m"
echo -e "\033[1;93m|\033[0m  ${grenbo}7.${NC} \033[0;36m\u0e2d\u0e31\u0e1b\u0e40\u0e14\u0e15\u0e23\u0e30\u0e1a\u0e1a\033[0m                      \033[1;93m|\033[0m"
echo -e "\033[1;93m+------------------------------------------+\033[0m"
read -p "\u0e40\u0e25\u0e37\u0e2d\u0e01\u0e08\u0e32\u0e01\u0e15\u0e31\u0e27\u0e40\u0e25\u0e37\u0e2d\u0e01 [ 1 - 7 ] : " menu
case $menu in
1) add-ssh ;; 2) del-ssh ;; 3) renew-ssh ;; 4) cek-ssh ;;
5) change-port ;;
6) wget -qO- "https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/install.sh" | bash; echo ""; read -n 1 -s -r -p "Press any key"; ssh ;;
7) echo "\u0e2d\u0e31\u0e1b\u0e40\u0e14\u0e15\u0e23\u0e30\u0e1a\u0e1a..."; GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"; for f in menu ssh add-ssh del-ssh renew-ssh cek-ssh change-port; do wget -q -O /usr/sbin/$f "$GH/admin/$f" && chmod +x /usr/sbin/$f; done; wget -q -O /var/www/admin/index.php "$GH/admin/index.php"; wget -q -O /usr/local/bin/ssh-admin "$GH/admin/ssh-admin" && chmod +x /usr/local/bin/ssh-admin; echo "\u0e2d\u0e31\u0e1b\u0e40\u0e14\u0e15\u0e40\u0e2a\u0e23\u0e47\u0e08!"; read -n 1 -s -r -p "Press any key"; menu ;;
*) ssh ;;
esac
