#!/bin/bash
#Script UdpCustom 2023
#Script By NevermoreSSH
cd
rm -rf /root/udp
mkdir -p /root/udp
# install udp-custom
echo ""
sleep 4
echo " Proses Download Script UdpCustom........"
sleep 4
clear
echo " Checking Tool UdpCustom By NevermoreSSH......."
sleep 4
echo "Downloading UDP Custom binary..."
REPO_UDP="https://raw.githubusercontent.com/EkromSSH/VPN/main/Tunnel/"
if [ -f "/root/VPN/Tunnel/udp-custom-linux-amd64" ]; then
    cp /root/VPN/Tunnel/udp-custom-linux-amd64 /root/udp/udp-custom
else
    wget -q "${REPO_UDP}udp-custom-linux-amd64" -O /root/udp/udp-custom 2>/dev/null || \
    wget -q "https://raw.githubusercontent.com/NevermoreSSH/Vergil/main/Tunnel/udp-custom-linux-amd64" -O /root/udp/udp-custom
fi
chmod +x /root/udp/udp-custom

# install Config Default Udp
wget -q "${REPO_UDP}udp-config.json" -O /root/udp/config.json 2>/dev/null || \
wget -q "https://raw.githubusercontent.com/NevermoreSSH/Vergil/main/Tunnel/config.json" -O /root/udp/config.json 2>/dev/null || true
chmod 644 /root/udp/config.json 2>/dev/null || true

if [ -z "$1" ]; then
cat <<EOF > /etc/systemd/system/udp-custom.service
[Unit]
Description=UDP Custom by NevermoreSSH

[Service]
User=root
Type=simple
ExecStart=/root/udp/udp-custom server
WorkingDirectory=/root/udp/
Restart=always
RestartSec=2s

[Install]
WantedBy=default.target
EOF
else
cat <<EOF > /etc/systemd/system/udp-custom.service
[Unit]
Description=UDP Custom by NevermoreSSH

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

