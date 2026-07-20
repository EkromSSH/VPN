<?php
session_start();
$password = "admin123";
$error = "";
if (!isset($_SESSION["logged"])) {
    if ($_POST["pass"] ?? null) {
        if ($_POST["pass"] === $password) {
            $_SESSION["logged"] = true;
            header("Location: /?page=dashboard");
            exit;
        } else {
            $error = "Wrong password";
        }
    }
    if (!isset($_SESSION["logged"])) {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Login</title>';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<style>';
        echo '*{margin:0;padding:0;box-sizing:border-box;}';
        echo 'body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#0d1117;color:#c9d1d9;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}';
        echo '.box{background:#161b22;padding:32px;border-radius:16px;border:1px solid #30363d;width:100%;max-width:360px;}';
        echo 'h1{color:#58a6ff;text-align:center;margin-bottom:4px;}';
        echo 'p{color:#8b949e;text-align:center;margin-bottom:20px;}';
        echo 'input{width:100%;padding:12px;margin-bottom:12px;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#c9d1d9;font-size:16px;}';
        echo 'input:focus{border-color:#58a6ff;outline:none;}';
        echo 'button{width:100%;padding:12px;background:#238636;border:none;border-radius:8px;color:#fff;font-size:16px;font-weight:600;cursor:pointer;}';
        echo '</style></head><body>';
        echo '<div class="box"><h1>EkromVPN</h1><p>Admin Panel</p>';
        if ($error) echo '<div style="background:#f8514920;color:#f85149;padding:10px;border-radius:8px;text-align:center;margin-bottom:12px;">'.$error.'</div>';
        echo '<form method="post"><input type="password" name="pass" placeholder="password" required><button>Login</button></form>';
        echo '</div></body></html>';
        exit;
    }
}
if ($_GET["logout"]??null) { session_destroy(); header("Location: /"); exit; }

$page = $_GET["page"] ?? "dashboard";
if ($_POST["action"]??null) {
    $a=$_POST["action"]; $u=escapeshellcmd($_POST["username"]??"");
    if ($a==="create"&&$u) {$p=escapeshellcmd($_POST["password"]??"");$d=intval($_POST["days"]?:30);exec("sudo /usr/local/bin/ssh-admin create $u $p $d 2>/dev/null",$o,$c);$msg=$c===0?"ok_$u":"fail";}
    elseif ($a==="delete"&&$u) {exec("sudo /usr/local/bin/ssh-admin delete $u 2>/dev/null",$o,$c);$msg=$c===0?"del_$u":"nf";}
    elseif ($a==="passwd"&&$u) {$p=escapeshellcmd($_POST["password"]??"");exec("sudo /usr/local/bin/ssh-admin passwd $u $p 2>/dev/null",$o,$c);$msg=$c===0?"pw_$u":"pf";}
    header("Location: ?page=$page&msg=$msg");exit;
}

$ip=trim(exec("curl -s ipv4.icanhazip.com")??"");
$domain=trim(@file_get_contents("/etc/xray/domain")??"");
$ram_t=trim(exec("free -h|grep Mem|awk '{print $2}'")??"");$ram_u=trim(exec("free -h|grep Mem|awk '{print $3}'")??"");
$uptime=trim(exec("uptime -p|cut -d' ' -f2-")??"");$port="8080";
$online=trim(exec("ps h -o user -C sshd 2>/dev/null|grep -v root|sort -u|wc -l")?:"0");
$users=[];if(file_exists("/etc/ssh/.ssh.db")){foreach(file("/etc/ssh/.ssh.db") as$l){if(preg_match("/^### (.+)/",$l,$m))$users[]=trim($m[1]);}}
$ol=[];exec("ps h -o user -C sshd 2>/dev/null|grep -v root|sort -u",$ol);
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>EkromVPN</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#0d1117;color:#c9d1d9;min-height:100vh;}
.bar{display:flex;align-items:center;gap:8px;background:#161b22;padding:10px 12px;border-bottom:1px solid #30363d;position:sticky;top:0;z-index:10;flex-wrap:wrap;}
.bar .t{color:#58a6ff;font-size:15px;font-weight:700;white-space:nowrap;}
.bar .r{margin-left:auto;color:#8b949e;font-size:11px;white-space:nowrap;}
.nav{display:flex;gap:2px;overflow-x:auto;flex:1;margin:0 4px;scrollbar-width:none;}
.nav a{color:#8b949e;text-decoration:none;font-size:11px;padding:5px 8px;border-radius:6px;white-space:nowrap;}
.nav a.act{background:#1f6feb20;color:#58a6ff;font-weight:600;}
.m{padding:12px;max-width:900px;margin:0 auto;}
.hd{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.hd h2{color:#58a6ff;font-size:17px;}
.gr{display:grid;grid-template-columns:1fr;gap:10px;margin-bottom:16px;}
.cd{background:#161b22;border:1px solid #30363d;border-radius:10px;padding:14px;}
.cd h3{font-size:12px;color:#8b949e;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;}
.r{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #21262d;}
.l{color:#8b949e;font-size:12px;}
.v{color:#c9d1d9;font-size:13px;font-weight:500;}
input,select{width:100%;padding:10px 12px;margin:0 0 8px 0;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#c9d1d9;font-size:16px;}
input:focus,select:focus{border-color:#58a6ff;outline:none;}
button{width:100%;padding:11px;background:#238636;border:none;border-radius:8px;color:#fff;font-size:14px;font-weight:600;cursor:pointer;}
.btn2{background:#da3633;}
table{width:100%;border-collapse:collapse;}
th{padding:7px 8px;text-align:left;border-bottom:1px solid #21262d;}
td{padding:7px 8px;border-bottom:1px solid #21262d;font-size:13px;}
.msg{padding:10px 12px;border-radius:8px;margin-bottom:10px;background:#0d1117;border:1px solid #30363d;}
</style></head><body>
<div class="bar"><span class="t">EkromVPN</span>
<div class="nav">
<a href="?page=dashboard" class="act">Dashboard</a>
<a href="?page=create">Create</a>
<a href="?page=delete">Delete</a>
<a href="?page=online">Online</a>
<a href="?page=settings">Settings</a>
</div><span class="r"><?=$ip?></span><a href="?logout=1" style="color:#8b949e;text-decoration:none;font-size:12px;">Logout</a>
</div>
<div class="m">
<div class="hd"><h2><?php
$ts=["dashboard"=>"Dashboard","create"=>"Create SSH","delete"=>"Delete SSH","online"=>"Online Users","settings"=>"Settings"];
echo $ts[$page]??"Dashboard";
?></h2></div>
<?php
$m=$_GET["msg"]??"";
if($m){
    $c="#3fb950";$t="";
    if(strpos($m,"ok_")===0){$t='Created '.substr($m,3);}
    elseif(strpos($m,"del_")===0){$t='Deleted '.substr($m,4);}
    elseif(strpos($m,"pw_")===0){$t='Password changed '.substr($m,3);}
    elseif($m==="fail"){$c="#f85149";$t='Create failed';}
    elseif($m==="nf"){$c="#f85149";$t='Not found';}
    elseif($m==="pf"){$c="#f85149";$t='Password failed';}
    if($t)echo '<div class="msg" style="color:'.$c.'">'.$t.'</div>';
}

if($page==="dashboard"):?>
<div class="gr">
<div class="cd"><h3>System</h3>
<div class="r"><span class="l">IP</span><span class="v"><?=$ip?></span></div>
<div class="r"><span class="l">Domain</span><span class="v"><?=$domain?:'-'?></span></div>
<div class="r"><span class="l">RAM</span><span class="v"><?=$ram_u?>/<?=$ram_t?></span></div>
<div class="r"><span class="l">Uptime</span><span class="v"><?=$uptime?></span></div>
<div class="r"><span class="l">SSH WS Port</span><span class="v"><?=$port?></span></div>
</div>
<div class="cd"><h3>Users</h3>
<div class="r"><span class="l">Total</span><span class="v"><?=count($users)?></span></div>
<div class="r"><span class="l">Online</span><span class="v"><?=$online?></span></div>
</div></div>
<div class="cd"><h3>All Users</h3>
<table><tr><th>#</th><th>Name</th><th>Status</th></tr>
<?php foreach($users as$i=>$u):?>
<tr><td><?=$i+1?></td><td><?=$u?></td><td><?=in_array($u,$ol)?'<span style="color:#3fb950;font-weight:600;">Online</span>':'<span style="color:#8b949e;">Offline</span>'?></td></tr>
<?php endforeach;?>
</table></div>

<?php elseif($page==="create"):?>
<div class="gr">
<div class="cd"><h3>Create SSH User</h3>
<form method="post"><input type="hidden" name="action" value="create">
<input name="username" placeholder="Username" required>
<input name="password" placeholder="Password" required>
<input name="days" type="number" placeholder="Days" value="30">
<button>Create</button></form></div>
<div class="cd"><h3>Change Password</h3>
<form method="post"><input type="hidden" name="action" value="passwd">
<select name="username"><option value="">Select user...</option>
<?php foreach($users as$u):?><option value="<?=$u?>"><?=$u?></option><?php endforeach;?></select>
<input name="password" placeholder="New password" required>
<button>Change</button></form></div></div>

<?php elseif($page==="delete"):?>
<div class="cd" style="max-width:400px"><h3>Delete SSH User</h3>
<form method="post" onsubmit="return confirm('Delete?')">
<input type="hidden" name="action" value="delete">
<select name="username"><option value="">Select user...</option>
<?php foreach($users as$u):?><option value="<?=$u?>"><?=$u?></option><?php endforeach;?></select>
<button class="btn2">Delete</button></form></div>

<?php elseif($page==="online"):?>
<div class="cd"><h3>Online Users</h3>
<?php if(count($ol)>0):?><table><tr><th>#</th><th>Name</th></tr>
<?php foreach($ol as$i=>$u):?><tr><td><?=$i+1?></td><td><?=$u?></td></tr><?php endforeach;?>
</table><?php else:?><p style="color:#8b949e;">No online users</p><?php endif;?></div>

<?php elseif($page==="settings"):?>
<div class="cd"><h3>System</h3>
<div class="r"><span class="l">SSH WS Port</span><span class="v"><?=$port?></span></div>
<div class="r"><span class="l">IP</span><span class="v"><?=$ip?></span></div>
<div class="r"><span class="l">Domain</span><span class="v"><?=$domain?:'-'?></span></div>
</div>
<?php endif;?>
</div></body></html>
