��───────────────────────────────────────┘\033[0m"
echo -e "\033[1;35m┌────────────────────────────────────────────────────┐\033[0m"
echo -e "\033[1;35m│  ${grenbo}1.${NC} \033[0mจัดการ SSH OVPN${NC}          ${grenbo}8.${NC}  \033[0mข้อมูลพอร์ต VPS${NC}"
echo -e "\033[1;35m│  ${grenbo}2.${NC} \033[0mจัดการ VMESS${NC}             ${grenbo}9.${NC}  \033[0mข้อมูลโหลด VPS${NC}"
echo -e "\033[1;35m│  ${grenbo}3.${NC} \033[0mจัดการ VLESS${NC}             ${grenbo}10.${NC} \033[0mทดสอบความเร็ว${NC}"
echo -e "\033[1;35m│  ${grenbo}4.${NC} \033[0mจัดการ TROJAN${NC}            ${grenbo}11.${NC} \033[0mเปลี่ยนโดเมน${NC}"
echo -e "\033[1;35m│  ${grenbo}5.${NC} \033[0mจัดการ SHADWSOCK${NC}         ${grenbo}12.${NC} \033[0mเปลี่ยนแบนเนอร์${NC}"
echo -e "\033[1;35m│  ${grenbo}6.${NC} \033[0mระบบที่กำลังทำงาน${NC}        ${grenbo}13.${NC} \033[0mรีสตาร์ทบริการ${NC}"
echo -e "\033[1;35m│  ${grenbo}7.${NC} \033[0mสำรองและกู้คืน${NC}           ${grenbo}14.${NC} \033[0mรีสตาร์ทเซิร์ฟเวอร์${NC}"
echo -e "\033[1;35m│  "
echo -e "\033[1;35m│  ${grenbo}15.${NC} \033[0mเปลี่ยน DNS${NC}             ${grenbo}16.${NC} \033[0mตรวจสอบ NETFLIX${NC}"
echo -e "\033[1;35m│  ${grenbo}17.${NC} \033[0mSWAP RAM${NC}                ${grenbo}18.${NC} \033[0mเปลี่ยน XRAYCORE${NC}"
echo -e "\033[1;35m│  ${grenbo}19.${NC} \033[0mติดตั้ง UDP${NC}             ${grenbo}20.${NC} \033[0mติดตั้ง BBRPLUS${NC}"
echo -e "\033[1;35m└────────────────────────────────────────────────────┘\033[0m"
echo -e ""
echo -e "\033[1;35m┌─────────────────────────────────────────────────────┐\033[0m"
echo -e "\033[1;35m│\033[0m\033[0mEKROMVPNSSH${NC}(C)\033[1;94mhttps://github.com/EkromSSH${NC}\033[1;35m\033[0m"
echo -e "\033[1;35m└─────────────────────────────────────────────────────┘\033[0m"
echo -e ""
read -p "เลือกจากตัวเลือก [ 1 - 20 ] : " menu
case $menu in
1)
    ssh
    ;;
2)
    vmess
    ;;
3)
    vless
    ;;
4)
    trojan
    ;;
5)
    shadowsocks
    ;;
6)
    run
    ;;
7)
    get-backres
    ;;
8)
    portin
    ;;
9)
    gotop
    ;;
10)
    speedtest
    ;;
11)
    get-domain
    ;;
12)
    nano /etc/issue.net
    ;;
13)
    seres
    ;;
14)
    reboot
    ;;
15)
    dns
    ;;
16)
    netf
    ;;
17)
    wget -q -O /usr/bin/swapram "https://raw.githubusercontent.com/EkromSSH/swapram/main/swapram.sh" && chmod +x /usr/bin/swapram && swapram
    ;;
18)
    wget -q -O /usr/bin/xraychanger2 "https://raw.githubusercontent.com/EkromSSH/Xcore-custompath/main/xraychanger2.sh" && chmod +x /usr/bin/xraychanger2 && xraychanger2
    ;;
19)
    wget https://raw.githubusercontent.com/NevermoreSSH/Vergil/main/Tunnel/udp.sh && bash udp.sh
    ;;
20)
    wget -q -O /usr/sbin/bbr5 "https://raw.githubusercontent.com/NevermoreSSH/BBRplus/main/bbr.sh" && chmod +x /usr/sbin/bbr5 && bbr5
    ;;
*)
    menu
    ;;
esac
