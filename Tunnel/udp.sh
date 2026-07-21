#!/bin/bash
#Script UdpCustom 2023
#Script By EkromSSH
cd
rm -rf /root/udp
mkdir -p /root/udp
# install udp-custom
echo ""
sleep 4
echo " Proses Download Script UdpCustom........"
sleep 4
clear
echo " Checking Tool UdpCustom By EkromSSH......."
sleep 4
clear
echo " Success Checking Tool..........."
sleep 4
clear
echo " Please Waiting Proses Downloading Toll UdpCustom........"
sleep 4
clear
wget -q --show-progress --load-cookies /tmp/cookies.txt "https://raw.githubusercontent.com/EkromSSH/VPN/main/Tunnel/udp-custom-linux-amd64" -O /root/udp/udp-custom && rm -rf /tmp/cookies.txt
chmod +x /root/udp/udp-custom
clear
# install Config Default Udp
echo ""
sleep 4
echo " Proses Download Script Config Default........"
sleep 4
clear
wget -q --show-progress --load-cookies /tmp/cookies.txt "https://raw.githubusercontent.com/EkromSSH/VPN/main/Tunnel/config.json" -O /root/udp/config.json && rm -rf /tmp/cookies.txt
echo ""
sleep 4
echo " Proses Download Config Default Sukses........"
sleep 4
clear
echo " Sukses Daftlowload Config Default......."
sleep 4
clear
echo " Sukses Download Config Default........"
sleep 4
clear
if [[ $(cat /etc/os-release | grep -w ID | cut -d '=' -f2) = "ubuntu" ]]; then
cat <<EOF > /etc/systemd/system/udp-custom.service
[Unit]
Description=UDP Custom by EkromSSH

[Service]
User=root
Type=simple
ExecStart=/root/udp/udp-custom server -exclude $1
WorkingDirectory=/root/udp/
Restart=always
RestartSec=2s

[Install]
WantedBy=default.target
EOF
else
cat <<EOF > /etc/systemd/system/udp-custom.service
[Unit]
Description=UDP Custom by EkromSSH

[Service]
User=root
Type=simple
ExecStart=/root/udp/udp-custom server -exclude $1
WorkingDirectory=/root/udp/
Restart=always
RestartSec=2s

[Install]
WantedBy=default.target
EOF
fi

echo start service udp-custom
systemctl start udp-custom &>/dev/null

echo enable service udp-custom
systemctl enable udp-custom &>/dev/null

echo ""
sleep 0,5
clear
