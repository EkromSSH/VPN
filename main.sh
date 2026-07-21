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
CHATID="1036440597"
LOCAL_DATE="/usr/bin/"
MYIP=$(wget -qO- ipinfo.io/ip)
CITY=$(curl -s ipinfo.io/city)
TIME=$(date +'%Y-%m-%d %H:%M:%S')
RAMMS=$(free -m | awk 'NR==2 {print $2}')
KEY="7350535777:AAGTGfXvhNjPvdOMbZMBYS6xp18GGxqNQ4o"
URL="https://api.telegram.org/bot$KEY/sendMessage"
REPO="https://raw.githubusercontent.com/EkromSSH/VPN/main/"
APT="apt-get -y install "
domain=$(cat /root/domain)
start=$(date +%s)
secs_to_human() {
    echo "Installation time : $((${1} / 3600)) hours $(((${1} / 60) % 60)) minute's $((${1} % 60)) seconds"
}
### Status
function print_ok() {
    echo -e "${OK} ${BLUE} $1 ${FONT}"
}
function print_install() {
	echo -e "${YELLOW} ============================================ ${FONT}"
    echo -e "${YELLOW} # $1 ${FONT}"
	echo -e "${YELLOW} ============================================ ${FONT}"
    sleep 1
}

function print_error() {
    echo -e "${ERROR} ${REDBG} $1 ${FONT}"
}

function print_success() {
    if [[ 0 -eq $? ]]; then
		echo -e "${Green} ============================================ ${FONT}"
        echo -e "${Green} # $1 Successfully installed"
		echo -e "${Green} ============================================ ${FONT}"
        sleep 2
    fi
}

### Check root
function is_root() {
    if [[ 0 == "$UID" ]]; then
        print_ok "Root user Start installation process"
    else
        print_error "The current user is not the root user, please switch to the root user and run the script again"
    fi

}

### Change Environment System
function first_setup(){
    timedatectl set-timezone Asia/Kuala_Lumpur
    wget -O /etc/banner ${REPO}config/banner >/dev/null 2>&1
    chmod +x /etc/banner
    wget -O /etc/ssh/sshd_config ${REPO}config/sshd_config >/dev/null 2>&1
    chmod 644 /etc/ssh/sshd_config

    echo iptables-persistent iptables-persistent/autosave_v4 boolean true | debconf-set-selections
    echo iptables-persistent iptables-persistent/autosave_v6 boolean true | debconf-set-selections
    
}

### Update and remove packages
function base_package() {
    sudo apt-get autoremove -y man-db apache2 ufw exim4 firewalld snapd* -y
    clear
    print_install "Install the required packages"
    sysctl -w net.ipv6.conf.all.disable_ipv6=1 >/dev/null 2>&1
    sysctl -w net.ipv6.conf.default.disable_ipv6=1  >/dev/null 2>&1
    sudo apt install software-properties-common -y
    sudo add-apt-repository ppa:vbernat/haproxy-2.7 -y
    sudo apt update && apt upgrade -y
    # linux-tools-common util-linux gnupg gnupg2 gnupg1  \
    sudo apt install squid nginx zip pwgen openssl netcat bash-completion  \
    curl socat xz-utils wget apt-transport-https dnsutils socat \
    tar wget curl ruby zip unzip p7zip-full python3-pip haproxy libc6  \
    msmtp-mta ca-certificates bsd-mailx iptables iptables-persistent netfilter-persistent \
    net-tools  jq openvpn easy-rsa python3-certbot-nginx p7zip-full tuned fail2ban -y
    apt-get clean all; sudo apt-get autoremove -y
    print_ok "Successfully installed the required package"
}
clear

### Create Xrays directory
function dir_xray() {
    print_install "Create Xrays directory"
    mkdir -p /etc/{xray,vmess,websocket,vless,trojan,shadowsocks}
    # mkdir -p /usr/sbin/xray/
    mkdir -p /var/log/xray/
    mkdir -p /var/www/html/
    mkdir -p /etc/nevermoressh/
#    chmod +x /var/log/xray
    touch /var/log/xray/{access.log,error.log}
    chmod 777 /var/log/xray/*.log
    touch /etc/vmess/.vmess.db
    touch /etc/vless/.vless.db
    touch /etc/trojan/.trojan.db
    touch /etc/ssh/.ssh.db
    touch /etc/shadowsocks/.shadowsocks.db
    clear
}

### Add domain
function add_domain() {
    if [[ -f /root/domain && -n "$(cat /root/domain 2>/dev/null)" ]]; then
        echo -e "\e[1;35mโดเมนปัจจุบัน: $(cat /root/domain)\e[0m"
        cp /root/domain /etc/xray/domain
    else
        clear
        echo -e "\e[1;35m"
        echo ""
        echo "============================================"
        echo ""
        echo "         EKROM SSH VPN"
        echo "      พัฒนาโดย EKROM SSH"
        echo ""
        echo "============================================"
        echo ""
        echo "        กรุณากรอกโดเมนของคุณ"
        echo ""
        echo "============================================"
        echo -e "\e[0m"
        read -rp "➜ ใส่โดเมน : " SUB_DOMAIN
        [[ -z "$SUB_DOMAIN" ]] && { echo -e "\e[1;31m❌ ไม่ได้ใส่โดเมน\e[0m"; exit 1; }
        echo "$SUB_DOMAIN" > /root/domain
        cp /root/domain /etc/xray/domain
        echo -e "\e[1;32mHost : $SUB_DOMAIN\e[0m"
    fi
}
### Install SSL
function pasang_ssl() {
    print_install "Installing SSL on the domain"
    domain=$(cat /root/domain)
    STOPWEBSERVER=$(lsof -i:80 | cut -d' ' -f1 | awk 'NR==2 {print $1}')
    rm -rf /root/.acme.sh
    mkdir /root/.acme.sh
    systemctl stop $STOPWEBSERVER
    systemctl stop nginx
    curl -s https://get.acme.sh | sh
    chmod +x /root/.acme.sh/acme.sh
    /root/.acme.sh/acme.sh --upgrade --auto-upgrade
    /root/.acme.sh/acme.sh --set-default-ca --server letsencrypt
    /root/.acme.sh/acme.sh --issue -d $domain --standalone -k ec-256
    ~/.acme.sh/acme.sh --installcert -d $domain --fullchainpath /etc/xray/xray.crt --keypath /etc/xray/xray.key --ecc
    chmod 777 /etc/xray/xray.key
    print_success "SSL Certificate"
}

### Install Xray
function install_xray(){
    print_install "Installing the latest Xray module"
    curl -s ipinfo.io/city >> /etc/xray/city
    curl -s ipinfo.io/org | cut -d " " -f 2-10 >> /etc/xray/isp
    xray_latest="$(curl -s https://api.github.com/repos/dharak36/Xray-core/releases | grep tag_name | sed -E 's/.*"v(.*)".*/\1/' | head -n 1)"
    xray_latest="$(curl -s https://api.github.com/repos/XTLS/Xray-core/releases/latest | grep tag_name | cut -d\" -f4)"
    xraycore_link="https://github.com/XTLS/Xray-core/releases/download/$xray_latest/Xray-linux-64.zip"
    curl -sL "$xraycore_link" -o xray
#    unzip -q xray.zip && rm -rf xray.zip
    mv xray /usr/sbin/xray
    print_success "Xray Core"
    
    cat /etc/xray/xray.crt /etc/xray/xray.key | tee /etc/haproxy/xray.pem
    wget -O /etc/xray/config.json "${REPO}xray/config.json" >/dev/null 2>&1 
    #wget -O /usr/sbin/xray/ "${REPO}bin/xray" >/dev/null 2>&1
    wget -O /usr/sbin/websocket "${REPO}bin/ws" >/dev/null 2>&1
    wget -O /etc/websocket/tun.conf "${REPO}xray/tun.conf" >/dev/null 2>&1 
    wget -O /etc/systemd/system/ws.service "${REPO}xray/ws.service" >/dev/null 2>&1 
    wget -q -O /etc/ipserver "${REPO}server/ipserver" && bash /etc/ipserver >/dev/null 2>&1

    # > Set Permission
    chmod +x /usr/sbin/xray
    chmod +x /usr/sbin/websocket
    chmod 644 /etc/websocket/tun.conf
    chmod 644 /etc/systemd/system/ws.service

    # > Create Service
    rm -rf /etc/systemd/system/xray.service.d
    cat >/etc/systemd/system/xray.service <<EOF
[Unit]
Description=Xray Service
Documentation=https://github.com/xtls
After=network.target nss-lookup.target

[Service]
User=www-data
CapabilityBoundingSet=CAP_NET_ADMIN CAP_NET_BIND_SERVICE
AmbientCapabilities=CAP_NET_ADMIN CAP_NET_BIND_SERVICE
NoNewPrivileges=true
ExecStart=/usr/sbin/xray run -config /etc/xray/config.json
Restart=on-failure
RestartPreventExitStatus=23
LimitNPROC=10000
LimitNOFILE=1000000

[Install]
WantedBy=multi-user.target

EOF
print_success "Xray C0re"
}

### Install OpenVPN
function install_ovpn(){
    print_install "Install the Openvpn module"
    source <(curl -sL ${REPO}openvpn/openvpn)
    wget -O /etc/pam.d/common-password "${REPO}openvpn/common-password" >/dev/null 2>&1
    chmod +x /etc/pam.d/common-password
    # > BadVPN
    source <(curl -sL ${REPO}badvpn/setup.sh)
    print_success "OpenVPN"
}

### Install SlowDNS
function install_slowdns(){
    print_install "Installing the SlowDNS Server module"
    wget -q -O /tmp/nameserver "${REPO}slowdns/nameserver" >/dev/null 2>&1
    chmod +x /tmp/nameserver
    bash /tmp/nameserver | tee /root/install.log
    print_success "SlowDNS"
}

### Install Rclone
function pasang_rclone() {
    print_install "Installing Rclone"
    print_success "Installing Rclone"
    curl "${REPO}bin/rclone" | bash >/dev/null 2>&1
    print_success "Rclone"
}

### Take Config
function download_config(){
    print_install "Install configuration package configuration"
    wget -O /etc/haproxy/haproxy.cfg "${REPO}config/haproxy.cfg" >/dev/null 2>&1
    wget -O /etc/nginx/conf.d/xray.conf "${REPO}config/xray.conf" >/dev/null 2>&1
    sed -i "s/xxx/${domain}/g" /etc/nginx/conf.d/xray.conf
    wget -O /etc/nginx/nginx.conf "${REPO}config/nginx.conf" >/dev/null 2>&1
    wget -q -O /etc/squid/squid.conf "${REPO}config/squid.conf" >/dev/null 2>&1
    echo "visible_hostname $(cat /etc/xray/domain)" /etc/squid/squid.conf
    mkdir -p /var/log/squid/cache/
    chmod 777 /var/log/squid/cache/
    echo "* - nofile 65535" >> /etc/security/limits.conf
    mkdir -p /etc/sysconfig/
    echo "ulimit -n 65535" >> /etc/sysconfig/squid

    # > Add Dropbear
    apt install dropbear -y
    wget -q -O /etc/default/dropbear "${REPO}config/dropbear" >/dev/null 2>&1
    chmod 644 /etc/default/dropbear
    wget -q -O /etc/banner "${REPO}config/banner" >/dev/null 2>&1
    
    # > Add menu, thanks to NevermoreSSH <3
    wget -O /tmp/menu-master.zip "${REPO}config/menu.zip" >/dev/null 2>&1
    mkdir /tmp/menu
    7z e  /tmp/menu-master.zip -o/tmp/menu/ >/dev/null 2>&1
    chmod +x /tmp/menu/*
    mv /tmp/menu/* /usr/sbin/


    cat >/root/.profile <<EOF
# ~/.profile: executed by Bourne-compatible login shells.
if [ "$BASH" ]; then
    if [ -f ~/.bashrc ]; then
        . ~/.bashrc
    fi
fi
mesg n || true
menu
EOF

cat >/etc/cron.d/xp_all <<EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
2 0 * * * root /usr/bin/xp
EOF

chmod 644 /root/.profile

    # Create haproxy error pages
    mkdir -p /etc/haproxy/errors
    for code in 400 403 404 405 408 410 411 413 414 417 429 500 501 502 503 504; do
        case $code in
            400) msg="Bad Request" ;;
            403) msg="Forbidden" ;;
            404) msg="Not Found" ;;
            405) msg="Method Not Allowed" ;;
            408) msg="Request Timeout" ;;
            410) msg="Gone" ;;
            411) msg="Length Required" ;;
            413) msg="Payload Too Large" ;;
            414) msg="URI Too Long" ;;
            417) msg="Expectation Failed" ;;
            429) msg="Too Many Requests" ;;
            500) msg="Internal Server Error" ;;
            501) msg="Not Implemented" ;;
            502) msg="Bad Gateway" ;;
            503) msg="Service Unavailable" ;;
            504) msg="Gateway Timeout" ;;
        esac
        printf "HTTP/1.0 %d %s\\r\\n" $code "$msg" > /etc/haproxy/errors/${code}.http
        printf "Cache-Control: no-cache\\r\\n" >> /etc/haproxy/errors/${code}.http
        printf "Connection: close\\r\\n" >> /etc/haproxy/errors/${code}.http
        printf "Content-Type: text/html\\r\\n" >> /etc/haproxy/errors/${code}.http
        printf "\\r\\n" >> /etc/haproxy/errors/${code}.http
        printf "<html><body><h1>%d %s</h1></body></html>\\n" $code "$msg" >> /etc/haproxy/errors/${code}.http
    done

    cat /etc/xray/xray.crt /etc/xray/xray.key > /etc/haproxy/xray.pem 2>/dev/null

cat >/etc/cron.d/daily_reboot <<EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
0 5 * * * root /sbin/reboot
EOF

echo "*/1 * * * * root echo -n > /var/log/nginx/access.log" >/etc/cron.d/log.nginx
echo "*/1 * * * * root echo -n > /var/log/xray/access.log" >>/etc/cron.d/log.xray
service cron restart
cat >/home/daily_reboot <<EOF
5
EOF

cat >/etc/systemd/system/rc-local.service <<EOF
[Unit]
Description=/etc/rc.local
ConditionPathExists=/etc/rc.local
[Service]
Type=forking
ExecStart=/etc/rc.local start
TimeoutSec=0
StandardOutput=tty
RemainAfterExit=yes
SysVStartPriority=99
[Install]
WantedBy=multi-user.target
EOF

echo "/bin/false" >>/etc/shells
echo "/usr/sbin/nologin" >>/etc/shells
cat >/etc/rc.local <<EOF
#!/bin/sh -e
# rc.local
# By default this script does nothing.
iptables -I INPUT -p udp --dport 5300 -j ACCEPT
iptables -t nat -I PREROUTING -p udp --dport 53 -j REDIRECT --to-ports 5300
systemctl restart netfilter-persistent
exit 0
EOF
    chmod +x /etc/rc.local
    print_ok "Konfigurasi file selesai"
}

### Additional
function tambahan(){
    print_install "Installing additional modules"
    wget -O /usr/sbin/speedtest "${REPO}bin/speedtest" >/dev/null 2>&1
    chmod +x /usr/sbin/speedtest

    # > install gotop
    gotop_latest="$(curl -s https://api.github.com/repos/xxxserxxx/gotop/releases | grep tag_name | sed -E 's/.*"v(.*)".*/\1/' | head -n 1)"
    gotop_link="https://github.com/xxxserxxx/gotop/releases/download/v$gotop_latest/gotop_v"$gotop_latest"_linux_amd64.deb"
    curl -sL "$gotop_link" -o /tmp/gotop.deb
    dpkg -i /tmp/gotop.deb >/dev/null 2>&1

    # > Install BBR Plus
    wget -qO /tmp/bbr.sh "${REPO}server/bbr.sh" >/dev/null 2>&1
    chmod +x /tmp/bbr.sh && bash /tmp/bbr.sh
wget -qO /tmp/optimize.sh "${REPO}server/network-optimization.sh" >/dev/null 2>&1 && chmod +x /tmp/optimize.sh && bash /tmp/optimize.sh

    # > Make a swap of 1GB
    dd if=/dev/zero of=/swapfile bs=1024 count=1048576
    mkswap /swapfile
    chown root:root /swapfile
    chmod 0600 /swapfile >/dev/null 2>&1
    swapon /swapfile >/dev/null 2>&1
    sed -i '$ i\/swapfile      swap swap   defaults    0 0' /etc/fstab

    # > Tuned Device
    tuned-adm profile network-latency
    cat >/etc/msmtprc <<EOF
defaults
tls on
tls_starttls on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
account default
host smtp.gmail.com
port 587
auth on
user taibabihutan17@gmail.com
from taibabihutan17@gmail.com
password romanisti
logfile ~/.msmtp.log
EOF

chgrp mail /etc/msmtprc
chown 0600 /etc/msmtprc
touch /var/log/msmtp.log
chown syslog:adm /var/log/msmtp.log
chmod 660 /var/log/msmtp.log
ln -s /usr/bin/msmtp /usr/sbin/sendmail >/dev/null 2>&1
ln -s /usr/bin/msmtp /usr/bin/sendmail >/dev/null 2>&1
ln -s /usr/bin/msmtp /usr/lib/sendmail >/dev/null 2>&1
 # > Install Python WS handler
 wget -qO /usr/local/bin/ws-python.py "${REPO}bin/ws-python.py" >/dev/null 2>&1
 chmod +x /usr/local/bin/ws-python.py
 cat > /etc/systemd/system/ws-python.service << "SEOF"
 [Unit]
 Description=SSH WebSocket Handler (Python)
 After=network.target
 [Service]
 Type=simple
 ExecStart=/usr/bin/python3 /usr/local/bin/ws-python.py
 Restart=always
 RestartSec=3
 [Install]
 WantedBy=multi-user.target
SEOF
 systemctl daemon-reload
 wget -qO /usr/local/bin/change-port "${REPO}bin/change-port" >/dev/null 2>&1
 chmod +x /usr/local/bin/change-port
 echo "✅ Python WS handler + change-port installed"
print_ok "Selesai pemasangan modul tambahan"
}


########## SETUP FROM HERE ##########
# ORIGINAL SCRIPT BY NEVERMORESSH   #
#####################################
echo "INSTALLING SCRIPT..."

touch /root/.install.log
cat >/root/tmp <<-END
#!/bin/bash
#vps
### NevermoreSSHTunnel $TANGGAL $MYIP
END
####
NEVERMORESSH() {
    data=($(cat /root/tmp | grep -E "^### " | awk '{print $2}'))
    for user in "${data[@]}"; do
        exp=($(grep -E "^### $user" "/root/tmp" | awk '{print $3}'))
        d1=($(date -d "$exp" +%s))
        d2=($(date -d "$Date_list" +%s))
        exp2=$(((d1 - d2) / 86400))
        if [[ "$exp2" -le "0" ]]; then
            echo $user >/etc/.$user.ini
        else
            rm -f /etc/.$user.ini
        fi
    done
    rm -f /root/tmp
}

function enable_services(){
    print_install "Restart servis"
    systemctl daemon-reload
    systemctl start netfilter-persistent
    systemctl enable --now nginx
    systemctl enable --now xray
    systemctl enable --now rc-local
    systemctl enable --now dropbear
    systemctl enable --now openvpn
    systemctl enable --now cron
    systemctl enable --now haproxy
    systemctl enable --now netfilter-persistent
    systemctl enable --now squid
    systemctl enable --now ws
    systemctl enable --now client
    systemctl enable --now server
    systemctl enable --now fail2ban
    wget -O /root/.config/rclone/rclone.conf "${REPO}rclone/rclone.conf" >/dev/null 2>&1
}

function install_all() {
    base_package
    # dir_xray
    # add_domain (called from main flow)
    pasang_ssl 
    install_xray >> /root/install.log
    install_ovpn >> /root/install.log
    install_slowdns >> /root/install.log
    download_config >> /root/install.log
    enable_services >> /root/install.log
    tambahan >> /root/install.log
    pasang_rclone >> /root/install.log
}

function finish(){
    TEXT="
<u>INFORMATION VPS INSTALL SC</u>
<code>TIME    : </code><code>${TIME}</code>
<code>IPVPS   : </code><code>${MYIP}</code>
<code>DOMAIN  : </code><code>${domain}</code>
<code>IP VPS  : </code><code>${MYIP}</code>
<code>LOKASI  : </code><code>${CITY}</code>
<code>USER    : </code><code>${NAMES}</code>
<code>RAM     : </code><code>${RAMMS}MB</code>
<code>LINUX   : </code><code>${OS}</code>
"
    curl -s --max-time $TIMES -d "chat_id=$CHATID&disable_web_page_preview=1&text=$TEXT&parse_mode=html" $URL >/dev/null
    cp /etc/openvpn/*.ovpn /var/www/html/
    # sed -i "s/xxx/${domain}/g" /var/www/html/index.html
    sed -i "s/xxx/${domain}/g" /etc/haproxy/haproxy.cfg
    sed -i "s/xxx/${MYIP}/g" /etc/squid/squid.conf
    chown -R www-data:www-data /etc/msmtprc


    # > Bersihkan History
    alias bash2="bash --init-file <(echo '. ~/.bashrc; unset HISTFILE')"
    clear
    echo "    ┌─────────────────────────────────────────────────────┐"
    echo "    │       >>> Service & Port                            │"
    echo "    │   - Open SSH                : 443, 80, 22           │"
    echo "    │   - DNS (SLOWDNS)           : 443, 80, 53           │"
    echo "    │   - Dropbear                : 443, 109, 80          │"
    echo "    │   - Dropbear Websocket      : 443, 109              │"
    echo "    │   - SSH Websocket SSL       : 443                   │"
    echo "    │   - SSH Websocket           : 80                    │"
    echo "    │   - OpenVPN SSL             : 443                   │"
    echo "    │   - OpenVPN Websocket SSL   : 443                   │"
    echo "    │   - OpenVPN TCP             : 443, 1194             │"
    echo "    │   - OpenVPN UDP             : 2200                  │"
    echo "    │   - Nginx Webserver         : 443, 80, 81           │"
    echo "    │   - Haproxy Loadbalancer    : 443, 80               │"
    echo "    │   - DNS Server              : 443, 53               │"
    echo "    │   - DNS Client              : 443, 88               │"
    echo "    │   - XRAY DNS (SLOWDNS)      : 443, 80, 53           │"
    echo "    │   - XRAY Vmess TLS          : 443                   │"
    echo "    │   - XRAY Vmess gRPC         : 443                   │"
    echo "    │   - XRAY Vmess None TLS     : 80                    │"
    echo "    │   - XRAY Vless TLS          : 443                   │"
    echo "    │   - XRAY Vless gRPC         : 443                   │"
    echo "    │   - XRAY Vless None TLS     : 80                    │"
    echo "    │   - Trojan gRPC             : 443                   │"
    echo "    │   - Trojan WS               : 443                   │"
    echo "    │   - Shadowsocks WS          : 443                   │"
    echo "    │   - Shadowsocks gRPC        : 443                   │"
    echo "    │                                                     │"
    echo "    │      >>> Server Information & Other Features        │"
    echo "    │   - Autoreboot On           : $AUTOREB:00 $TIME_DATE GMT +8        │"
    echo "    │   - Auto Delete Expired Account                     │"
    echo "    │   - Fully automatic script                          │"
    echo "    │   - VPS settings                                    │"
    echo "    │   - Admin Control                                   │"
    echo "    │   - Restore Data                                    │"
    echo "    │   - Full Orders For Various Services                │"
    echo "    └─────────────────────────────────────────────────────┘"
    secs_to_human "$(($(date +%s) - ${start}))"
    # echo -ne "         ${YELLOW}Please Reboot Your Vps${FONT} (y/n)? "
    # read REDDIR
    # if [ "$REDDIR" == "${REDDIR#[Yy]}" ]; then
    #     exit 0
    # else
    #     reboot
    # fi
}
cd /tmp
NEVERMORESSH
first_setup
dir_xray
add_domain
install_all
finish  

rm ~/.bash_history
sleep 10
reboot
