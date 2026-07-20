#!/usr/bin/env python3
import socket, select, os, signal, sys
from threading import Thread

def pipe(src, dst):
    """Zero-copy pipe between sockets"""
    try:
        while True:
            r, _, _ = select.select([src, dst], [], [], 120)
            if not r:
                break
            for s in r:
                data = s.recv(65536)
                if not data:
                    return
                if s is src:
                    dst.sendall(data)
                else:
                    src.sendall(data)
    except:
        pass
    finally:
        try: src.close()
        except: pass
        try: dst.close()
        except: pass

def handle(conn, addr):
    try:
        # TCP_NODELAY - reduce latency for SSH
        conn.setsockopt(socket.IPPROTO_TCP, socket.TCP_NODELAY, 1)
        conn.settimeout(30)
        
        data = conn.recv(4096)
        if not data:
            return
        
        ssh = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        ssh.settimeout(30)
        ssh.setsockopt(socket.IPPROTO_TCP, socket.TCP_NODELAY, 1)
        ssh.connect(("127.0.0.1", 22))
        
        # Detect protocol
        if b"Upgrade: websocket" in data or b"Sec-WebSocket-Key" in data:
            conn.sendall(b"HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n\r\n")
        elif b"CONNECT" in data:
            conn.sendall(b"HTTP/1.1 200 Connection Established\r\n\r\n")
        else:
            ssh.sendall(data)
        
        pipe(conn, ssh)
    except:
        pass
    finally:
        try: conn.close()
        except: pass

def main():
    # Set high file limit
    try:
        import resource
        resource.setrlimit(resource.RLIMIT_NOFILE, (100000, 100000))
    except:
        pass
    
    srv = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEPORT, 1)
    srv.bind(("0.0.0.0", 8080))
    srv.listen(50000)
    
    with open("/var/run/ws-ssh.pid", "w") as f:
        f.write(str(os.getpid()))
    
    while True:
        try:
            conn, addr = srv.accept()
            Thread(target=handle, args=(conn, addr), daemon=True).start()
        except:
            pass

if __name__ == "__main__":
    main()
