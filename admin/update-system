#!/bin/bash

### Color
Green="\e[92;1m"
RED="\033[31m"
YELLOW="\033[33m"
BLUE="\033[36m"
FONT="\033[0m"
GREENBG="\033[42;37m"
REDBG="\033[41;37m"
OK="${Green}--->${FONT}"
ERROR="${RED}[ERROR]${FONT}"
GRAY="\e[1;30m"
NC='\e[0m'
red='\e[1;31m'
green='\e[0;32m'

### System Information
TANGGAL=$(date '+%Y-%m-%d')
TIMES="10"
NAMES=$(whoami)
IMP="wget -q -O"    
MYIP=$(wget -qO- ipinfo.io/ip)
CITY=$(curl -s ipinfo.io/city)
TIME=$(date +'%Y-%m-%d %H:%M:%S')
RAMMS=$(free -m | awk 'NR==2 {print $2}')
REPO="https://raw.githubusercontent.com/EkromSSH/VPN/main/admin/"
APT="apt-get -y install"
start=$(date +%s)

echo "0 1 * * * root xp" >> /etc/crontab
echo "*/2 * * * * root logclean" >> /etc/crontab
echo "0 3 * * * root /usr/sbin/xp" >> /etc/crontab
echo "0 5 * * * root reboot" >> /etc/crontab

# fix missing & update
apt install htop -y 
apt install vnstat -y 
apt install resolvconf -y 

# download menu
cd /usr/sbin
wget -O menu "${REPO}menu"
wget -O ssh "${REPO}ssh"
wget -O add-ssh "${REPO}add-ssh"
wget -O del-ssh "${REPO}del-ssh"
wget -O renew-ssh "${REPO}renew-ssh"
wget -O cek-ssh "${REPO}cek-ssh"
wget -O change-port "${REPO}change-port"

chmod +x menu
chmod +x ssh
chmod +x add-ssh
chmod +x del-ssh
chmod +x renew-ssh
chmod +x cek-ssh
chmod +x change-port

# download additional scripts
cd /usr/local/bin
wget -O ssh-admin "${REPO}ssh-admin"

chmod +x ssh-admin

cd
sleep 2
menu
