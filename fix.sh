#!/bin/bash
# ═══════════════════════════════════════════════════════════
#  EKROM SSH VPN — FIX SCRIPT (รันครั้งเดียวหลังติดตั้ง)
#  แก้ปัญหาที่พบทั้งหมด:
#  - ติดตั้ง vnstat (เมนูแสดงปริมาณการใช้งาน)
#  - ดาวน์โหลดสคริปต์ที่หาย (seres, vmess, vless, trojan ฯลฯ)
#  - ติดตั้ง quota monitor (GB + ลิมิต IP) + cron
#  - สร้าง telegram.conf
#  - แก้ nginx Too many open files
#  วิธีใช้: wget -q -O fix.sh https://raw.githubusercontent.com/EkromSSH/VPN/main/fix.sh && bash fix.sh
# ═══════════════════════════════════════════════════════════
REPO="https://raw.githubusercontent.com/EkromSSH/VPN/main/"
GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok() { echo -e "${GREEN}✅ $1${NC}"; }
fail() { echo -e "${RED}❌ $1${NC}"; }
info() { echo -e "${YELLOW}➡️  $1${NC}"; }

echo -e "\n${GREEN}══════════════════════════════════════${NC}"
echo -e "${GREEN}   EKROM SSH VPN — FIX SCRIPT v1.0${NC}"
echo -e "${GREEN}══════════════════════════════════════${NC}\n"

# ── 1. ติดตั้ง vnstat (เมนูแสดงปริมาณการใช้งาน) ──
info "ตรวจสอบ vnstat..."
if command -v vnstat >/dev/null 2>&1; then
    ok "vnstat มีอยู่แล้ว"
else
    apt-get install -y vnstat >/dev/null 2>&1 && ok "ติดตั้ง vnstat สำเร็จ" || fail "ติดตั้ง vnstat ไม่สำเร็จ"
fi
systemctl enable vnstat >/dev/null 2>&1
systemctl start vnstat >/dev/null 2>&1
[ -f /etc/systemd/system/vnstat.service ] && systemctl restart vnstat >/dev/null 2>&1

# ── 2. ดาวน์โหลดสคริปต์ที่หาย ──
info "ดาวน์โหลดสคริปต์ล่าสุด..."
for f in menu ssh add-ssh del-ssh renew-ssh cek-ssh seres vmess vless trojan shadowsocks run; do
    wget -q -O /usr/sbin/$f "${REPO}admin/$f" 2>/dev/null
    chmod +x /usr/sbin/$f
done
wget -q -O /usr/local/bin/ssh-admin "${REPO}admin/ssh-admin" 2>/dev/null
chmod +x /usr/local/bin/ssh-admin
ok "สคริปต์ทั้งหมดอัปเดตแล้ว"

# ── 3. ติดตั้ง quota monitor (GB + ลิมิต IP) + cron ──
info "ติดตั้งระบบจำกัด GB + ลิมิต IP..."
wget -q -O /usr/sbin/ssh-quota-monitor "${REPO}admin/ssh-quota-monitor" 2>/dev/null
chmod +x /usr/sbin/ssh-quota-monitor
(crontab -l 2>/dev/null | grep -v ssh-quota-monitor; echo "*/5 * * * * /usr/sbin/ssh-quota-monitor >/dev/null 2>&1") | crontab -
ok "quota monitor พร้อมใช้งาน (ตรวจทุก 5 นาที)"

# ── 4. สร้าง telegram.conf (ถ้ายังไม่มี) ──
info "ตรวจสอบ Telegram config..."
if [ ! -f /etc/ssh/telegram.conf ]; then
    mkdir -p /etc/ssh
    cat > /etc/ssh/telegram.conf <<'EOF'
# ใส่ KEY และ CHATID ของบอท Telegram คุณเอง
# สร้างบอทที่ @BotFather แล้ววาง KEY ตรงนี้ (หรือปล่อยว่าง = ไม่แจ้งเตือน)
KEY=""
CHATID=""
EOF
    chmod 600 /etc/ssh/telegram.conf
    info "สร้าง /etc/ssh/telegram.conf แล้ว — แก้ KEY/CHATID ได้ที่: nano /etc/ssh/telegram.conf"
else
    ok "telegram.conf มีอยู่แล้ว"
fi

# ── 5. แก้ nginx Too many open files ──
# ให้ www-data restart ws.service ได้ (เปลี่ยนพอร์ตผ่านเว็บ)
if [ -f /etc/websocket/tun.conf ] && ! grep -q ws-panel /etc/sudoers.d/ws-panel 2>/dev/null; then
    echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart ws" > /etc/sudoers.d/ws-panel
    chmod 440 /etc/sudoers.d/ws-panel
    ok "เพิ่มสิทธิ์เปลี่ยนพอร์ตผ่านเว็บแล้ว"
fi
info "ตรวจสอบ nginx..."
if [ -f /etc/nginx/nginx.conf ] && ! grep -q 'worker_rlimit_nofile' /etc/nginx/nginx.conf; then
    sed -i 's/^worker_processes.*/worker_processes auto;\nworker_rlimit_nofile 65535;/' /etc/nginx/nginx.conf
    nginx -t >/dev/null 2>&1 && systemctl restart nginx >/dev/null 2>&1 && ok "nginx อัปเดตแล้ว" || fail "nginx config มีปัญหา"
else
    ok "nginx เรียบร้อย"
fi

# ── 6. Web panel (ถ้ามี) ──
info "ตรวจสอบ Web Panel..."
if [ -d /var/www/admin ]; then
    wget -q -O /var/www/admin/index.php "${REPO}admin/index.php" 2>/dev/null
    ok "Web Panel อัปเดตแล้ว (มีช่องกำหนด GB + ลิมิต IP)"
fi

echo ""
echo -e "${GREEN}══════════════════════════════════════${NC}"
echo -e "${GREEN}   ✅ แก้ไขเสร็จเรียบร้อย! ${NC}"
echo -e "${GREEN}   พิมพ์ menu เพื่อใช้งานได้เลย${NC}"
echo -e "${GREEN}══════════════════════════════════════${NC}"
echo ""
