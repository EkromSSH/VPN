#!/bin/bash
# เปลี่ยนพอร์ต SSH WS (public port) v4
# - เปลี่ยน bind ใน haproxy.cfg (พอร์ตที่ลูกค้าใช้)
# - ถ้าชนกับ websocket internal (8080) → ย้าย internal อัตโนมัติ
export PATH=/usr/sbin:/usr/bin:/sbin:/bin
PORT="$1"

if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ -z "$PORT" ]; then
    echo "FAIL: พอร์ตไม่ถูกต้อง"
    exit 1
fi
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "FAIL: พอร์ตต้องอยู่ระหว่าง 1-65535"
    exit 1
fi

HAPROXY_CFG="/etc/haproxy/haproxy.cfg"
TUN_CFG="/etc/websocket/tun.conf"

# ── อ่านค่าปัจจุบัน ──
PUBLIC_PORT=$(grep -oP 'bind \*:\K[0-9]+' "$HAPROXY_CFG" 2>/dev/null | head -1)
INTERNAL_PORT=$(grep -oP 'listen_port:\s*\K[0-9]+' "$TUN_CFG" 2>/dev/null | head -1)
BACKEND_PORT=$(grep -oP 'ws-local 127\.0\.0\.1:\K[0-9]+' "$HAPROXY_CFG" 2>/dev/null | head -1)
[ -z "$INTERNAL_PORT" ] && INTERNAL_PORT="$BACKEND_PORT"

if [ -z "$PUBLIC_PORT" ]; then
    echo "FAIL: ไม่พบ bind ใน $HAPROXY_CFG"
    exit 1
fi

restart_services() {
    if command -v sudo >/dev/null 2>&1; then
        sudo -n systemctl restart haproxy 2>/dev/null
        sudo -n systemctl restart ws 2>/dev/null
    else
        systemctl restart haproxy 2>/dev/null
        systemctl restart ws 2>/dev/null
    fi
    sleep 2
}

rollback() {
    sed -i "s/bind \*:$PORT/bind *:$PUBLIC_PORT/" "$HAPROXY_CFG" 2>/dev/null
    [ -n "$SAVED_TUN" ] && echo "$SAVED_TUN" > "$TUN_CFG" 2>/dev/null
    sed -i "s/ws-local 127\.0\.0\.1:[0-9]*/ws-local 127.0.0.1:$INTERNAL_PORT/" "$HAPROXY_CFG" 2>/dev/null
    restart_services
    echo "FAIL: เปลี่ยนไม่สำเร็จ (ย้อนกลับ $PUBLIC_PORT)"
    exit 1
}

# ── พอร์ตเดียวกัน → ไม่ต้องทำ ──
if [ "$PUBLIC_PORT" = "$PORT" ]; then
    echo "OK $PORT (พอร์ตเดิม)"
    exit 0
fi

# ── ตรวจพอร์ตเป้าหมายถูกใช้อยู่หรือยัง ──
if ss -tln | grep -q ":$PORT "; then
    USED_BY=$(ss -tlnp | grep ":$PORT " | grep -oE 'users:\(\("[^"]+"' | head -1)
    # ถ้าถูก websocket เองใช้ (internal) → ย้าย internal ได้
    if echo "$USED_BY" | grep -q websocket && [ "$PORT" = "$INTERNAL_PORT" ]; then
        # หาพอร์ต internal ใหม่ที่ว่าง (8080 → 8081, 8081 → 8080, อื่นๆ +1)
        NEW_INT=$INTERNAL_PORT
        if [ "$INTERNAL_PORT" = "8080" ]; then NEW_INT=8081; fi
        if [ "$INTERNAL_PORT" = "8081" ]; then NEW_INT=8080; fi
        if [ "$NEW_INT" = "$INTERNAL_PORT" ]; then NEW_INT=$((INTERNAL_PORT + 1)); fi
        while ss -tln | grep -q ":$NEW_INT "; do NEW_INT=$((NEW_INT + 1)); done

        # ย้าย websocket internal
        sed -i "s/listen_port:\s*$INTERNAL_PORT/listen_port: $NEW_INT/" "$TUN_CFG" 2>/dev/null
        # อัปเดต haproxy backend
        sed -i "s/ws-local 127\.0\.0\.1:$INTERNAL_PORT/ws-local 127.0.0.1:$NEW_INT/" "$HAPROXY_CFG" 2>/dev/null
        # เปลี่ยน bind เป็นพอร์ตเป้าหมาย
        sed -i "s/bind \*:$PUBLIC_PORT/bind *:$PORT/" "$HAPROXY_CFG" 2>/dev/null
        SAVED_TUN=""
        restart_services
        if systemctl is-active haproxy >/dev/null 2>&1 && systemctl is-active ws >/dev/null 2>&1 && ss -tln | grep -q ":$PORT "; then
            echo "OK $PORT (websocket ภายในย้ายไป $NEW_INT อัตโนมัติ)"
            exit 0
        fi
        rollback
    else
        echo "FAIL: พอร์ต $PORT ถูกใช้โดย: ${USED_BY:-อื่นๆ}"
        exit 1
    fi
fi

# ── ปกติ: เปลี่ยน bind อย่างเดียว ──
sed -i "s/bind \*:$PUBLIC_PORT/bind *:$PORT/" "$HAPROXY_CFG" 2>/dev/null
restart_services
if systemctl is-active haproxy >/dev/null 2>&1 && ss -tln | grep -q ":$PORT "; then
    echo "OK $PORT"
    exit 0
fi
rollback
