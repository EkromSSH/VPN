:443?mode=gun&security=tls&encryption=none&type=grpc&serviceName=vless-grpc&sni=${domain}#${user}
_______________________________________________________

END
vlesslink1="vless://${uuid}@${domain}:443?path=/vless&security=tls&encryption=none&type=ws#${user}"
vlesslink2="vless://${uuid}@${domain}:80?path=/vless&encryption=none&type=ws#${user}"
vlesslink3="vless://${uuid}@${domain}:443?mode=gun&security=tls&encryption=none&type=grpc&serviceName=vless-grpc&sni=${domain}#${user}"
systemctl restart xray
systemctl restart nginx
service cron restart
if [ ! -e /etc/vless ]; then
  mkdir -p /etc/vless
fi

if [ -z ${Quota} ]; then
  Quota="0"
fi

c=$(echo "${Quota}" | sed 's/[^0-9]*//g')
d=$((${c} * 1024 * 1024 * 1024))

if [[ ${c} != "0" ]]; then
  echo "${d}" >/etc/vless/${user}
fi
DATADB=$(cat /etc/vless/.vless.db | grep "^###" | grep -w "${user}" | awk '{print $2}')
if [[ "${DATADB}" != '' ]]; then
  sed -i "/\b${user}\b/d" /etc/vless/.vless.db
fi
echo "### ${user} ${exp} ${uuid}" >>/etc/vless/.vless.db
curl -s --max-time $TIMES -d "chat_id=$CHATID&disable_web_page_preview=1&text=$TEXT&parse_mode=html" $URL
clear
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "\e[42m    Xray/Vless Account     \E[0m" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Remarks     : ${user}" | tee -a /etc/nevermoressh/user.log
echo -e "Domain      : ${domain}" | tee -a /etc/nevermoressh/user.log
echo -e "Host Slowdns: ${NS}" | tee -a /etc/nevermoressh/user.log
echo -e "Pub Key     : ${PUB}" | tee -a /etc/nevermoressh/user.log
#echo -e "Location    : $CITY" | tee -a /etc/nevermoressh/user.log
echo -e "User Quota  : ${Quota} GB" | tee -a /etc/nevermoressh/user.log
echo -e "port TLS    : 443" | tee -a /etc/nevermoressh/user.log
echo -e "Port DNS    : 443, 53 " | tee -a /etc/nevermoressh/user.log
echo -e "Port NTLS   : 80, 8080, 2086" | tee -a /etc/nevermoressh/user.log
echo -e "User ID     : ${uuid}" | tee -a /etc/nevermoressh/user.log
echo -e "Encryption  : none" | tee -a /etc/nevermoressh/user.log
echo -e "Path TLS    : /vless " | tee -a /etc/nevermoressh/user.log
echo -e "ServiceName : vless-grpc" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Link TLS   : ${vlesslink1}" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Link NTLS  : ${vlesslink2}" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Link GRPC  : ${vlesslink3}" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Format OpenClash : https://${domain}:81/vless-$user.txt" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Expired On : $exp" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "" | tee -a /etc/nevermoressh/user.log
read -n 1 -s -r -p "Press any key to back on menu"

menu
