#!/bin/bash
PORT="$1"
CURRENT=$(grep -oP 'bind\("0\.0\.0\.0", \K[0-9]+' /usr/local/bin/ws-ssh.py 2>/dev/null)
if [[ -z "$CURRENT" || -z "$PORT" || "$CURRENT" == "$PORT" ]]; then exit 1; fi
sed -i "s/bind(\"0.0.0.0\", $CURRENT)/bind(\"0.0.0.0\", $PORT)/" /usr/local/bin/ws-ssh.py 2>/dev/null
systemctl restart ws-ssh 2>/dev/null
sleep 1
if systemctl is-active ws-ssh >/dev/null 2>&1; then
  echo "OK $PORT"
else
  sed -i "s/bind(\"0.0.0.0\", $PORT)/bind(\"0.0.0.0\", $CURRENT)/" /usr/local/bin/ws-ssh.py 2>/dev/null
  systemctl restart ws-ssh 2>/dev/null
  exit 1
fi
