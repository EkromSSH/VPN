<?php
session_start();
$password="admin123";
if(!isset($_SESSION["logged"])){
 if($_POST["pass"]??null){if($_POST["pass"]===$password){$_SESSION["logged"]=true;}else{$error="รหัสผ่านผิด";}}
 if(!isset($_SESSION["logged"])){?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>เข้าสู่ระบบ</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#0d1117;color:#c9d1d9;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}
.box{background:#161b22;padding:40px;border-radius:16px;border:1px solid #30363d;width:100%;max-width:360px;}
h1{color:#58a6ff;text-align:center;margin-bottom:4px;}p{color:#8b949e;text-align:center;margin-bottom:24px;}
input{width:100%;padding:12px;margin-bottom:12px;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#c9d1d9;font-size:16px;}
input:focus{border-color:#58a6ff;outline:none;}
button{width:100%;padding:12px;background:#238636;border:none;border-radius:8px;color:#fff;font-size:16px;font-weight:600;cursor:pointer;}
</style></head><body>
<div class="box"><h1>EkromVPN</h1><p>แผงจัดการ</p>
<?=isset($error)?"<div style='background:#f8514920;color:#f85149;padding:12px;border-radius:8px;margin-bottom:16px;text-align:center'>$error</div>":""?>
<form method="post"><input type="password" name="pass" placeholder="รหัสผ่าน" required><button>เข้าสู่ระบบ</button></form>
<div style="text-align:center;padding:20px 16px;margin-top:8px;"><a href="?logout=1" style="color:#f85149;text-decoration:none;font-size:14px;padding:10px 24px;border:1px solid #f8514940;border-radius:8px;display:inline-block;">ออกจากระบบ</a></div></div></body></html>
<?php exit;}}
if($_GET["logout"]??null){session_destroy();header("Location: /");exit;}
$page=$_GET["page"]??"dashboard";
if($page==="api_online"){
 $ol2=[];exec("ps aux|grep 'sshd:'|grep -v 'listener\\|grep\\|root'|awk '{print $1}'|grep -v '^root$'|grep -v '^sshd$'|sort -u",$ol2);
 $t=0;$f="/etc/ssh/.ssh.db";if(file_exists($f)){foreach(file($f) as$l){if(preg_match("/^### /",$l))$t++;}}
 header("Content-Type: application/json");echo json_encode(["total"=>$t,"online"=>count($ol2),"users"=>$ol2]);exit;
}
if(($_POST["action"]??$_GET["action"]??null)!=null){
 $a=$_POST["action"]??$_GET["action"]??"";$u=escapeshellcmd($_POST["username"]??$_GET["user"]??"");
 if($a==="create"&&$u){$p=escapeshellcmd($_GET["pass"]??"");$d=intval($_GET["days"]?:30);exec("sudo /usr/local/bin/ssh-admin create $u $p $d 2>/dev/null",$o,$c);$msg=$c===0?"ok_$u":"fail";}
 elseif(($a==="delete"||$a==="del")&&$u){exec("sudo /usr/local/bin/ssh-admin delete $u 2>/dev/null",$o,$c);$msg=$c===0?"del_$u":"nf";}
 elseif($a==="passwd"&&$u){$p=escapeshellcmd($_GET["pass"]??"");exec("sudo /usr/local/bin/ssh-admin passwd $u $p 2>/dev/null",$o,$c);$msg=$c===0?"pw_$u":"pf";}
 elseif($a==="port"&&$p){$c=intval($p);exec("sudo /usr/local/bin/change-port-web $c 2>/dev/null",$o,$c);$msg=$c===0?"port_$p":"pf";}
 header("Location: ?page=$page&msg=$msg");exit;
}
$ip=trim(exec("curl -s ipv4.icanhazip.com")??"");$domain=trim(@file_get_contents("/etc/xray/domain")??"");
$ram_t=trim(exec("free -h|grep Mem|awk '{print $2}'")??"");$ram_u=trim(exec("free -h|grep Mem|awk '{print $3}'")??"");
$uptime=trim(exec("uptime -p|cut -d' ' -f2-")??"");$port="8080";
$users=[];if(file_exists("/etc/ssh/.ssh.db")){foreach(file("/etc/ssh/.ssh.db") as$l){if(preg_match("/^### (\w+)/",$l,$m))$users[]=trim($m[1]);}}
$ol=[];exec("ps aux|grep 'sshd:'|grep -v 'listener\\|grep\\|root'|awk '{print $1}'|grep -v '^root$'|grep -v '^sshd$'|sort -u",$ol);
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>EkromVPN แผงจัดการ</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#0d1117;color:#c9d1d9;}
.header{background:#161b22;border-bottom:1px solid #30363d;padding:12px 16px;display:flex;align-items:center;position:sticky;top:0;z-index:100;}
.header .logo{color:#fff;font-size:17px;font-weight:700;}.header .logo span{color:#58a6ff;}
.header .spacer{flex:1;}.header a{color:#8b949e;text-decoration:none;font-size:13px;padding:4px 8px;}
.nav{display:flex;gap:2px;overflow-x:auto;background:#161b22;border-bottom:1px solid #21262d;padding:0 16px;}
.nav a{color:#8b949e;text-decoration:none;font-size:12px;padding:8px 12px;white-space:nowrap;border-bottom:2px solid transparent;}
.nav a:hover{color:#c9d1d9;background:#1f6feb10;}
.nav a.act{color:#58a6ff;border-bottom-color:#58a6ff;}
.m{max-width:960px;margin:0 auto;padding:16px;}
.hd{margin-bottom:16px;}.hd h2{font-size:20px;color:#c9d1d9;}
.gr{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:16px;}
.cd{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:16px;}
.cd h3{font-size:11px;color:#8b949e;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;}
.rw{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #21262d;}
.rw:last-child{border:none;}.l{color:#8b949e;font-size:13px;}.v{color:#e6edf3;font-size:14px;font-weight:500;}
.on{color:#3fb950;font-weight:600;}.off{color:#8b949e;}
input,select{width:100%;padding:10px;margin:0 0 10px;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#c9d1d9;font-size:14px;}
input:focus,select:focus{border-color:#58a6ff;outline:none;}
button{width:100%;padding:10px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;}
.btn-p{background:#238636;color:#fff;}.btn-d{background:#da3633;color:#fff;}
table{width:100%;border-collapse:collapse;}
th{color:#8b949e;font-size:11px;padding:8px;text-align:left;border-bottom:1px solid #30363d;}
td{padding:8px;border-bottom:1px solid #21262d;font-size:13px;}
.msg{padding:10px;border-radius:8px;margin-bottom:12px;background:#0d1117;border:1px solid #30363d;}
@media(min-width:600px){.header{padding:12px 32px;}.nav{padding:0 32px;}.m{padding:24px 32px;}.gr{grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;}}
</style></head><body>
<div class="header" style="flex-direction:column;padding:16px;gap:4px;"><div class="logo" style="font-size:24px;text-align:center;width:100%;">Ekrom<span>SSH</span> <span style="color:#58a6ff;font-weight:400;">VPN</span></div><div class="spacer"></div></div>

<div class="nav">
<a href="?page=dashboard" class="<?=$page==='dashboard'?'act':''?>">หน้าแรก</a>
<a href="?page=create" class="<?=$page==='create'?'act':''?>">สร้าง SSH</a>
<a href="?page=delete" class="<?=$page==='delete'?'act':''?>">ลบ SSH</a>
<a href="?page=online" class="<?=$page==='online'?'act':''?>">ออนไลน์</a>
<a href="?page=settings" class="<?=$page==='settings'?'act':''?>">ตั้งค่า</a>
</div>
<div class="m">
<div class="hd"><h2><?php $ts=["dashboard"=>"หน้าแรก","create"=>"สร้าง SSH","delete"=>"ลบ SSH","online"=>"ออนไลน์: " . count($ol) . " / ทั้งหมด: " . count($users),"settings"=>"ตั้งค่า"];echo $ts[$page]??"หน้าแรก";?></h2></div>
<?php
$m=$_GET["msg"]??"";
if($m){
 $c="#3fb950";$t="";
 if(strpos($m,"ok_")===0){$t='สร้างชื่อ '.substr($m,3).' สำเร็จ';}
 elseif(strpos($m,"del_")===0){$t='ลบชื่อ '.substr($m,4).' สำเร็จ';}
 elseif(strpos($m,"pw_")===0){$t='เปลี่ยนรหัสผ่านสำเร็จ';}
 elseif($m==="fail"){$c="#f85149";$t='ล้มเหลว';}
 elseif($m==="nf"){$c="#f85149";$t='ไม่พบผู้ใช้';}
 elseif(strpos($m,"port_")===0){$t='เปลี่ยนพอร์ตเป็น '.substr($m,5).' สำเร็จ';}
 if($t)echo '<div class="msg" style="color:'.$c.'">'.$t.'</div>';
}
if($page==="dashboard"):?>
<div class="gr">
<div class="cd"><h3>ระบบ</h3><div class="rw"><span class="l">ไอพี</span><span class="v"><?=$ip?></span></div><div class="rw"><span class="l">โดเมน</span><span class="v"><?=$domain?:'-'?></span></div><div class="rw"><span class="l">RAM</span><span class="v"><?=$ram_u?>/<?=$ram_t?></span></div><div class="rw"><span class="l">อัปไทม์</span><span class="v"><?=$uptime?></span></div><div class="rw"><span class="l">พอร์ต SSH WS</span><span class="v"><?=$port?></span></div></div>
<div class="cd"><h3>ผู้ใช้</h3><div class="rw"><span class="l">ทั้งหมด</span><span class="v"><?=count($users)?></span></div><div class="rw"><span class="l">ออนไลน์</span><span class="v"><?=count($ol)?></span></div></div></div>
<div class="cd"><h3>ผู้ใช้ทั้งหมด</h3><table><tr><th>#</th><th>ชื่อ</th><th>สถานะ</th><th>คงเหลือ</th></tr>
<tbody><?php foreach($users as$i=>$u):
$rem='-';
if(file_exists("/etc/ssh/.ssh.db")){foreach(file("/etc/ssh/.ssh.db") as$l){if(preg_match("/^### $u (\d+)/",$l,$m)){$et=intval($m[1]);$diff=$et-time();if($diff>0){$d=floor($diff/86400);$h=floor(($diff%86400)/3600);$rem=$d.'d '.$h.'h';}else{$rem='<span style="color:#f85149">Expired</span>';}break;}}
}
?><tr><td><?=$i+1?></td><td><?=$u?></td><td class="<?=in_array($u,$ol)?'on':'off'?>"><?=in_array($u,$ol)?'● ออนไลน์':'○ ออฟไลน์'?></td><td style="font-size:12px;color:#8b949e"><?=$rem?></td></tr><?php endforeach;?></tbody></table></div>

<?php elseif($page==="create"):?>
<div class="gr"><div class="cd"><h3>สร้างผู้ใช้ SSH</h3><div><input id="c_user" placeholder="ชื่อผู้ใช้"><input id="c_pass" placeholder="รหัสผ่าน"><input id="c_days" type="number" value="30"><button class="btn-p" onclick="var u=document.getElementById('c_user').value,p=document.getElementById('c_pass').value,d=document.getElementById('c_days').value;if(u&&p){location.href='?page=create&action=create&user='+u+'&pass='+p+'&days='+d;}">สร้าง</button></div></div>
<div class="cd"><h3>เปลี่ยนรหัสผ่าน</h3><div><select id="cp_user"><option value="">เลือก...</option><?php foreach($users as$u):?><option value="<?=$u?>"><?=$u?></option><?php endforeach;?></select><input id="cp_pass" placeholder="รหัสผ่านใหม่"><button class="btn-p" onclick="var u=document.getElementById('cp_user').value,p=document.getElementById('cp_pass').value;if(u&&p){location.href='?page=create&action=passwd&user='+u+'&pass='+p;}">เปลี่ยน</button></div></div>
<div class="cd"><h3>เปลี่ยนพอร์ต SSH WS</h3>
<div><input id="new_port" type="number" placeholder="พอร์ตใหม่" value="8080">
<button class="btn-p" onclick="var p=document.getElementById('new_port').value;if(p&&p!=8080){location.href='?page=create&action=port&pass='+p;}">เปลี่ยนพอร์ต</button></div></div>
<?php elseif($page==="delete"):?>
<div class="cd" style="max-width:400px"><h3>ลบผู้ใช้ SSH</h3><div><select id="del_user"><option value="">เลือก...</option><?php foreach($users as$u):?><option value="<?=$u?>"><?=$u?></option><?php endforeach;?></select><button class="btn-d" onclick="if(document.getElementById('del_user').value){location.href='?page=delete&action=del&user='+document.getElementById('del_user').value;}">ลบ SSH</button></div></div>

<?php elseif($page==="online"):?>
<div class="cd"><h3>ผู้ใช้ออนไลน์</h3><table><tr><th>#</th><th>ชื่อ</th><th>สถานะ</th></tr>
<tbody id="olist"><?php foreach($ol as$i=>$u):?><tr><td><?=$i+1?></td><td><?=$u?></td><td class="on">● ออนไลน์</td></tr><?php endforeach;?></tbody></table></div>
<script>function upO(){fetch("?page=api_online").then(r=>r.json()).then(d=>{
var o=document.getElementById("olist");if(o){var h="";d.users.forEach(function(v,i){h+="<tr><td>"+(i+1)+"</td><td>"+v+"</td><td class=\"on\">● ออนไลน์</td></tr>";});o.innerHTML=h||"<tr><td colspan=3>No users</td></tr>";}
}).catch(()=>{});setTimeout(upO,10000);}setTimeout(upO,10000);</script>

<?php elseif($page==="settings"):?>
<div class="cd"><h3>ข้อมูลระบบ</h3><div class="rw"><span class="l">พอร์ต SSH WS</span><span class="v"><?=$port?></span></div><div class="rw"><span class="l">ไอพี</span><span class="v"><?=$ip?></span></div><div class="rw"><span class="l">โดเมน</span><span class="v"><?=$domain?:'-'?></span></div></div>
<?php endif;?>
<div style="text-align:center;padding:20px 16px;margin-top:8px;"><a href="?logout=1" style="color:#f85149;text-decoration:none;font-size:14px;padding:10px 24px;border:1px solid #f8514940;border-radius:8px;display:inline-block;">ออกจากระบบ</a></div></div></body></html>