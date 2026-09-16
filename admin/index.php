<?php
/**
 * EKROM SSH VPN - Modern Clean White Web Management Panel
 * ออกแบบใหม่: ธีมสีขาว สว่าง คลีน สบายตา การ์ดผู้ใช้สวยงาม ปุ่มต่ออายุและจัดการชัดเจน
 */
session_start();

$passFile = "/etc/ssh/.panel_pass";
$password = trim(@file_get_contents($passFile)) ?: "admin123";

// ── ตรวจสอบการเข้าสู่ระบบ ──
if (!isset($_SESSION["logged"])) {
    $error = "";
    if (isset($_POST["action"]) && $_POST["action"] === "login") {
        $inputPass = trim($_POST["pass"] ?? "");
        if ($inputPass === $password) {
            $_SESSION["logged"] = true;
            header("Location: /");
            exit;
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
        }
    }
    if (isset($_POST["pass"]) && !isset($_POST["action"])) {
        if ($_POST["pass"] === $password) {
            $_SESSION["logged"] = true;
            header("Location: /");
            exit;
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
        }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | EKROM SSH VPN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Prompt', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        body { background-color: #f1f5f9; }
    </style>
</head>
<body class="min-h-screen text-slate-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-3xl p-8 shadow-xl shadow-slate-200/60 relative">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-600 text-white text-3xl shadow-lg shadow-blue-500/25 mb-4">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">EKROM <span class="text-blue-600">SSH VPN</span></h1>
            <p class="text-slate-500 text-sm mt-1">แผงควบคุมและสร้างผู้ใช้งาน SSH (ธีมสว่าง)</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation text-lg shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2 uppercase tracking-wider">รหัสผ่านแผงควบคุม</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="pass" placeholder="กรอกรหัสผ่าน (เริ่มต้น: admin123)" required autofocus
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all text-sm">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/25 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>เข้าสู่ระบบจัดการ</span>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400">EKROM SSH VPN Management Panel · Port 8888</p>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// ── ออกจากระบบ ──
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: /");
    exit;
}

// ── Helper ส่งออก JSON ──
function json_resp($data) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── API: ผู้ใช้ออนไลน์ ──
if (($_GET["page"] ?? "") === "api_online") {
    $ol2 = [];
    exec("ps aux | grep 'sshd:' | grep -v 'listener\\|grep\\|root' | awk '{print $1}' | grep -v '^root$' | grep -v '^sshd$' | sort -u", $ol2);
    $t = 0;
    $f = "/etc/ssh/.ssh.db";
    if (file_exists($f)) {
        foreach (file($f) as $l) {
            if (preg_match("/^### /", $l)) $t++;
        }
    }
    json_resp(["total" => $t, "online" => count($ol2), "users" => array_values($ol2)]);
}

// ── การประมวลผลคำสั่ง (Action) ──
$action = $_POST["action"] ?? $_GET["action"] ?? "";
$isAjax = (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") 
          || (isset($_POST["ajax"]) || isset($_GET["ajax"]))
          || (strpos($_SERVER["HTTP_ACCEPT"] ?? "", "application/json") !== false);

if (!empty($action)) {
    $u = trim($_POST["user"] ?? $_POST["username"] ?? $_GET["user"] ?? "");
    $p = trim($_POST["pass"] ?? $_POST["password"] ?? $_GET["pass"] ?? "");

    // 1. สร้างบัญชี SSH
    if ($action === "create" && !empty($u)) {
        if (!preg_match("/^[a-zA-Z0-9_-]{3,32}$/", $u)) {
            $msg = "ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษหรือตัวเลข 3-32 ตัว";
            $isAjax ? json_resp(["status" => "error", "message" => $msg]) : header("Location: /?msg=fail");
        }
        if ($p === "") {
            $msg = "กรุณาระบุรหัสผ่าน";
            $isAjax ? json_resp(["status" => "error", "message" => $msg]) : header("Location: /?msg=fail");
        }
        $d = max(1, intval($_POST["days"] ?? $_GET["days"] ?? 30));
        $g = max(0, intval($_POST["gb"] ?? $_GET["gb"] ?? 200));
        $mip = max(0, intval($_POST["ip"] ?? $_GET["ip"] ?? 2));

        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/ssh-admin create " . escapeshellarg($u) . " " . escapeshellarg($p) . " $d $g $mip 2>&1", $out, $ret);

        if ($ret === 0) {
            $isAjax ? json_resp([
                "status" => "success",
                "message" => "สร้างบัญชี SSH: $u เรียบร้อยแล้ว!",
                "data" => [
                    "username" => $u,
                    "password" => $p,
                    "days" => $d,
                    "limit_gb" => $g,
                    "limit_ip" => $mip,
                    "exp_date" => date("Y-m-d", strtotime("+$d days"))
                ]
            ]) : header("Location: /?msg=ok_" . urlencode($u));
        } else {
            $err = trim(implode(" ", $out)) ?: "ไม่สามารถสร้างผู้ใช้ได้";
            $isAjax ? json_resp(["status" => "error", "message" => $err]) : header("Location: /?msg=fail");
        }
    }

    // 2. ลบบัญชี SSH
    if (($action === "delete" || $action === "del") && !empty($u)) {
        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/ssh-admin delete " . escapeshellarg($u) . " 2>&1", $out, $ret);
        if ($ret === 0) {
            $isAjax ? json_resp(["status" => "success", "message" => "ลบบัญชี $u ออกจากเซิร์ฟเวอร์เรียบร้อยแล้ว"]) : header("Location: /?msg=del_" . urlencode($u));
        } else {
            $isAjax ? json_resp(["status" => "error", "message" => "ลบบัญชีไม่สำเร็จ"]) : header("Location: /?msg=fail");
        }
    }

    // 3. เปลี่ยนรหัสผ่าน SSH
    if ($action === "passwd" && !empty($u)) {
        if ($p === "") {
            $isAjax ? json_resp(["status" => "error", "message" => "กรุณาระบุรหัสผ่านใหม่"]) : header("Location: /?msg=pf");
        }
        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/ssh-admin passwd " . escapeshellarg($u) . " " . escapeshellarg($p) . " 2>&1", $out, $ret);
        if ($ret === 0) {
            $isAjax ? json_resp(["status" => "success", "message" => "เปลี่ยนรหัสผ่านบัญชี $u สำเร็จ"]) : header("Location: /?msg=pw_" . urlencode($u));
        } else {
            $isAjax ? json_resp(["status" => "error", "message" => "เปลี่ยนรหัสผ่านไม่สำเร็จ"]) : header("Location: /?msg=pf");
        }
    }

    // 4. ต่ออายุบัญชี SSH
    if ($action === "renew" && !empty($u)) {
        $d = max(1, intval($_POST["days"] ?? $_GET["days"] ?? 30));
        $g = isset($_POST["gb"]) ? intval($_POST["gb"]) : (isset($_GET["gb"]) ? intval($_GET["gb"]) : -1);
        $mip = isset($_POST["ip"]) ? intval($_POST["ip"]) : (isset($_GET["ip"]) ? intval($_GET["ip"]) : -1);

        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/ssh-admin renew " . escapeshellarg($u) . " $d $g $mip 2>&1", $out, $ret);
        if ($ret === 0) {
            $extraTxt = "";
            if ($g >= 0) $extraTxt .= " (เน็ต: " . ($g > 0 ? "$g GB" : "ไม่จำกัด") . ")";
            if ($mip >= 0) $extraTxt .= " (ลิมิต: " . ($mip > 0 ? "$mip เครื่อง" : "ไม่จำกัด") . ")";
            $isAjax ? json_resp(["status" => "success", "message" => "ต่ออายุบัญชี $u เพิ่ม $d วัน$extraTxt เรียบร้อย!"]) : header("Location: /?msg=renew_" . urlencode($u));
        } else {
            $isAjax ? json_resp(["status" => "error", "message" => "ต่ออายุไม่สำเร็จ"]) : header("Location: /?msg=fail");
        }
    }

    // 4.1 แก้ไขโควต้า GB และ ลิมิต IP
    if ($action === "limit" && !empty($u)) {
        $g = max(0, intval($_POST["gb"] ?? $_GET["gb"] ?? 0));
        $mip = max(0, intval($_POST["ip"] ?? $_GET["ip"] ?? 0));
        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/ssh-admin limit " . escapeshellarg($u) . " $g $mip 2>&1", $out, $ret);
        if ($ret === 0) {
            $gbTxt = $g > 0 ? "$g GB" : "ไม่จำกัด";
            $ipTxt = $mip > 0 ? "$mip เครื่อง" : "ไม่จำกัด";
            $isAjax ? json_resp(["status" => "success", "message" => "อัปเดตลิมิตบัญชี $u เป็น (เน็ต: $gbTxt / เครื่อง: $ipTxt) เรียบร้อย!"]) : header("Location: /?msg=limit_ok");
        } else {
            $isAjax ? json_resp(["status" => "error", "message" => "อัปเดตลิมิตไม่สำเร็จ"]) : header("Location: /?msg=fail");
        }
    }

    // 5. ตัดการเชื่อมต่อ (Kick)
    if ($action === "kick" && !empty($u)) {
        exec("/usr/local/bin/su-exec pkill -u " . escapeshellarg($u) . " 2>/dev/null");
        $isAjax ? json_resp(["status" => "success", "message" => "ตัดการเชื่อมต่อของ $u เรียบร้อยแล้ว"]) : header("Location: /?msg=kick_" . urlencode($u));
    }

    // 6. เปลี่ยนพอร์ต WebSocket
    if ($action === "port" && !empty($p)) {
        $newPort = intval($p);
        if ($newPort < 1 || $newPort > 65535) {
            $isAjax ? json_resp(["status" => "error", "message" => "พอร์ตต้องอยู่ระหว่าง 1-65535"]) : header("Location: /?msg=pf");
        }
        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/change-port-web $newPort 2>&1", $out, $ret);
        if ($ret === 0) {
            $isAjax ? json_resp(["status" => "success", "message" => "เปลี่ยนพอร์ต WebSocket เป็น $newPort สำเร็จ!"]) : header("Location: /?msg=port_" . urlencode($newPort));
        } else {
            $err = trim(implode(" ", $out)) ?: "เปลี่ยนพอร์ตไม่สำเร็จ";
            $isAjax ? json_resp(["status" => "error", "message" => $err]) : header("Location: /?msg=pf");
        }
    }

    // 7. เปลี่ยนรหัสผ่านแผงควบคุม
    if ($action === "chpass") {
        $old = trim($_POST["old"] ?? $_GET["old"] ?? "");
        $new = trim($_POST["new"] ?? $_GET["new"] ?? "");
        if ($old !== $password) {
            $isAjax ? json_resp(["status" => "error", "message" => "รหัสผ่านเดิมไม่ถูกต้อง"]) : header("Location: /?msg=pwd_fail");
        }
        if (strlen($new) < 4) {
            $isAjax ? json_resp(["status" => "error", "message" => "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 4 ตัวอักษร"]) : header("Location: /?msg=pwd_fail");
        }
        $out = [];
        $ret = 0;
        exec("/usr/local/bin/su-exec /usr/local/bin/chpass-web " . escapeshellarg($new) . " 2>&1", $out, $ret);
        if ($ret === 0) {
            $isAjax ? json_resp(["status" => "success", "message" => "เปลี่ยนรหัสผ่านแผงควบคุมสำเร็จแล้ว!"]) : header("Location: /?msg=pwd_ok");
        } else {
            $isAjax ? json_resp(["status" => "error", "message" => "เปลี่ยนรหัสผ่านไม่สำเร็จ"]) : header("Location: /?msg=pwd_fail");
        }
    }
}

// ── ดึงข้อมูลเซิร์ฟเวอร์ & ผู้ใช้ ──
$ip = trim(exec("curl -s ipv4.icanhazip.com") ?: (exec("hostname -I | awk '{print $1}'") ?: "82.26.104.61"));
$domain = trim(@file_get_contents("/etc/xray/domain") ?: "server.idavpn.win");
$ram_t = trim(exec("free -h | grep Mem | awk '{print $2}'") ?: "1G");
$ram_u = trim(exec("free -h | grep Mem | awk '{print $3}'") ?: "500M");
$uptime = trim(exec("uptime -p | cut -d' ' -f2-") ?: "up");
$port = trim(exec("grep -oP 'listen\\s+\\K[0-9]+' /etc/nginx/conf.d/ssh-ws-80.conf 2>/dev/null | head -1") ?: "80");

// อ่านข้อมูลผู้ใช้จาก /etc/ssh/.ssh.db
$usersList = [];
$dbFile = "/etc/ssh/.ssh.db";
if (file_exists($dbFile)) {
    $lines = file($dbFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^###\\s+([^\\s]+)\\s+(\\d+)(?:\\s+(\\d+)\\s+(\\d+))?/', $line, $m)) {
            $uName = $m[1];
            $expTs = (int)$m[2];
            $limitGb = isset($m[3]) ? (int)$m[3] : 0;
            $limitIp = isset($m[4]) ? (int)$m[4] : 0;
            $usersList[$uName] = [
                "username" => $uName,
                "exp_ts" => $expTs,
                "limit_gb" => $limitGb,
                "limit_ip" => $limitIp
            ];
        }
    }
}

// ตรวจสอบสถานะออนไลน์
$ol = [];
exec("ps aux | grep 'sshd:' | grep -v 'listener\\|grep\\|root' | awk '{print $1}' | grep -v '^root$' | grep -v '^sshd$' | sort -u", $ol);
$onlineMap = array_flip($ol);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EKROM SSH VPN - แผงควบคุมระบบ (ธีมสว่าง)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Prompt', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        body { background-color: #f8fafc; color: #0f172a; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .swal2-popup { font-family: 'Prompt', sans-serif !important; border-radius: 1.25rem !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- Top Navigation Bar (คลีน สีขาว สว่างตา) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Server Status -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white text-lg shadow-md shadow-blue-600/20">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg text-slate-900 tracking-tight">EKROM <span class="text-blue-600">SSH</span></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span> ออนไลน์
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-mono flex items-center gap-1.5">
                            <i class="fa-solid fa-globe text-[10px] text-blue-500"></i> <?= htmlspecialchars($domain) ?> (<?= htmlspecialchars($ip) ?>)
                        </p>
                    </div>
                </div>

                <!-- Action Buttons: Settings & Logout -->
                <div class="flex items-center gap-2">
                    <button onclick="openSettingsModal()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer">
                        <i class="fa-solid fa-gear text-slate-500"></i>
                        <span>ตั้งค่าระบบ</span>
                    </button>
                    <a href="?logout=1" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all text-sm" title="ออกจากระบบ">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 space-y-6">

        <!-- Status & Stats Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium">บัญชีทั้งหมด</p>
                    <p class="text-xl font-bold text-slate-900"><?= count($usersList) ?> <span class="text-xs font-normal text-slate-400">คน</span></p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-signal"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium">ออนไลน์ตอนนี้</p>
                    <p id="stat-online-count" class="text-xl font-bold text-emerald-600"><?= count($ol) ?> <span class="text-xs font-normal text-slate-400">คน</span></p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
                <div>
                    <p class="text-[11px] text-slate-500 font-medium">พอร์ต WebSocket</p>
                    <p class="text-xl font-bold text-amber-600 font-mono"><?= htmlspecialchars($port) ?></p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div class="truncate">
                    <p class="text-[11px] text-slate-500 font-medium">RAM / อัปไทม์</p>
                    <p class="text-sm font-bold text-slate-800 font-mono mt-0.5"><?= htmlspecialchars($ram_u) ?> / <?= htmlspecialchars($ram_t) ?></p>
                </div>
            </div>
        </div>

        <!-- Notification Banner from URL -->
        <?php
        $m = $_GET["msg"] ?? "";
        if ($m):
            $bannerBg = "bg-emerald-50 border-emerald-200 text-emerald-800";
            $bannerText = "";
            if (strpos($m, "ok_") === 0) $bannerText = "สร้างบัญชี SSH: " . htmlspecialchars(substr($m, 3)) . " เรียบร้อยแล้ว!";
            elseif (strpos($m, "del_") === 0) $bannerText = "ลบบัญชี " . htmlspecialchars(substr($m, 4)) . " ออกแล้ว";
            elseif (strpos($m, "pw_") === 0) $bannerText = "เปลี่ยนรหัสผ่านสำเร็จ";
            elseif (strpos($m, "renew_") === 0) $bannerText = "ต่ออายุบัญชี " . htmlspecialchars(substr($m, 6)) . " สำเร็จ";
            elseif (strpos($m, "port_") === 0) $bannerText = "เปลี่ยนพอร์ต WebSocket เป็น " . htmlspecialchars(substr($m, 5)) . " เรียบร้อย!";
            elseif ($m === "pwd_ok") $bannerText = "เปลี่ยนรหัสผ่านแผงควบคุมสำเร็จ";
            elseif ($m === "fail" || $m === "pf" || $m === "pwd_fail") {
                $bannerBg = "bg-red-50 border-red-200 text-red-700";
                $bannerText = "เกิดข้อผิดพลาดในการทำรายการ";
            }
            if ($bannerText):
        ?>
        <div class="p-4 rounded-2xl border <?= $bannerBg ?> text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5 font-medium">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span><?= $bannerText ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-700 text-sm">&times;</button>
        </div>
        <?php endif; endif; ?>

        <!-- ========================================== -->
        <!-- ส่วนที่ 1: ฟอร์มสร้าง SSH จุดเดียว ไม่สับสน -->
        <!-- ========================================== -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-sm shadow-blue-500/30">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                        <span>สร้างบัญชี SSH ใหม่</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">พิมพ์ชื่อผู้ใช้และรหัสผ่าน หรือคลิกปุ่มสุ่มข้อมูลอัตโนมัติด้านขวา</p>
                </div>

                <button type="button" onclick="autoGenerateUserPass()" class="self-start sm:self-auto px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-xl border border-blue-200 flex items-center gap-1.5 transition-all cursor-pointer">
                    <i class="fa-solid fa-shuffle text-blue-600"></i>
                    <span>🎲 สุ่ม User & Pass อัตโนมัติ</span>
                </button>
            </div>

            <form id="create-user-form" onsubmit="submitCreateUser(event)" class="space-y-4">
                
                <!-- User & Pass Inputs (ช่องว่างเปล่า มีแค่ข้อความตัวอย่าง ไม่ใส่ค่าค้างไว้) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">ชื่อผู้ใช้ (Username): <span class="text-red-500">*</span></label>
                        <input type="text" id="inp_user" name="user" required placeholder="ตัวอย่าง: user01 (พิมพ์ชื่อที่ต้องการ หรือกดปุ่มสุ่ม)"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">รหัสผ่าน (Password): <span class="text-red-500">*</span></label>
                        <input type="text" id="inp_pass" name="pass" required placeholder="ตัวอย่าง: 123456 (พิมพ์รหัสที่ต้องการ หรือกดปุ่มสุ่ม)"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all">
                    </div>
                </div>

                <!-- Days Selection -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">ระยะเวลาใช้งาน (วัน):</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="number" id="inp_days" name="days" value="30" min="1" max="3650"
                            class="w-28 px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-blue-600">
                        
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" onclick="setDays(3)" class="day-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded-xl font-medium transition-all">3 วัน</button>
                            <button type="button" onclick="setDays(7)" class="day-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded-xl font-medium transition-all">7 วัน</button>
                            <button type="button" onclick="setDays(30)" class="day-btn px-3 py-1.5 bg-blue-600 text-white text-xs rounded-xl font-semibold shadow-sm transition-all">30 วัน (1 เดือน) ⭐</button>
                            <button type="button" onclick="setDays(60)" class="day-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded-xl font-medium transition-all">60 วัน</button>
                            <button type="button" onclick="setDays(365)" class="day-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded-xl font-medium transition-all">1 ปี</button>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Advanced Settings: Quota GB & IP Limit (Default: 200 GB / 2 IP) -->
                <div class="border border-slate-200/80 rounded-2xl p-3.5 bg-slate-50/60">
                    <button type="button" onclick="toggleAdvancedSettings()" class="flex items-center justify-between w-full text-left text-xs font-semibold text-slate-700 hover:text-blue-600 transition-all cursor-pointer">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-blue-600"></i>
                            <span>ตั้งค่าโควต้าเน็ตและจำกัดเครื่อง (ค่าเริ่มต้น: 200 GB / 2 IP)</span>
                        </span>
                        <span id="adv-arrow" class="text-slate-400 text-[10px]"><i class="fa-solid fa-chevron-down"></i></span>
                    </button>
                    
                    <div id="adv-box" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3 pt-3 border-t border-slate-200">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">โควต้า Data (GB):</label>
                            <input type="number" id="inp_gb" name="gb" value="200" min="0" max="10000"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:border-blue-600">
                            <span class="text-[11px] text-slate-400 mt-1 block">ใส่ 0 = ไม่จำกัดจำนวนเน็ต</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">จำกัดจำนวนเครื่อง (Max IPs):</label>
                            <input type="number" id="inp_ip" name="ip" value="2" min="0" max="100"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono text-slate-900 focus:outline-none focus:border-blue-600">
                            <span class="text-[11px] text-slate-400 mt-1 block">ใส่ 0 = ไม่จำกัดจำนวนเครื่อง</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="btn-create-submit" class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/25 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check"></i>
                    <span>✨ ยืนยันสร้างบัญชี SSH ทันที</span>
                </button>

            </form>

        </div>

        <!-- ========================================== -->
        <!-- ส่วนที่ 2: จัดการ ต่ออายุ แก้ไขรหัสผ่าน หรือลบบัญชี -->
        <!-- ========================================== -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
            
            <!-- Header Section พร้อมปุ่มลัด 3 ปุ่มหลักด้านขวา -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shadow-sm shadow-emerald-500/30">
                            <i class="fa-solid fa-users-gear"></i>
                        </span>
                        <span>จัดการบัญชีผู้ใช้งาน SSH</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">จัดการ ต่ออายุ แก้ไขรหัสผ่าน หรือลบบัญชี (กดจากปุ่มด่วนด้านขวา หรือกดที่การ์ดลูกค้าได้ทันที)</p>
                </div>

                <!-- เมนูปุ่มด่วน 4 ปุ่มเด่นชัด -->
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="openQuickRenewModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm hover:shadow flex items-center gap-1.5 transition-all cursor-pointer">
                        <i class="fa-solid fa-calendar-plus text-xs"></i>
                        <span>➕ ต่ออายุผู้ใช้</span>
                    </button>
                    <button onclick="openQuickLimitModal()" class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-sm hover:shadow flex items-center gap-1.5 transition-all cursor-pointer">
                        <i class="fa-solid fa-sliders text-xs"></i>
                        <span>⚙️ ปรับลิมิต (GB/IP)</span>
                    </button>
                    <button onclick="openQuickPasswdModal()" class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold rounded-xl border border-purple-200 flex items-center gap-1.5 transition-all cursor-pointer">
                        <i class="fa-solid fa-key text-purple-600"></i>
                        <span>🔑 เปลี่ยนรหัสผ่าน</span>
                    </button>
                    <button onclick="openQuickDeleteModal()" class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl border border-red-200 flex items-center gap-1.5 transition-all cursor-pointer">
                        <i class="fa-solid fa-trash-can text-red-500"></i>
                        <span>🗑️ ลบบัญชี</span>
                    </button>
                </div>
            </div>

            <!-- Toolbar: Search Input & View Toggle -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input id="search-input" type="text" placeholder="พิมพ์ค้นหาชื่อผู้ใช้ (เจอทันที)..." onkeyup="filterUserList()"
                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-blue-600 transition-all">
                </div>

                <!-- View Switcher -->
                <div class="flex items-center gap-1 self-end sm:self-auto bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button type="button" onclick="setViewMode('card')" id="btn-view-card" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-blue-700 shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-grip"></i>
                        <span>แบบการ์ด (ดูง่าย)</span>
                    </button>
                    <button type="button" onclick="setViewMode('table')" id="btn-view-table" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-table-list"></i>
                        <span>แบบตาราง</span>
                    </button>
                </div>
            </div>

            <!-- 1. มุมมองแบบการ์ด (CARD GRID VIEW - สวย คลีน ตัวหนังสือและปุ่มใหญ่ สัมผัสง่าย) -->
            <div id="view-card-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                <?php 
                if (empty($usersList)):
                ?>
                <div class="col-span-full py-12 text-center text-slate-400 bg-slate-50 rounded-2xl border border-slate-200">
                    <i class="fa-solid fa-user-slash text-3xl mb-2 block text-slate-300"></i>
                    <span>ยังไม่มีบัญชีผู้ใช้งานในระบบ</span>
                </div>
                <?php 
                else:
                    foreach ($usersList as $uName => $info):
                        $isOnline = isset($onlineMap[$uName]);
                        $expTs = $info["exp_ts"];
                        $diff = $expTs - time();
                        $isExpired = $diff <= 0;
                        
                        if ($isExpired) {
                            $remText = "<span class=\"text-red-600 font-bold\"><i class=\"fa-solid fa-triangle-exclamation mr-1\"></i>หมดอายุแล้ว</span>";
                        } else {
                            $dLeft = floor($diff / 86400);
                            $hLeft = floor(($diff % 86400) / 3600);
                            $remText = "เหลือ $dLeft วัน " . ($dLeft < 3 ? "$hLeft ชม." : "");
                        }

                        $gbLabel = $info["limit_gb"] > 0 ? $info["limit_gb"] . " GB" : "ไม่จำกัด";
                        $ipLabel = $info["limit_ip"] > 0 ? $info["limit_ip"] . " เครื่อง" : "ไม่จำกัด";
                ?>
                <div class="user-item bg-slate-50/80 hover:bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-sm transition-all space-y-3.5" data-u="<?= strtolower(htmlspecialchars($uName)) ?>">
                    
                    <!-- Top Row: Username & Status -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold shrink-0">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-bold text-base text-slate-900"><?= htmlspecialchars($uName) ?></span>
                                    <button onclick="copySingleText('<?= htmlspecialchars($uName) ?>')" class="text-slate-400 hover:text-blue-600 text-xs p-1" title="คัดลอกชื่อผู้ใช้">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                                <span class="text-[11px] text-slate-500 font-mono">หมดอายุ: <?= date("Y-m-d", $expTs) ?></span>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <?php if ($isOnline): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> ออนไลน์
                        </span>
                        <?php elseif ($isExpired): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 shrink-0">
                            หมดอายุแล้ว
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-200 text-slate-600 shrink-0">
                            ออฟไลน์
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Middle: Details Pills -->
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 font-medium flex items-center gap-1.5 shadow-2xs">
                            <i class="fa-regular fa-clock text-blue-600"></i>
                            <span><?= $remText ?></span>
                        </span>
                        <span class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 font-medium flex items-center gap-1.5 shadow-2xs">
                            <i class="fa-solid fa-database text-purple-600"></i>
                            <span>โควต้า: <?= $gbLabel ?></span>
                        </span>
                        <span class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-700 font-medium flex items-center gap-1.5 shadow-2xs">
                            <i class="fa-solid fa-mobile-screen text-amber-600"></i>
                            <span>ลิมิต: <?= $ipLabel ?></span>
                        </span>
                    </div>

                    <!-- Bottom: Action Buttons (ปุ่มต่ออายุเด่นชัดที่สุด + ส่งลูกค้า + รหัส + ลบ) -->
                    <div class="pt-2 border-t border-slate-200/80 flex items-center gap-2 flex-wrap">
                        
                        <!-- ➕ ปุ่มต่ออายุ สีเขียวเด่นสะดุดตา มีชื่อปุ่มบอกชัดเจน -->
                        <button onclick="openRenewModal('<?= htmlspecialchars($uName) ?>', <?= $info['limit_gb'] ?>, <?= $info['limit_ip'] ?>)"
                            class="flex-1 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm hover:shadow flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <i class="fa-solid fa-calendar-plus text-xs"></i>
                            <span>➕ ต่ออายุ</span>
                        </button>

                        <!-- ⚙️ ปุ่มปรับลิมิตเน็ต / IP -->
                        <button onclick="openLimitModal('<?= htmlspecialchars($uName) ?>', <?= $info['limit_gb'] ?>, <?= $info['limit_ip'] ?>)"
                            class="py-2 px-3 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold border border-purple-200 flex items-center gap-1.5 transition-all cursor-pointer" title="ปรับเน็ต GB / ลิมิต IP">
                            <i class="fa-solid fa-sliders text-xs"></i>
                            <span>⚙️ ลิมิต</span>
                        </button>

                        <!-- 📋 ปุ่มคัดลอกส่งลูกค้า -->
                        <button onclick="copyCustomerFormat('<?= htmlspecialchars($uName) ?>', '******', '<?= date('Y-m-d', $expTs) ?>', '<?= $gbLabel ?>', '<?= $ipLabel ?>')"
                            class="py-2 px-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-semibold border border-blue-200 flex items-center gap-1.5 transition-all cursor-pointer" title="คัดลอกข้อมูลส่งลูกค้า">
                            <i class="fa-solid fa-share-nodes text-xs"></i>
                            <span>ส่งลูกค้า</span>
                        </button>

                        <!-- 🔑 ปุ่มเปลี่ยนรหัส -->
                        <button onclick="openPasswdModal('<?= htmlspecialchars($uName) ?>')"
                            class="py-2 px-3 bg-white hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-medium border border-slate-200 flex items-center gap-1.5 transition-all cursor-pointer" title="เปลี่ยนรหัสผ่าน">
                            <i class="fa-solid fa-key text-purple-600"></i>
                            <span>รหัส</span>
                        </button>

                        <!-- 🛑 ปุ่มเตะออก (ถ้าออนไลน์) -->
                        <?php if ($isOnline): ?>
                        <button onclick="kickUser('<?= htmlspecialchars($uName) ?>')"
                            class="py-2 px-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-xs font-medium border border-amber-200 flex items-center gap-1 transition-all cursor-pointer" title="ตัดการเชื่อมต่อ">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                        <?php endif; ?>

                        <!-- 🗑️ ปุ่มลบ -->
                        <button onclick="deleteUser('<?= htmlspecialchars($uName) ?>')"
                            class="py-2 px-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-medium border border-red-200 flex items-center gap-1 transition-all cursor-pointer" title="ลบบัญชีนี้">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>

                    </div>

                </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>

            <!-- 2. มุมมองแบบตาราง (TABLE VIEW - สลับดูได้สำหรับคนชอบตาราง) -->
            <div id="view-table-container" class="hidden overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-center w-12">#</th>
                            <th class="px-4 py-3">ชื่อผู้ใช้ (Username)</th>
                            <th class="px-4 py-3 text-center">สถานะ</th>
                            <th class="px-4 py-3">ลิมิต (GB/IP)</th>
                            <th class="px-4 py-3">วันหมดอายุ</th>
                            <th class="px-4 py-3 text-center">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        if (empty($usersList)):
                        ?>
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                <span>ยังไม่มีบัญชีผู้ใช้งานในระบบ</span>
                            </td>
                        </tr>
                        <?php 
                        else:
                            $ti = 1;
                            foreach ($usersList as $uName => $info):
                                $isOnline = isset($onlineMap[$uName]);
                                $expTs = $info["exp_ts"];
                                $diff = $expTs - time();
                                $isExpired = $diff <= 0;
                                $remTextT = $isExpired ? "<span class=\"text-red-600 font-bold\">หมดอายุ</span>" : "เหลือ " . floor($diff / 86400) . " วัน";
                                $gbLabel = $info["limit_gb"] > 0 ? $info["limit_gb"] . " GB" : "ไม่จำกัด";
                                $ipLabel = $info["limit_ip"] > 0 ? $info["limit_ip"] . " เครื่อง" : "ไม่จำกัด";
                        ?>
                        <tr class="user-row hover:bg-slate-50/80 transition-all" data-u="<?= strtolower(htmlspecialchars($uName)) ?>">
                            <td class="px-4 py-3.5 text-center text-slate-400 font-mono text-xs"><?= $ti++ ?></td>
                            <td class="px-4 py-3.5 font-mono font-semibold text-slate-900 text-sm"><?= htmlspecialchars($uName) ?></td>
                            <td class="px-4 py-3.5 text-center">
                                <?= $isOnline ? '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">ออนไลน์</span>' : ($isExpired ? '<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-red-50 text-red-600">หมดอายุ</span>' : '<span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500">ออฟไลน์</span>') ?>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600"><?= $gbLabel ?> / <?= $ipLabel ?></td>
                            <td class="px-4 py-3.5 font-mono text-xs"><?= date("Y-m-d", $expTs) ?> (<?= $remTextT ?>)</td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="openRenewModal('<?= htmlspecialchars($uName) ?>', <?= $info['limit_gb'] ?>, <?= $info['limit_ip'] ?>)" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition-all flex items-center gap-1" title="ต่ออายุ">
                                        <i class="fa-solid fa-calendar-plus text-xs"></i> <span>ต่ออายุ</span>
                                    </button>
                                    <button onclick="openLimitModal('<?= htmlspecialchars($uName) ?>', <?= $info['limit_gb'] ?>, <?= $info['limit_ip'] ?>)" class="px-2.5 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-bold border border-purple-200 flex items-center gap-1" title="ปรับลิมิตเน็ต/IP">
                                        <i class="fa-solid fa-sliders text-xs"></i> <span>ลิมิต</span>
                                    </button>
                                    <button onclick="copyCustomerFormat('<?= htmlspecialchars($uName) ?>', '******', '<?= date('Y-m-d', $expTs) ?>', '<?= $gbLabel ?>', '<?= $ipLabel ?>')" class="px-2 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold border border-blue-200" title="ส่งลูกค้า">
                                        ส่งลูกค้า
                                    </button>
                                    <button onclick="openPasswdModal('<?= htmlspecialchars($uName) ?>')" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs border border-slate-200">
                                        <i class="fa-solid fa-key text-purple-600"></i>
                                    </button>
                                    <button onclick="deleteUser('<?= htmlspecialchars($uName) ?>')" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs border border-red-200">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-500">
        EKROM SSH VPN Management Panel · Version 3.1 Clean Light
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const SERVER_DOMAIN = <?= json_encode($domain) ?>;
        const SERVER_IP = <?= json_encode($ip) ?>;
        const WS_PORT = <?= json_encode($port) ?>;
        window.USERS_DATA = <?= json_encode($usersList) ?>;

        // สลับมุมมอง การ์ด vs ตาราง
        function setViewMode(mode) {
            const cardBox = document.getElementById("view-card-container");
            const tableBox = document.getElementById("view-table-container");
            const btnCard = document.getElementById("btn-view-card");
            const btnTable = document.getElementById("btn-view-table");

            if (mode === "table") {
                cardBox.classList.add("hidden");
                tableBox.classList.remove("hidden");
                btnTable.className = "px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-blue-700 shadow-sm transition-all flex items-center gap-1.5 cursor-pointer";
                btnCard.className = "px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5 cursor-pointer";
            } else {
                tableBox.classList.add("hidden");
                cardBox.classList.remove("hidden");
                btnCard.className = "px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-blue-700 shadow-sm transition-all flex items-center gap-1.5 cursor-pointer";
                btnTable.className = "px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5 cursor-pointer";
            }
        }

        // สุ่มชื่อและรหัสผ่านอัตโนมัติ
        function autoGenerateUserPass() {
            const rUser = "user" + Math.floor(1000 + Math.random() * 9000);
            const rPass = Math.floor(100000 + Math.random() * 900000).toString();
            document.getElementById("inp_user").value = rUser;
            document.getElementById("inp_pass").value = rPass;
        }

        // ตั้งค่าวันใช้งานด่วน
        function setDays(days) {
            document.getElementById("inp_days").value = days;
            document.querySelectorAll(".day-btn").forEach(btn => {
                btn.className = "day-btn px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded-xl font-medium transition-all";
            });
            event.target.className = "day-btn px-3 py-1.5 bg-blue-600 text-white text-xs rounded-xl font-semibold shadow-sm transition-all";
        }

        // เปิด/ปิดการตั้งค่าขั้นสูง (GB/IP)
        function toggleAdvancedSettings() {
            const box = document.getElementById("adv-box");
            const arrow = document.getElementById("adv-arrow");
            if (box.classList.contains("hidden")) {
                box.classList.remove("hidden");
                arrow.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
            } else {
                box.classList.add("hidden");
                arrow.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';
            }
        }

        // ค้นหาในตารางและการ์ด
        function filterUserList() {
            const query = document.getElementById("search-input").value.toLowerCase().trim();
            document.querySelectorAll(".user-item, .user-row").forEach(el => {
                const u = el.getAttribute("data-u");
                el.style.display = u.includes(query) ? "" : "none";
            });
        }

        // ยืนยันสร้างผู้ใช้ SSH
        function submitCreateUser(e) {
            e.preventDefault();
            const u = document.getElementById("inp_user").value.trim();
            const p = document.getElementById("inp_pass").value.trim();
            const d = document.getElementById("inp_days").value;
            const g = document.getElementById("inp_gb").value;
            const i = document.getElementById("inp_ip").value;

            if (!u || !p) {
                Swal.fire({ icon: 'warning', title: 'ข้อมูลไม่ครบ', text: 'กรุณาระบุ Username และ Password' });
                return;
            }

            Swal.fire({
                title: 'กำลังสร้างบัญชีบนเซิร์ฟเวอร์...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const fd = new FormData();
            fd.append("action", "create");
            fd.append("ajax", "1");
            fd.append("user", u);
            fd.append("pass", p);
            fd.append("days", d);
            fd.append("gb", g);
            fd.append("ip", i);

            fetch("", { method: "POST", body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status === "success") {
                        const data = res.data || {};
                        const gbText = data.limit_gb > 0 ? `${data.limit_gb} GB` : 'ไม่จำกัด';
                        const ipText = data.limit_ip > 0 ? `${data.limit_ip} เครื่อง` : 'ไม่จำกัด';

                        const customerText = `🛡️ ข้อมูลบัญชี SSH VPN
──────────────────────────────
👤 ชื่อผู้ใช้: ${data.username}
🔑 รหัสผ่าน: ${data.password}
🌐 Host / IP: ${SERVER_DOMAIN}
🔌 SSH Direct: 22, 83, 143
⚡ SSH WebSocket: ${WS_PORT}, 443
🎮 UDPGW: 7100, 7200, 7300
📅 หมดอายุ: ${data.exp_date} (${data.days} วัน)
📦 โควต้าเน็ต: ${gbText}
📱 จำกัด: ${ipText}
──────────────────────────────`;

                        Swal.fire({
                            icon: 'success',
                            title: '🎉 สร้างบัญชีสำเร็จ!',
                            html: `
                                <div class="text-left text-xs space-y-3 p-1">
                                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 font-mono text-[12px] text-slate-800 whitespace-pre leading-relaxed select-all">
${escapeHtml(customerText)}
                                    </div>
                                    <button onclick="copyCustomerFormatText(\`${customerText.replace(/`/g, "\\`")}\`)" 
                                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all shadow-md cursor-pointer">
                                        <i class="fa-solid fa-copy"></i>
                                        <span>📋 คัดลอกข้อความสำหรับส่งให้ลูกค้า</span>
                                    </button>
                                </div>
                            `,
                            confirmButtonText: 'ปิดหน้าต่าง',
                            confirmButtonColor: '#2563eb'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: res.message || 'เกิดข้อผิดพลาด' });
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
                });
        }

        // ฟังก์ชันช่วยบวก GB
        function addGbValue(inputId, amount) {
            const el = document.getElementById(inputId);
            if (!el) return;
            let val = parseInt(el.value) || 0;
            el.value = Math.max(0, val + amount);
        }

        // เมนูด่วน: ➕ ต่ออายุผู้ใช้ (Quick Renew Modal)
        function openQuickRenewModal() {
            let optionsHtml = '';
            let firstUser = '';
            for (const [u, info] of Object.entries(window.USERS_DATA || {})) {
                if (!firstUser) firstUser = u;
                const expDate = new Date(info.exp_ts * 1000).toISOString().split('T')[0];
                optionsHtml += `<option value="${escapeHtml(u)}" data-gb="${info.limit_gb}" data-ip="${info.limit_ip}">${escapeHtml(u)} (หมดอายุ: ${expDate})</option>`;
            }
            if (!optionsHtml) {
                Swal.fire({ icon: 'info', title: 'ยังไม่มีบัญชี', text: 'กรุณาสร้างบัญชี SSH ก่อนต่ออายุครับ' });
                return;
            }
            const initialGb = window.USERS_DATA[firstUser] ? window.USERS_DATA[firstUser].limit_gb : 0;
            const initialIp = window.USERS_DATA[firstUser] ? window.USERS_DATA[firstUser].limit_ip : 0;

            Swal.fire({
                title: '➕ ต่ออายุการใช้งาน',
                html: `
                    <div class="text-left space-y-3.5 text-xs p-1">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1.5">เลือกบัญชีที่ต้องการต่ออายุ:</label>
                            <select id="swal-quick-user" onchange="onQuickRenewUserChange(this)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-sm">
                                ${optionsHtml}
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1.5">เพิ่มจำนวนวันใช้งาน:</label>
                            <input id="swal-quick-days" type="number" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="30">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-quick-days').value=7" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 text-xs cursor-pointer">7 วัน</button>
                                <button type="button" onclick="document.getElementById('swal-quick-days').value=30" class="px-2.5 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold rounded-lg text-xs cursor-pointer">30 วัน (1 เดือน) ⭐</button>
                                <button type="button" onclick="document.getElementById('swal-quick-days').value=60" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 text-xs cursor-pointer">60 วัน</button>
                                <button type="button" onclick="document.getElementById('swal-quick-days').value=90" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 text-xs cursor-pointer">90 วัน</button>
                                <button type="button" onclick="document.getElementById('swal-quick-days').value=365" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 text-xs cursor-pointer">1 ปี</button>
                            </div>
                        </div>

                        <!-- ปรับโควต้าเน็ต GB -->
                        <div class="bg-purple-50/60 p-3 rounded-xl border border-purple-200">
                            <label class="block font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-database text-purple-600"></i>
                                <span>ปรับโควต้าเน็ต (GB):</span>
                            </label>
                            <input id="swal-quick-renew-gb" type="number" min="0" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="${initialGb}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="addGbValue('swal-quick-renew-gb', 10)" class="px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-lg text-xs font-bold cursor-pointer">+10 GB</button>
                                <button type="button" onclick="addGbValue('swal-quick-renew-gb', 50)" class="px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-lg text-xs font-bold cursor-pointer">+50 GB</button>
                                <button type="button" onclick="addGbValue('swal-quick-renew-gb', 100)" class="px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-lg text-xs font-bold cursor-pointer">+100 GB</button>
                                <button type="button" onclick="document.getElementById('swal-quick-renew-gb').value=0" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                        </div>

                        <!-- ลิมิต IP -->
                        <div class="bg-amber-50/60 p-3 rounded-xl border border-amber-200">
                            <label class="block font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-mobile-screen text-amber-600"></i>
                                <span>จำกัด IP (เครื่อง/จอ):</span>
                            </label>
                            <input id="swal-quick-renew-ip" type="number" min="0" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="${initialIp}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-quick-renew-ip').value=1" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">1 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-renew-ip').value=2" class="px-2.5 py-1 bg-amber-200 hover:bg-amber-300 text-amber-900 font-bold rounded-lg text-xs cursor-pointer">2 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-renew-ip').value=3" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">3 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-renew-ip').value=0" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 ยืนยันต่ออายุ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const u = document.getElementById('swal-quick-user').value;
                    const d = document.getElementById('swal-quick-days').value;
                    const g = document.getElementById('swal-quick-renew-gb').value;
                    const ip = document.getElementById('swal-quick-renew-ip').value;
                    if (!u || !d || Number(d) < 1) {
                        Swal.showValidationMessage('กรุณาเลือกบัญชีและระบุจำนวนวัน');
                        return false;
                    }
                    return { user: u, days: d, gb: g, ip: ip };
                }
            }).then(res => {
                if (res.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "renew");
                    fd.append("ajax", "1");
                    fd.append("user", res.value.user);
                    fd.append("days", res.value.days);
                    fd.append("gb", res.value.gb);
                    fd.append("ip", res.value.ip);
                    postApiAction(fd, `ต่ออายุบัญชี ${res.value.user} เพิ่ม ${res.value.days} วัน เรียบร้อยแล้ว!`);
                }
            });
        }

        function onQuickRenewUserChange(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt) {
                const gb = opt.getAttribute('data-gb') || '0';
                const ip = opt.getAttribute('data-ip') || '0';
                const gbInput = document.getElementById('swal-quick-renew-gb');
                const ipInput = document.getElementById('swal-quick-renew-ip');
                if (gbInput) gbInput.value = gb;
                if (ipInput) ipInput.value = ip;
            }
        }

        // เมนูด่วน: ⚙️ ปรับลิมิตผู้ใช้ (Quick Limit Modal)
        function openQuickLimitModal() {
            let optionsHtml = '';
            let firstUser = '';
            for (const [u, info] of Object.entries(window.USERS_DATA || {})) {
                if (!firstUser) firstUser = u;
                const gbTxt = info.limit_gb > 0 ? `${info.limit_gb} GB` : 'ไม่จำกัด';
                const ipTxt = info.limit_ip > 0 ? `${info.limit_ip} จอ` : 'ไม่จำกัด';
                optionsHtml += `<option value="${escapeHtml(u)}" data-gb="${info.limit_gb}" data-ip="${info.limit_ip}">${escapeHtml(u)} (${gbTxt} / ${ipTxt})</option>`;
            }
            if (!optionsHtml) {
                Swal.fire({ icon: 'info', title: 'ยังไม่มีบัญชี', text: 'กรุณาสร้างบัญชี SSH ก่อนปรับลิมิตครับ' });
                return;
            }

            const initialGb = window.USERS_DATA[firstUser] ? window.USERS_DATA[firstUser].limit_gb : 0;
            const initialIp = window.USERS_DATA[firstUser] ? window.USERS_DATA[firstUser].limit_ip : 0;

            Swal.fire({
                title: '⚙️ ปรับลิมิตโควต้า GB และ IP',
                html: `
                    <div class="text-left space-y-3.5 text-xs p-1">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1.5">เลือกบัญชีที่ต้องการปรับ:</label>
                            <select id="swal-quick-limit-user" onchange="onQuickLimitUserChange(this)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-sm">
                                ${optionsHtml}
                            </select>
                        </div>

                        <!-- โควต้าเน็ต GB -->
                        <div class="bg-purple-50/60 p-3 rounded-xl border border-purple-200">
                            <label class="block font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-database text-purple-600"></i>
                                <span>โควต้าปริมาณเน็ต (GB):</span>
                            </label>
                            <input id="swal-quick-limit-gb" type="number" min="0" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="${initialGb}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="addGbValue('swal-quick-limit-gb', 10)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+10 GB</button>
                                <button type="button" onclick="addGbValue('swal-quick-limit-gb', 20)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+20 GB</button>
                                <button type="button" onclick="addGbValue('swal-quick-limit-gb', 50)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+50 GB</button>
                                <button type="button" onclick="addGbValue('swal-quick-limit-gb', 100)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+100 GB</button>
                                <button type="button" onclick="document.getElementById('swal-quick-limit-gb').value=200" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">200 GB</button>
                                <button type="button" onclick="document.getElementById('swal-quick-limit-gb').value=0" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                        </div>

                        <!-- ลิมิต IP -->
                        <div class="bg-amber-50/60 p-3 rounded-xl border border-amber-200">
                            <label class="block font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-mobile-screen text-amber-600"></i>
                                <span>จำกัดจำนวน IP (เครื่อง/จอ):</span>
                            </label>
                            <input id="swal-quick-limit-ip" type="number" min="0" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="${initialIp}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-quick-limit-ip').value=1" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">1 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-limit-ip').value=2" class="px-2.5 py-1 bg-amber-200 hover:bg-amber-300 text-amber-900 font-bold rounded-lg text-xs cursor-pointer">2 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-limit-ip').value=3" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">3 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-limit-ip').value=5" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">5 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-quick-limit-ip').value=0" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 ยืนยันบันทึก',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const u = document.getElementById('swal-quick-limit-user').value;
                    const g = document.getElementById('swal-quick-limit-gb').value;
                    const ip = document.getElementById('swal-quick-limit-ip').value;
                    if (!u) {
                        Swal.showValidationMessage('กรุณาเลือกบัญชีผู้ใช้');
                        return false;
                    }
                    return {
                        user: u,
                        gb: Math.max(0, parseInt(g) || 0),
                        ip: Math.max(0, parseInt(ip) || 0)
                    };
                }
            }).then(res => {
                if (res.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "limit");
                    fd.append("ajax", "1");
                    fd.append("user", res.value.user);
                    fd.append("gb", res.value.gb);
                    fd.append("ip", res.value.ip);
                    postApiAction(fd, `อัปเดตลิมิตบัญชี ${res.value.user} สำเร็จ!`);
                }
            });
        }

        function onQuickLimitUserChange(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt) {
                const gb = opt.getAttribute('data-gb') || '0';
                const ip = opt.getAttribute('data-ip') || '0';
                const gbInput = document.getElementById('swal-quick-limit-gb');
                const ipInput = document.getElementById('swal-quick-limit-ip');
                if (gbInput) gbInput.value = gb;
                if (ipInput) ipInput.value = ip;
            }
        }

        // เมนูด่วน: 🔑 เปลี่ยนรหัสผ่านด่วน (Quick Password Modal)
        function openQuickPasswdModal() {
            let optionsHtml = '';
            for (const [u, info] of Object.entries(window.USERS_DATA || {})) {
                optionsHtml += `<option value="${escapeHtml(u)}">${escapeHtml(u)}</option>`;
            }
            if (!optionsHtml) {
                Swal.fire({ icon: 'info', title: 'ยังไม่มีบัญชี', text: 'กรุณาสร้างบัญชี SSH ก่อนครับ' });
                return;
            }
            Swal.fire({
                title: '🔑 เปลี่ยนรหัสผ่าน SSH',
                html: `
                    <div class="text-left space-y-3.5 text-xs p-1">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1.5">เลือกบัญชีผู้ใช้:</label>
                            <select id="swal-quick-pw-user" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-sm">
                                ${optionsHtml}
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1.5">รหัสผ่านใหม่:</label>
                            <input id="swal-quick-pw-pass" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" placeholder="กรอกรหัสผ่านใหม่">
                            <button type="button" onclick="document.getElementById('swal-quick-pw-pass').value=Math.floor(100000+Math.random()*900000)" class="mt-2 text-blue-600 hover:underline text-xs cursor-pointer">
                                🎲 สุ่มรหัสผ่าน 6 หลัก
                            </button>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 บันทึกรหัสผ่านใหม่',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const u = document.getElementById('swal-quick-pw-user').value;
                    const p = document.getElementById('swal-quick-pw-pass').value.trim();
                    if (!u || !p) {
                        Swal.showValidationMessage('กรุณาระบุรหัสผ่านใหม่');
                        return false;
                    }
                    return { user: u, pass: p };
                }
            }).then(res => {
                if (res.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "passwd");
                    fd.append("ajax", "1");
                    fd.append("user", res.value.user);
                    fd.append("pass", res.value.pass);
                    postApiAction(fd, `เปลี่ยนรหัสผ่านบัญชี ${res.value.user} เรียบร้อยแล้ว!`);
                }
            });
        }

        // เมนูด่วน: 🗑️ ลบบัญชีด่วน (Quick Delete Modal)
        function openQuickDeleteModal() {
            let optionsHtml = '';
            for (const [u, info] of Object.entries(window.USERS_DATA || {})) {
                optionsHtml += `<option value="${escapeHtml(u)}">${escapeHtml(u)}</option>`;
            }
            if (!optionsHtml) {
                Swal.fire({ icon: 'info', title: 'ยังไม่มีบัญชี', text: 'ไม่มีบัญชีให้ลบครับ' });
                return;
            }
            Swal.fire({
                title: '🗑️ ลบบัญชีผู้ใช้ SSH',
                html: `
                    <div class="text-left space-y-3 text-xs p-1">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1.5">เลือกบัญชีที่ต้องการลบ:</label>
                            <select id="swal-quick-del-user" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-sm">
                                ${optionsHtml}
                            </select>
                        </div>
                        <p class="text-red-600 text-xs">⚠️ เมื่อลบแล้วบัญชีนี้จะถูกตัดการเชื่อมต่อทันทีและไม่สามารถกู้คืนได้</p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'ยืนยันลบทันที',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    return document.getElementById('swal-quick-del-user').value;
                }
            }).then(res => {
                if (res.isConfirmed && res.value) {
                    deleteUser(res.value);
                }
            });
        }

        // หน้าต่างต่ออายุ (Renew จากการ์ดของคนนั้นๆ)
        function openRenewModal(user, currentGb, currentIp) {
            currentGb = currentGb !== undefined ? parseInt(currentGb) : 0;
            currentIp = currentIp !== undefined ? parseInt(currentIp) : 0;
            const currentGbText = currentGb > 0 ? `${currentGb} GB` : 'ไม่จำกัด';
            const currentIpText = currentIp > 0 ? `${currentIp} เครื่อง` : 'ไม่จำกัด';

            Swal.fire({
                title: `➕ ต่ออายุบัญชี: <span class="text-blue-600 font-mono">${escapeHtml(user)}</span>`,
                html: `
                    <div class="text-left space-y-3.5 text-xs p-1">
                        <!-- จำนวนวัน -->
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                            <label class="block font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <i class="fa-regular fa-clock text-blue-600"></i>
                                <span>เพิ่มจำนวนวันใช้งาน:</span>
                            </label>
                            <input id="swal-renew-days" type="number" min="1" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="30">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-renew-days').value=7" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 rounded-lg text-slate-700 text-xs cursor-pointer">7 วัน</button>
                                <button type="button" onclick="document.getElementById('swal-renew-days').value=30" class="px-2.5 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold rounded-lg text-xs cursor-pointer">30 วัน ⭐</button>
                                <button type="button" onclick="document.getElementById('swal-renew-days').value=60" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 rounded-lg text-slate-700 text-xs cursor-pointer">60 วัน</button>
                                <button type="button" onclick="document.getElementById('swal-renew-days').value=90" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 rounded-lg text-slate-700 text-xs cursor-pointer">90 วัน</button>
                                <button type="button" onclick="document.getElementById('swal-renew-days').value=365" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 rounded-lg text-slate-700 text-xs cursor-pointer">1 ปี</button>
                            </div>
                        </div>

                        <!-- ปรับโควต้าเน็ต GB -->
                        <div class="bg-purple-50/60 p-3 rounded-xl border border-purple-200">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-database text-purple-600"></i>
                                    <span>โควต้าเน็ต (GB):</span>
                                </label>
                                <span class="text-xs text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-md">ปัจจุบัน: ${currentGbText}</span>
                            </div>
                            <input id="swal-renew-gb" type="number" min="0" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="${currentGb}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="addGbValue('swal-renew-gb', 10)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+10 GB</button>
                                <button type="button" onclick="addGbValue('swal-renew-gb', 20)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+20 GB</button>
                                <button type="button" onclick="addGbValue('swal-renew-gb', 50)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+50 GB</button>
                                <button type="button" onclick="addGbValue('swal-renew-gb', 100)" class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer">+100 GB</button>
                                <button type="button" onclick="document.getElementById('swal-renew-gb').value=0" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                        </div>

                        <!-- ปรับลิมิต IP -->
                        <div class="bg-amber-50/60 p-3 rounded-xl border border-amber-200">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-mobile-screen text-amber-600"></i>
                                    <span>ลิมิต IP (จำนวนอุปกรณ์):</span>
                                </label>
                                <span class="text-xs text-amber-700 font-bold bg-amber-100 px-2 py-0.5 rounded-md">ปัจจุบัน: ${currentIpText}</span>
                            </div>
                            <input id="swal-renew-ip" type="number" min="0" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" value="${currentIp}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-renew-ip').value=1" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">1 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-renew-ip').value=2" class="px-2.5 py-1 bg-amber-200 hover:bg-amber-300 text-amber-900 font-bold rounded-lg text-xs cursor-pointer">2 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-renew-ip').value=3" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">3 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-renew-ip').value=0" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 บันทึกต่ออายุ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const d = document.getElementById("swal-renew-days").value;
                    const g = document.getElementById("swal-renew-gb").value;
                    const ip = document.getElementById("swal-renew-ip").value;
                    if (!d || Number(d) < 1) {
                        Swal.showValidationMessage("กรุณาระบุจำนวนวัน");
                        return false;
                    }
                    return {
                        days: parseInt(d),
                        gb: Math.max(0, parseInt(g) || 0),
                        ip: Math.max(0, parseInt(ip) || 0)
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "renew");
                    fd.append("ajax", "1");
                    fd.append("user", user);
                    fd.append("days", result.value.days);
                    fd.append("gb", result.value.gb);
                    fd.append("ip", result.value.ip);
                    postApiAction(fd, `ต่ออายุและอัปเดตลิมิตบัญชี ${user} เรียบร้อยแล้ว!`);
                }
            });
        }

        // หน้าต่างปรับเฉพาะโควต้า GB และลิมิต IP (ไม่เปลี่ยนวันหมดอายุ)
        function openLimitModal(user, currentGb, currentIp) {
            currentGb = currentGb !== undefined ? parseInt(currentGb) : 0;
            currentIp = currentIp !== undefined ? parseInt(currentIp) : 0;
            const currentGbText = currentGb > 0 ? `${currentGb} GB` : 'ไม่จำกัด';
            const currentIpText = currentIp > 0 ? `${currentIp} เครื่อง` : 'ไม่จำกัด';

            Swal.fire({
                title: `⚙️ ปรับลิมิต: <span class="text-blue-600 font-mono">${escapeHtml(user)}</span>`,
                html: `
                    <div class="text-left space-y-4 text-xs p-1">
                        <!-- โควต้าเน็ต GB -->
                        <div class="bg-purple-50/60 p-3.5 rounded-xl border border-purple-200">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-database text-purple-600"></i>
                                    <span>โควต้าปริมาณเน็ต (GB):</span>
                                </label>
                                <span class="text-xs text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-md">ปัจจุบัน: ${currentGbText}</span>
                            </div>
                            <input id="swal-limit-gb" type="number" min="0" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm focus:border-purple-600 focus:outline-none" value="${currentGb}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="addGbValue('swal-limit-gb', 10)" class="px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer transition-all shadow-xs">+10 GB</button>
                                <button type="button" onclick="addGbValue('swal-limit-gb', 20)" class="px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer transition-all shadow-xs">+20 GB</button>
                                <button type="button" onclick="addGbValue('swal-limit-gb', 50)" class="px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer transition-all shadow-xs">+50 GB</button>
                                <button type="button" onclick="addGbValue('swal-limit-gb', 100)" class="px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold cursor-pointer transition-all shadow-xs">+100 GB</button>
                                <button type="button" onclick="document.getElementById('swal-limit-gb').value=200" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">200 GB</button>
                                <button type="button" onclick="document.getElementById('swal-limit-gb').value=0" class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1.5">💡 กดปุ่ม +GB เพื่อเพิ่มเน็ตให้ลูกค้า หรือระบุตัวเลขใหม่โดยตรง (0 = ไม่จำกัดเน็ต)</p>
                        </div>

                        <!-- ลิมิต IP -->
                        <div class="bg-amber-50/60 p-3.5 rounded-xl border border-amber-200">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-mobile-screen text-amber-600"></i>
                                    <span>จำกัดจำนวน IP (เครื่อง/จอ):</span>
                                </label>
                                <span class="text-xs text-amber-700 font-bold bg-amber-100 px-2 py-0.5 rounded-md">ปัจจุบัน: ${currentIpText}</span>
                            </div>
                            <input id="swal-limit-ip" type="number" min="0" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono text-sm focus:border-amber-600 focus:outline-none" value="${currentIp}">
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-limit-ip').value=1" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium cursor-pointer">1 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-limit-ip').value=2" class="px-3 py-1.5 bg-amber-200 hover:bg-amber-300 text-amber-900 font-bold rounded-lg text-xs cursor-pointer">2 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-limit-ip').value=3" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium cursor-pointer">3 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-limit-ip').value=5" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium cursor-pointer">5 เครื่อง</button>
                                <button type="button" onclick="document.getElementById('swal-limit-ip').value=0" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs cursor-pointer">0 (ไม่จำกัด)</button>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1.5">💡 จำนวนอุปกรณ์ที่อนุญาตให้ล็อกอินพร้อมกัน (0 = ไม่จำกัด)</p>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 บันทึกการเปลี่ยนแปลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const g = document.getElementById("swal-limit-gb").value;
                    const ip = document.getElementById("swal-limit-ip").value;
                    return {
                        gb: Math.max(0, parseInt(g) || 0),
                        ip: Math.max(0, parseInt(ip) || 0)
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "limit");
                    fd.append("ajax", "1");
                    fd.append("user", user);
                    fd.append("gb", result.value.gb);
                    fd.append("ip", result.value.ip);
                    postApiAction(fd, `อัปเดตลิมิตบัญชี ${user} สำเร็จ!`);
                }
            });
        }

        // หน้าต่างเปลี่ยนรหัสผ่าน (Password จากการ์ด)
        function openPasswdModal(user) {
            Swal.fire({
                title: `🔑 เปลี่ยนรหัสผ่าน: ${escapeHtml(user)}`,
                html: `
                    <div class="text-left text-xs p-1">
                        <label class="block font-semibold text-slate-700 mb-1.5">รหัสผ่านใหม่:</label>
                        <input id="swal-newpass" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-sm" placeholder="กรอกรหัสผ่านใหม่">
                        <button type="button" onclick="document.getElementById('swal-newpass').value=Math.floor(100000+Math.random()*900000)" class="mt-2 text-blue-600 hover:underline text-xs cursor-pointer">
                            🎲 สุ่มรหัสผ่าน 6 หลัก
                        </button>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 บันทึกรหัสผ่าน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                preConfirm: () => {
                    const p = document.getElementById("swal-newpass").value.trim();
                    if (!p) {
                        Swal.showValidationMessage("กรุณาระบุรหัสผ่าน");
                        return false;
                    }
                    return p;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "passwd");
                    fd.append("ajax", "1");
                    fd.append("user", user);
                    fd.append("pass", result.value);
                    postApiAction(fd, `เปลี่ยนรหัสผ่านบัญชี ${user} สำเร็จ!`);
                }
            });
        }

        // ลบบัญชีผู้ใช้ (Delete)
        function deleteUser(user) {
            Swal.fire({
                title: `🗑️ ต้องการลบบัญชี ${escapeHtml(user)}?`,
                text: "เมื่อลบแล้วจะไม่สามารถกู้คืนได้ และผู้ใช้จะถูกตัดการเชื่อมต่อทันที",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันลบทันที',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append("action", "delete");
                    fd.append("ajax", "1");
                    fd.append("user", user);
                    postApiAction(fd, `ลบบัญชี ${user} ออกแล้ว!`);
                }
            });
        }

        // ตัดการเชื่อมต่อ (Kick)
        function kickUser(user) {
            const fd = new FormData();
            fd.append("action", "kick");
            fd.append("ajax", "1");
            fd.append("user", user);
            postApiAction(fd, `ตัดการเชื่อมต่อของ ${user} แล้ว!`);
        }

        // หน้าต่างตั้งค่าระบบ (Settings Modal)
        function openSettingsModal() {
            Swal.fire({
                title: '⚙️ ตั้งค่าระบบเซิร์ฟเวอร์',
                html: `
                    <div class="text-left space-y-4 text-xs p-1">
                        <!-- Port WS -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <label class="block font-bold text-slate-800 mb-1">เปลี่ยนพอร์ต WebSocket (ปัจจุบัน: ${WS_PORT}):</label>
                            <div class="flex gap-2">
                                <input id="swal-port" type="number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm font-mono" value="${WS_PORT}">
                                <button type="button" onclick="submitChangePort()" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-semibold shrink-0 cursor-pointer">บันทึกพอร์ต</button>
                            </div>
                            <div class="flex gap-1.5 mt-2 flex-wrap">
                                <button type="button" onclick="document.getElementById('swal-port').value=80" class="px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 rounded-lg text-xs font-bold text-emerald-800 cursor-pointer">พอร์ต 80 (แนะนำ ⭐)</button>
                                <button type="button" onclick="document.getElementById('swal-port').value=8080" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 cursor-pointer">พอร์ต 8080</button>
                                <button type="button" onclick="document.getElementById('swal-port').value=8880" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 cursor-pointer">พอร์ต 8880</button>
                            </div>
                            <span class="text-[11px] text-slate-500 mt-2 block">💡 พอร์ต 80 สามารถใช้งานได้ทันที ระบบตั้งค่าให้ทำงานร่วมกับเว็บร้านค้าได้อย่างสมบูรณ์</span>
                        </div>

                        <!-- Panel Password -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                            <label class="block font-bold text-slate-800 mb-1">เปลี่ยนรหัสผ่านแผงควบคุม (Web Panel):</label>
                            <input id="swal-old-pass" type="password" placeholder="รหัสผ่านเดิม" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm mb-2">
                            <input id="swal-new-pass" type="password" placeholder="รหัสผ่านใหม่ (ขั้นต่ำ 4 ตัว)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm mb-2">
                            <button type="button" onclick="submitChangePanelPass()" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold cursor-pointer">อัปเดตรหัสผ่านแผง</button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'ปิดหน้าต่าง',
                cancelButtonColor: '#64748b'
            });
        }

        function submitChangePort() {
            const p = document.getElementById("swal-port").value.trim();
            if (!p || Number(p) < 1 || Number(p) > 65535) {
                alert("กรุณาระบุพอร์ต 1-65535");
                return;
            }
            const fd = new FormData();
            fd.append("action", "port");
            fd.append("ajax", "1");
            fd.append("pass", p);
            postApiAction(fd, `เปลี่ยนพอร์ตเป็น ${p} สำเร็จ!`);
        }

        function submitChangePanelPass() {
            const oldP = document.getElementById("swal-old-pass").value;
            const newP = document.getElementById("swal-new-pass").value;
            if (!oldP || !newP || newP.length < 4) {
                alert("กรุณากรอกรหัสผ่านเดิม และรหัสผ่านใหม่ขั้นต่ำ 4 ตัว");
                return;
            }
            const fd = new FormData();
            fd.append("action", "chpass");
            fd.append("ajax", "1");
            fd.append("old", oldP);
            fd.append("new", newP);
            postApiAction(fd, "เปลี่ยนรหัสผ่านแผงสำเร็จ!");
        }

        // ส่งคำสั่ง AJAX
        function postApiAction(formData, successMsg) {
            Swal.fire({
                title: 'กำลังดำเนินการ...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch("", { method: "POST", body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: res.message || successMsg,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: res.message || 'เกิดข้อผิดพลาด' });
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
                });
        }

        // ฟังก์ชันคัดลอกข้อความ รองรับทั้ง HTTPS และ HTTP ธรรมดา (ไม่ทำให้ปุ่มค้างหรือไม่มีการตอบสนอง)
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).catch(() => fallbackCopy(text));
            }
            return fallbackCopy(text);
        }

        function fallbackCopy(text) {
            return new Promise((resolve, reject) => {
                try {
                    const ta = document.createElement("textarea");
                    ta.value = text;
                    ta.setAttribute("readonly", "");
                    ta.style.position = "fixed";
                    ta.style.left = "-9999px";
                    ta.style.top = "0";
                    document.body.appendChild(ta);
                    ta.focus();
                    ta.select();
                    ta.setSelectionRange(0, 99999);
                    const ok = document.execCommand("copy");
                    document.body.removeChild(ta);
                    if (ok) resolve();
                    else reject(new Error("execCommand failed"));
                } catch (err) {
                    reject(err);
                }
            });
        }

        // คัดลอกรูปแบบส่งลูกค้า
        function copyCustomerFormat(user, pass, expDate, gbText, ipText) {
            const text = `🛡️ ข้อมูลบัญชี SSH VPN
──────────────────────────────
👤 ชื่อผู้ใช้: ${user}
🔑 รหัสผ่าน: ${pass}
🌐 Host / IP: ${SERVER_DOMAIN}
🔌 SSH Direct: 22, 83, 143
⚡ SSH WebSocket: ${WS_PORT}, 443
🎮 UDPGW: 7100, 7200, 7300
📅 หมดอายุ: ${expDate}
📦 โควต้าเน็ต: ${gbText}
📱 จำกัด: ${ipText}
──────────────────────────────`;
            copyCustomerFormatText(text, user);
        }

        function copyCustomerFormatText(text, username) {
            copyToClipboard(text).then(() => {
                showCustomerModal(text, true, username);
            }).catch(() => {
                showCustomerModal(text, false, username);
            });
        }

        function showCustomerModal(text, copySuccess, username) {
            const title = username ? `📋 ข้อมูลบัญชี: ${escapeHtml(username)}` : `📋 ข้อมูลบัญชีสำหรับส่งลูกค้า`;
            const statusHtml = copySuccess
                ? `<div id="swal-copy-status" class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-semibold flex items-center gap-2 mb-2 border border-emerald-200"><i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i><span>คัดลอกลงคลิปบอร์ดแล้ว! สามารถกดวางส่งให้ลูกค้าได้ทันที</span></div>`
                : `<div id="swal-copy-status" class="p-2.5 rounded-xl bg-blue-50 text-blue-800 text-xs font-semibold flex items-center gap-2 mb-2 border border-blue-200"><i class="fa-solid fa-info-circle text-blue-600 text-sm"></i><span>สามารถกดปุ่ม "คัดลอกข้อความอีกครั้ง" หรือเลือกข้อความด้านล่างได้เลยครับ</span></div>`;

            Swal.fire({
                title: title,
                html: `
                    <div class="text-left text-xs space-y-3 p-1">
                        ${statusHtml}
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">ข้อความสำหรับส่งลูกค้า (แก้ไขหรือดูข้อความก่อนส่งได้):</label>
                            <textarea id="swal-customer-textarea" rows="11" class="w-full p-3 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-slate-800 focus:bg-white focus:outline-none focus:border-blue-600 select-all leading-relaxed whitespace-pre">${escapeHtml(text)}</textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="copyFromCustomerModal()" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 shadow-sm cursor-pointer transition-all">
                                <i class="fa-solid fa-copy"></i>
                                <span>คัดลอกข้อความอีกครั้ง</span>
                            </button>
                            ${navigator.share ? `
                            <button type="button" onclick="shareCustomerText()" class="py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-sm cursor-pointer transition-all">
                                <i class="fa-solid fa-share-nodes"></i>
                                <span>แชร์</span>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `,
                confirmButtonText: 'ปิดหน้าต่าง',
                confirmButtonColor: '#64748b'
            });
        }

        function copyFromCustomerModal() {
            const ta = document.getElementById("swal-customer-textarea");
            if (!ta) return;
            const text = ta.value;
            copyToClipboard(text).then(() => {
                const status = document.getElementById("swal-copy-status");
                if (status) {
                    status.className = "p-2.5 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-semibold flex items-center gap-2 mb-2 border border-emerald-200";
                    status.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i><span>คัดลอกข้อความเรียบร้อยแล้ว! พร้อมส่งให้ลูกค้า</span>`;
                }
            }).catch(() => {
                ta.focus();
                ta.select();
            });
        }

        function shareCustomerText() {
            const ta = document.getElementById("swal-customer-textarea");
            if (!ta || !navigator.share) return;
            navigator.share({
                title: 'ข้อมูลบัญชี SSH VPN',
                text: ta.value
            }).catch(() => {});
        }

        function copySingleText(text) {
            copyToClipboard(text).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'คัดลอกแล้ว',
                    showConfirmButton: false,
                    timer: 1200
                });
            }).catch(() => {
                prompt("คัดลอกข้อความ:", text);
            });
        }

        function escapeHtml(string) {
            const pre = document.createElement("pre");
            const text = document.createTextNode(string);
            pre.appendChild(text);
            return pre.innerHTML;
        }
    </script>
</body>
</html>
