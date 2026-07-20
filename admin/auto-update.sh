#!/bin/bash
# auto-update.sh - ตรวจสอบอัปเดตจาก GitHub ทุก 6 ชั่วโมง
GH="https://raw.githubusercontent.com/EkromSSH/VPN/main"
LOG="/var/log/ekrom-update.log"

echo "[$(date '+%Y-%m-%d %H:%M')] กำลังตรวจสอบอัปเดต..." >> $LOG

# Files to update
declare -A FILES
FILES["admin/index.php"]="/var/www/admin/index.php"
FILES["admin/ssh-admin"]="/usr/local/bin/ssh-admin"

for url_path in "${!FILES[@]}"; do
    local_path="${FILES[$url_path]}"
    wget -q -O "$local_path.tmp" "$GH/$url_path" 2>/dev/null
    if [[ $? -eq 0 && -s "$local_path.tmp" ]]; then
        # Check if file changed
        if ! diff -q "$local_path" "$local_path.tmp" &>/dev/null; then
            cp "$local_path.tmp" "$local_path"
            chmod +x "$local_path" 2>/dev/null
            echo "  ✅ อัปเดต: $url_path" >> $LOG
        fi
    fi
    rm -f "$local_path.tmp"
done

# Restart nginx if updated
systemctl is-active nginx &>/dev/null && systemctl reload nginx 2>/dev/null

echo "[$(date '+%Y-%m-%d %H:%M')] เสร็จสิ้น" >> $LOG
