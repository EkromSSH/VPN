�───────</code>
<code>Format OpenClash :</code> http://${domain}:81/vmess-$user.txt
<code>───────────────────────────</code>
<code>Expired On : $exp</code>
"
systemctl restart xray
systemctl restart nginx
service cron restart

if [ ! -e /etc/vmess ]; then
  mkdir -p /etc/vmess
fi

if [ -z ${Quota} ]; then
  Quota="0"
fi

c=$(echo "${Quota}" | sed 's/[^0-9]*//g')
d=$((${c} * 1024 * 1024 * 1024))

if [[ ${c} != "0" ]]; then
  echo "${d}" >/etc/vmess/${user}
fi
DATADB=$(cat /etc/vmess/.vmess.db | grep "^###" | grep -w "${user}" | awk '{print $2}')
if [[ "${DATADB}" != '' ]]; then
  sed -i "/\b${user}\b/d" /etc/vmess/.vmess.db
fi
echo "### ${user} ${exp} ${uuid}" >>/etc/vmess/.vmess.db
curl -s --max-time $TIMES -d "chat_id=$CHATID&disable_web_page_preview=1&text=$TEXT&parse_mode=html" $URL >/dev/null
clear
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "\e[42m    Xray/Vmess Account     \E[0m" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Remarks      : ${user}" | tee -a /etc/nevermoressh/user.log
echo -e "Domain       : ${domain}" | tee -a /etc/nevermoressh/user.log
echo -e "Host Slowdns : ${NS}" | tee -a /etc/nevermoressh/user.log
echo -e "Pub Key      : ${PUB}" | tee -a /etc/nevermoressh/user.log
#echo -e "Location     : $CITY" | tee -a /etc/nevermoressh/user.log
echo -e "User Quota   : ${Quota} GB" | tee -a /etc/nevermoressh/user.log
echo -e "Port TLS     : 443" | tee -a /etc/nevermoressh/user.log
echo -e "Port NTLS    : 80, 8080, 2086" | tee -a /etc/nevermoressh/user.log
echo -e "Port DNS     : 443, 53 " | tee -a /etc/nevermoressh/user.log
echo -e "Port GRPC    : 443" | tee -a /etc/nevermoressh/user.log
echo -e "User ID      : ${uuid}" | tee -a /etc/nevermoressh/user.log
echo -e "AlterId      : 0" | tee -a /etc/nevermoressh/user.log
echo -e "Security     : auto" | tee -a /etc/nevermoressh/user.log
echo -e "Network      : WS or gRPC" | tee -a /etc/nevermoressh/user.log
echo -e "Path TLS     : (/multi path) " | tee -a /etc/nevermoressh/user.log
echo -e "Path NLS     : (/multi path) " | tee -a /etc/nevermoressh/user.log
echo -e "Path Dynamic : CF-XRAY:http://bug.com " | tee -a /etc/nevermoressh/user.log
echo -e "ServiceName  : vmess-grpc" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Link TLS     : ${vmesslink1}" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Link NTLS    : ${vmesslink2}" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Link GRPC    : ${vmesslink3}" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Format OpenClash : https://${domain}:81/vmess-$user.txt" | tee -a /etc/nevermoressh/user.log
echo -e "\033[1;93m───────────────────────────\033[0m" | tee -a /etc/nevermoressh/user.log
echo -e "Expired On : $exp" | tee -a /etc/nevermoressh/user.log
echo -e "" | tee -a /etc/nevermoressh/user.log
read -n 1 -s -r -p "Press any key to back on menu"

menu
