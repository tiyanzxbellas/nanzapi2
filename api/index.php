<?php
error_reporting(0);
ini_set('display_errors', '0');
if(session_status()==PHP_SESSION_NONE){session_start();}
define('API_FOLDER','api');
define('LINK_WA_REQUEST','https://wa.me/6283862356347?text=Halo+saya+ingin+request+fitur+baru+untuk+TiyanzApi');
define('LINK_WA_CHANNEL','https://whatsapp.com/channel/0029VbCs8HM9xVJd31fSu60X');
define('LINK_GROUP','https://chat.whatsapp.com/IJYEqJQ7A1B9dGUflHCDeK');
define('LINK_CHAT','https://api-tiyanz.my.id/chat');
define('BANNER_VIDEO_DARK','https://kappa.lol/2HLn5j');
define('BANNER_VIDEO_LIGHT','https://kappa.lol/uJWEb9');
define('LINK_NONTON_BACA','https://api-nanas.my.id');

function normalize_path($p){return str_replace(['\\','//'],'/',$p);}
function detect_method($c){return preg_match('/\$_(POST|FILES)\s*\[/i',$c)?'POST':'GET';}
function detect_params($c,$m='GET'){
  $p=[];
  if(preg_match_all('/\$_(GET|POST|REQUEST|FILES)\s*\[\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*\]/',$c,$r))foreach($r[2]as$v)$p[]=$v;
  if(preg_match_all('/filter_input\s*\(\s*INPUT_(?:GET|POST|REQUEST)\s*,\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]/i',$c,$r))foreach($r[1]as$v)$p[]=$v;
  if(preg_match_all('/@\$_(GET|POST|REQUEST)\s*\[\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*\]/',$c,$r))foreach($r[2]as$v)$p[]=$v;
  if(preg_match_all('/isset\s*\(\s*\\$_(GET|POST|REQUEST|FILES)\s*\[\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*\]\s*\)/i',$c,$r))foreach($r[2]as$v)$p[]=$v;
  return array_values(array_unique($p));
}
function parse_file_metadata($c){
  $m=['description'=>'Endpoint API','examples'=>[],'param_desc'=>[],'options'=>[]];
  if(preg_match('/\/\/\s*(?:Deskripsi|Description|Desc)\s*:\s*(.*)/i',$c,$r))$m['description']=trim($r[1]);
  if(preg_match('/\/\/\s*(?:Contoh|Example|Examples)\s*:\s*(\{.*\})/i',$c,$r)){$d=json_decode(trim($r[1]),true);if(json_last_error()===JSON_ERROR_NONE&&is_array($d))$m['examples']=$d;}
  if(preg_match_all('/\/\/\s*@param\s+([a-zA-Z0-9_\-]+)(?:\s*\((.*?)\))?\s+(.*)/i',$c,$r)){
    for($i=0;$i<count($r[1]);$i++){$n=trim($r[1][$i]);$m['param_desc'][$n]=trim($r[3][$i]);if(!empty(trim($r[2][$i])))$m['options'][$n]=array_map('trim',preg_split('/[,|]/',trim($r[2][$i])));}
  }
  return $m;
}
function generate_smart_example($pn,$fn,$d){return in_array(strtolower($pn),['file','image','video','audio','img','upload'])?'':'test_value';}
$base_api_dir=__DIR__;
function build_api_tree($dir,$base_dir){
  $tree=['folders'=>[],'files'=>[]];
  $files=glob($dir.'/*');if($files===false)return $tree;
  foreach($files as $file){
    $b=basename($file);
    if(is_dir($file)){
      $skip=['config','vendor','assets','css','js','upload','uploads','lib','libs','cache','tmp','temp','node_modules'];
      if(in_array(strtolower($b),$skip))continue;
      $sub=build_api_tree($file,$base_dir);
      if(!empty($sub['folders'])||!empty($sub['files']))$tree['folders'][$b]=$sub;
    }elseif(is_file($file)&&pathinfo($file,PATHINFO_EXTENSION)==='php'){
      if(strtolower($b)==='index.php')continue;
      $fc=@file_get_contents($file);if($fc===false)continue;
      $method=detect_method($fc);$params=detect_params($fc,$method);$meta=parse_file_metadata($fc);
      $ex=[];foreach($params as$param){$ex[$param]=isset($meta['examples'][$param])?(string)$meta['examples'][$param]:generate_smart_example($param,$b,$dir);}
      $rel=trim(str_replace(normalize_path($base_dir),'',normalize_path(dirname($file))),'/');
      $up=$rel?$rel.'/':'';
      $tree['files'][]=['name'=>ucwords(str_replace(['-','_','.php'],[' ',' ',''],$b)),'filename'=>$b,
        'url'=>"https://".$_SERVER['HTTP_HOST']."/".API_FOLDER."/".$up.$b,
        'base_url'=>"https://".$_SERVER['HTTP_HOST']."/".API_FOLDER."/".$up.$b,
        'method'=>$method,'params'=>$params,'description'=>$meta['description'],
        'examples'=>$ex,'param_descs'=>$meta['param_desc'],'options'=>$meta['options'],'path_key'=>$rel.'/'.$b];
    }
  }
  ksort($tree['folders']);usort($tree['files'],fn($a,$b)=>strcmp($a['name'],$b['name']));return $tree;
}
if(isset($_GET['fetch_api_data'])){
  header('Content-Type: application/json');header('Cache-Control: no-cache, no-store, must-revalidate');
  if(is_dir($base_api_dir)){
    $full_tree=build_api_tree($base_api_dir,$base_api_dir);$endpoint_id=1;
    function build_json_tree($td,$fn,$ip,$depth){
      global $endpoint_id;
      $node=['type'=>'folder','id'=>$ip,'name'=>strtoupper(str_replace(['-','_'],' ',$fn)),'depth'=>$depth,'children'=>[]];
      foreach($td['folders']as$sn=>$st)$node['children'][]=build_json_tree($st,$sn,$ip.'_'.substr(md5($sn),0,6),$depth+1);
      foreach($td['files']as$f){
        $pf=[];foreach($f['params']as$pn){
          $t='string';$pl=strtolower($pn);
          if(in_array($pl,['file','image','img','video','audio','document','pdf','upload']))$t='file';
          $do=$f['options'][$pn]??[];if(!empty($do))$t='select';
          $ev=$f['examples'][$pn]??'';$pd=$f['param_descs'][$pn]??($t==='file'?'Upload File':($ev?'e.g. '.$ev:'Enter value'));
          $pf[]=['name'=>$pn,'type'=>$t,'required'=>true,'description'=>$pd,'default_value'=>$t==='file'?'':$ev,'placeholder'=>$t==='file'?'':($ev?:$pn),'options'=>$do];
        }
        $node['children'][]=['type'=>'file','id'=>(string)$endpoint_id,'name'=>$f['name'],'url'=>$f['base_url'],'method'=>$f['method'],'description'=>$f['description'],'params'=>$pf];
        $endpoint_id++;
      }
      return $node;
    }
    echo json_encode(['tree'=>build_json_tree($full_tree,'Root','root',0)],JSON_PRETTY_PRINT);
  }else echo json_encode(['tree'=>['type'=>'folder','children'=>[]]]);
  exit;
}
function count_endpoints($t){$c=count($t['files']);foreach($t['folders']as$s)$c+=count_endpoints($s);return $c;}
$api_tree=is_dir($base_api_dir)?build_api_tree($base_api_dir,$base_api_dir):['folders'=>[],'files'=>[]];
$total_endpoints=count_endpoints($api_tree);
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>TiyanzApi | Docs</title>
<link rel="icon" type="image/png" href="https://tiyanzcode.my.id/uploads/6a1a993b3e680_1780128059.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── TOKENS ─────────────────────────────────────────── */
[data-theme="dark"]{
  --bg:#08081a; --bg2:#0c0c20; --card:#111128; --card2:#161638; --card3:#1b1b40;
  --b1:rgba(110,60,230,.16); --b2:rgba(110,60,230,.32); --b3:rgba(110,60,230,.52);
  --p:#7c3aed; --p2:#9061f9; --p3:#b48bfc;
  --pg:rgba(124,58,237,.18);
  --cyan:#22d3ee; --green:#22c55e; --orange:#f97316; --yellow:#fbbf24; --red:#f43f5e;
  --tx:#eeeeff; --tx2:#c0c0e0; --mu:#4a4a78; --mu2:#8080b8;
  --ic:#6b7280; --nav:rgba(8,8,26,.90);
  --inp:#070716; --resp:#050514;
}
[data-theme="light"]{
  --bg:#f2f0ff; --bg2:#ebe7ff; --card:#ffffff; --card2:#f6f3ff; --card3:#eee9ff;
  --b1:rgba(100,50,200,.12); --b2:rgba(100,50,200,.26); --b3:rgba(100,50,200,.48);
  --p:#6d28d9; --p2:#7c3aed; --p3:#8b5cf6;
  --pg:rgba(109,40,217,.12);
  --cyan:#0891b2; --green:#16a34a; --orange:#ea580c; --yellow:#d97706; --red:#dc2626;
  --tx:#1e1040; --tx2:#3d3070; --mu:#a090c0; --mu2:#6050a0;
  --ic:#9080b8; --nav:rgba(242,240,255,.93);
  --inp:#eae6ff; --resp:#e6e2ff;
}
/* ── BASE ───────────────────────────────────────────── */
:root{--mono:'Share Tech Mono',monospace;--head:'Orbitron',sans-serif;--body:'Rajdhani',sans-serif;--r:14px;--rs:10px;--rx:7px;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{scroll-behavior:smooth;}
body{background:var(--bg);color:var(--tx);font-family:var(--body);font-size:15px;min-height:100vh;overflow-x:hidden;transition:background .3s,color .3s;}
body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;background-image:radial-gradient(circle,var(--b1) 1px,transparent 1px);background-size:32px 32px;opacity:.7;}
.blob{position:fixed;border-radius:50%;pointer-events:none;z-index:0;will-change:transform;}
.b1{width:420px;height:420px;top:-140px;right:-140px;background:radial-gradient(circle,var(--pg) 0%,transparent 70%);animation:bF 14s ease-in-out infinite alternate;}
.b2{width:360px;height:360px;bottom:0;left:-120px;background:radial-gradient(circle,var(--pg) 0%,transparent 70%);animation:bF 11s ease-in-out infinite alternate-reverse;}
@keyframes bF{0%{transform:translate(0,0);}100%{transform:translate(18px,-22px);}}
::-webkit-scrollbar{width:4px;} ::-webkit-scrollbar-track{background:transparent;} ::-webkit-scrollbar-thumb{background:var(--b2);border-radius:4px;}

/* ── PARTIKEL UNGU ─────────────────────────────────── */
.particles-container{position:fixed;inset:0;z-index:0;pointer-events:none;}
.particle{position:absolute;background:radial-gradient(circle,var(--p2) 0%,transparent 80%);border-radius:50%;animation:floatUp ease-out infinite;opacity:0;filter:blur(1px);}
@keyframes floatUp{0%{transform:translateY(100vh) scale(0);opacity:0;}15%{opacity:0.7;}85%{opacity:0.1;}100%{transform:translateY(-120px) scale(1.5);opacity:0;}}

/* ── SPLASH SCREEN ─────────────────────────────────── */
#splash{position:fixed;inset:0;z-index:99999;background:var(--bg);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;transition:opacity .5s ease,visibility .5s ease;}
#splash.hidden{opacity:0;visibility:hidden;pointer-events:none;}
#splash .splash-logo{font-family:var(--head);font-size:2rem;font-weight:900;color:var(--p2);letter-spacing:4px;animation:logoGlow 1.5s ease-in-out infinite;}
#splash .splash-logo em{color:var(--p3);font-style:normal;}
.splash-bar-wrap{width:200px;height:4px;background:var(--card2);border-radius:4px;overflow:hidden;}
.splash-bar-fill{height:100%;width:0%;border-radius:4px;background:linear-gradient(90deg,#7c3aed,#a78bfa,#c084fc,#7c3aed);background-size:300% 100%;animation:splashFill 2s ease-out forwards, loadingBarRGB 1.5s linear infinite;}
@keyframes splashFill{0%{width:0%;}100%{width:100%;}}
@keyframes loadingBarRGB{0%{background-position:300% 0;}100%{background-position:-300% 0;}}

/* ── VID LOADER BAR ────────────────────────────────── */
.vid-loader{position:absolute;bottom:0;left:0;right:0;height:3px;z-index:5;pointer-events:none;opacity:0;transition:opacity .3s;}
.vid-loader.active{opacity:1;}
.vid-loader .vid-load-fill{height:100%;width:100%;background:linear-gradient(90deg,#7c3aed,#a78bfa,#c084fc,#7c3aed);background-size:300% 100%;animation:loadingBarRGB 1.2s linear infinite;}

/* ── POLLING DOT ───────────────────────────────────── */
.polling-dot{display:inline-block;width:7px;height:7px;background:var(--p2);border-radius:50%;margin-left:6px;vertical-align:middle;opacity:0;transition:opacity .25s;box-shadow:0 0 6px var(--p2);}
.polling-dot.active{opacity:1;animation:pulse 1.4s ease-in-out infinite;}

/* ── TOTAL ENDPOINT BADGE ─────────────────────────── */
.total-ep-badge{display:inline-flex;align-items:center;gap:4px;font-family:var(--mono);font-size:.58rem;font-weight:700;color:var(--p3);background:rgba(124,58,237,.15);border:1px solid var(--b2);border-radius:999px;padding:3px 10px;margin-left:8px;vertical-align:middle;white-space:nowrap;letter-spacing:.5px;transition:all .25s;}
.total-ep-badge i{font-size:.52rem;color:var(--p2);}
.total-ep-badge span{color:var(--tx2);}

/* ── BOTTOM ACTION BUTTON (DIPERKECIL) ────────────── */
.bottom-actions{padding:12px 16px;margin-top:16px;display:flex;gap:8px;animation:fadeUp .4s ease both;}
.bottom-actions .btn-nonton-baca{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;background:linear-gradient(135deg,var(--p),var(--p2));color:#fff;border:none;border-radius:var(--rx);font-family:var(--head);font-size:.58rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;text-decoration:none;box-shadow:0 3px 12px var(--pg);transition:transform .18s,box-shadow .18s;}
.bottom-actions .btn-nonton-baca:hover{transform:translateY(-1px);box-shadow:0 6px 18px var(--pg);}
.bottom-actions .btn-nonton-baca:active{transform:scale(.97);}
.bottom-actions .btn-nonton-baca i{font-size:.65rem;}
.bottom-actions .total-endpoint-inline{display:flex;align-items:center;gap:4px;padding:10px 12px;background:var(--card);border:1px solid var(--b2);border-radius:var(--rx);font-family:var(--mono);font-size:.58rem;font-weight:700;color:var(--p3);white-space:nowrap;}
.bottom-actions .total-endpoint-inline i{color:var(--p2);font-size:.6rem;}
.bottom-actions .total-endpoint-inline span{color:var(--tx2);font-size:.65rem;}

/* ── ANIMATIONS ─────────────────────────────────────── */
@keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
@keyframes logoGlow{0%,100%{opacity:1;}50%{opacity:.7;}}
@keyframes slideIn{from{opacity:0;transform:translateX(-12px);}to{opacity:1;transform:translateX(0);}}
/* ── WRAPPER ────────────────────────────────────────── */
.wrap{position:relative;z-index:1;max-width:480px;margin:0 auto;padding:0 0 30px;display:flex;flex-direction:column;}
/* ── NAV ────────────────────────────────────────────── */
.nav{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;position:sticky;top:0;z-index:200;background:var(--nav);border-bottom:1px solid var(--b2);}
.nav-left{display:flex;align-items:center;gap:6px;}
.nav-logo{font-family:var(--head);font-size:.82rem;font-weight:900;color:var(--p2);letter-spacing:3px;animation:logoGlow 3s ease-in-out infinite;}
.nav-logo em{color:var(--p3);font-style:normal;}
.nav-r{display:flex;gap:8px;}
.nbtn{width:33px;height:33px;border-radius:9px;background:var(--card2);border:1px solid var(--b2);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--mu2);font-size:.78rem;transition:border-color .2s,color .2s;}
.nbtn:hover{border-color:var(--p2);color:var(--p2);}
/* ── HERO ───────────────────────────────────────────── */
.hero{margin:0;animation:fadeUp .45s ease both;overflow:visible;padding:10px 10px 0 10px;}
.vid-box{position:relative;overflow:hidden;height:190px;background:var(--bg);border-radius:var(--r) var(--r) 0 0;}
.vid-box video{width:100%;height:100%;object-fit:cover;display:block;}
.hero-body{background:var(--card);border:1px solid var(--b2);border-radius:var(--r);border-top:none;padding:16px 18px 20px;position:relative;overflow:hidden;}
.hero-body::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--p2),transparent);}
.eyebrow{font-family:var(--mono);font-size:.58rem;color:var(--p3);letter-spacing:3px;text-transform:uppercase;margin-bottom:7px;display:flex;align-items:center;gap:6px;}
.eyebrow::before{content:'';width:14px;height:1px;background:var(--p3);}
.htitle{font-family:var(--head);font-size:1.6rem;font-weight:900;color:var(--tx);letter-spacing:2px;line-height:1.05;margin-bottom:4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.htitle span{color:var(--p2);}
.htitle .hero-badge{font-family:var(--mono);font-size:.65rem;font-weight:700;color:var(--p3);background:rgba(124,58,237,.12);border:1px solid var(--b2);border-radius:999px;padding:4px 12px;letter-spacing:1px;display:inline-flex;align-items:center;gap:5px;}
.htitle .hero-badge i{font-size:.55rem;color:var(--p2);}
.hsub{font-family:var(--mono);font-size:.66rem;color:var(--mu2);margin-bottom:16px;line-height:1.65;}
.hbtns{display:flex;gap:7px;flex-wrap:wrap;}
.btn-o{display:inline-flex;align-items:center;gap:6px;background:transparent;border:1px solid var(--b2);color:var(--mu2);border-radius:var(--rx);padding:8px 12px;font-family:var(--head);font-size:.58rem;font-weight:700;letter-spacing:1px;cursor:pointer;text-decoration:none;text-transform:uppercase;transition:border-color .2s,color .2s,background .2s;}
.btn-o:hover{border-color:var(--p2);color:var(--p2);background:rgba(124,58,237,.05);}
/* ── SECTION ────────────────────────────────────────── */
.sec{padding:0 16px;margin-top:12px;}
/* ── SEARCH ─────────────────────────────────────────── */
.srch{position:relative;}
.srch-ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--mu);font-size:.78rem;pointer-events:none;}
.srch-inp{width:100%;background:var(--card);border:1px solid var(--b2);border-radius:var(--rs);padding:12px 12px 12px 38px;font-family:var(--mono);font-size:.74rem;color:var(--tx);outline:none;transition:border-color .2s,box-shadow .2s;}
.srch-inp::placeholder{color:var(--mu);}
.srch-inp:focus{border-color:var(--p2);box-shadow:0 0 0 2px var(--pg);}
/* ── CATEGORY CHIPS ─────────────────────────────────── */
.chips-wrap{padding:10px 16px 2px;overflow-x:auto;-webkit-overflow-scrolling:touch;display:flex;gap:8px;flex-wrap:nowrap;scrollbar-width:none;-ms-overflow-style:none;}
.chips-wrap::-webkit-scrollbar{display:none;}
.chip{display:inline-flex;align-items:center;gap:6px;flex-shrink:0;padding:7px 14px;border-radius:999px;cursor:pointer;font-family:var(--head);font-size:.58rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;background:var(--card2);border:1px solid var(--b2);color:var(--mu2);transition:transform .18s,background .18s,border-color .18s,color .18s;user-select:none;will-change:transform;}
.chip:hover{transform:translateY(-1px);border-color:var(--p2);color:var(--p2);}
.chip.active{background:var(--p);border-color:var(--p2);color:#fff;}
.chip i{font-size:.7rem;}
/* ── ENDPOINT PANEL ─────────────────────────────────── */
.ep-panel{padding:0 16px;margin-top:20px;display:flex;flex-direction:column;gap:6px;}
.ep-panel.hidden{display:none;}
.sub-heading{font-family:var(--head);font-size:.72rem;font-weight:700;color:var(--p3);letter-spacing:2px;text-transform:uppercase;padding:12px 6px 6px;border-bottom:1px solid var(--b1);margin-bottom:6px;display:flex;align-items:center;gap:7px;}
.sub-heading i{color:var(--ic);font-size:.8rem;}
/* ── ENDPOINT ITEM ──────────────────────────────────── */
.ep-item{background:var(--card);border:1px solid var(--b1);border-radius:var(--rs);overflow:hidden;transition:border-color .2s;animation:slideIn .22s ease both;}
.ep-item.open{border-color:var(--p2);}
.ep-hdr{display:flex;align-items:center;gap:10px;padding:13px 16px;cursor:pointer;transition:background .18s;}
.ep-hdr:hover{background:rgba(124,58,237,.05);}
.ep-item.open .ep-hdr{background:rgba(124,58,237,.08);}
.mb{font-family:var(--mono);font-size:.6rem;font-weight:700;padding:4px 8px;border-radius:4px;letter-spacing:.5px;flex-shrink:0;}
.g{background:rgba(34,197,94,.11);color:#22c55e;border:1px solid rgba(34,197,94,.2);}
.po{background:rgba(124,58,237,.11);color:var(--p3);border:1px solid rgba(124,58,237,.22);}
.d{background:rgba(244,63,94,.11);color:var(--red);border:1px solid rgba(244,63,94,.2);}
.ep-name{flex:1;font-family:var(--mono);font-size:.8rem;font-weight:700;color:var(--mu2);transition:color .18s;}
.ep-item.open .ep-name{color:var(--p3);}
.ep-chv{color:var(--mu);font-size:.7rem;transition:transform .25s,color .18s;flex-shrink:0;}
.ep-item.open .ep-chv{transform:rotate(180deg);color:var(--p2);}
.ep-bw{display:grid;grid-template-rows:0fr;transition:grid-template-rows .28s ease;}
.ep-item.open .ep-bw{grid-template-rows:1fr;}
.ep-b{overflow:hidden;min-height:0;}
.ep-bi{padding:13px 14px;border-top:1px solid var(--b1);opacity:0;transition:opacity .18s .08s;}
.ep-item.open .ep-bi{opacity:1;}
.ep-info{display:flex;align-items:flex-start;gap:7px;background:rgba(124,58,237,.06);border:1px solid rgba(124,58,237,.16);border-radius:var(--rx);padding:8px 11px;margin-bottom:12px;font-family:var(--mono);font-size:.64rem;color:var(--mu2);line-height:1.6;}
.ep-info i{color:var(--p3);flex-shrink:0;margin-top:1px;}
.fg{margin-bottom:10px;}
.fl{display:flex;align-items:center;gap:5px;font-family:var(--mono);font-size:.6rem;font-weight:700;color:var(--mu2);margin-bottom:4px;}
.fn{color:var(--tx2);} .ft{background:var(--card3);border:1px solid var(--b1);padding:1px 5px;border-radius:3px;font-size:.52rem;color:var(--mu);}
.fr{color:var(--p3);font-size:.56rem;} .fh{color:var(--mu);font-size:.56rem;font-weight:400;margin-left:auto;}
.fi{width:100%;background:var(--inp);border:1px solid var(--b2);border-radius:var(--rx);padding:9px 11px;font-family:var(--mono);font-size:.7rem;color:var(--tx);outline:none;transition:border-color .18s,box-shadow .18s;}
.fi::placeholder{color:var(--mu);}
.fi:focus{border-color:var(--p2);box-shadow:0 0 0 2px var(--pg);}
input[type="file"].fi{padding:6px 9px;cursor:pointer;color:var(--mu);}
input[type="file"]::file-selector-button{background:rgba(124,58,237,.13);color:var(--p3);border:1px solid rgba(124,58,237,.28);padding:4px 9px;border-radius:4px;font-family:var(--mono);font-size:.6rem;font-weight:700;cursor:pointer;margin-right:7px;}
select.fi{appearance:none;background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234a4a78' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");background-repeat:no-repeat;background-position:right 9px center;background-size:11px;padding-right:26px;cursor:pointer;}
select.fi option{background:var(--card);color:var(--tx);}
.ul{font-family:var(--mono);font-size:.56rem;font-weight:700;color:var(--mu);margin-bottom:4px;display:block;letter-spacing:1.5px;text-transform:uppercase;}
.ub{background:var(--inp);border:1px solid var(--b1);border-radius:var(--rx);padding:7px 10px;display:flex;align-items:flex-start;gap:6px;margin-bottom:10px;}
.ut{font-family:var(--mono);font-size:.62rem;color:var(--cyan);word-break:break-all;flex:1;line-height:1.5;}
.icb{background:transparent;border:none;cursor:pointer;color:var(--mu);padding:2px 4px;border-radius:4px;font-size:.72rem;flex-shrink:0;transition:background .15s,color .15s;}
.icb:hover{background:var(--card3);color:var(--tx);}
.xbtn{width:100%;padding:14px 16px;background:linear-gradient(135deg,var(--p),var(--p2));color:#fff;border:none;border-radius:var(--rx);font-family:var(--head);font-size:.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px var(--pg);transition:transform .18s,box-shadow .18s,opacity .18s;margin-top:4px;}
.xbtn:hover{transform:translateY(-2px);box-shadow:0 8px 22px var(--pg);}
.xbtn:active{transform:scale(.97);}
.xbtn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none;}
.rw{margin-top:10px;border:1px solid var(--b1);border-radius:var(--rx);overflow:hidden;background:var(--resp);}
.rh{background:var(--card3);padding:6px 10px;display:flex;align-items:center;gap:6px;border-bottom:1px solid var(--b1);}
.rd{width:7px;height:7px;border-radius:50%;background:var(--mu);transition:background .25s;}
.rd.ok{background:var(--green);box-shadow:0 0 5px var(--green);}
.rd.err{background:var(--red);box-shadow:0 0 5px var(--red);}
.rd.ld{background:var(--yellow);animation:pulse .9s infinite;}
.rs{font-family:var(--mono);font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:3px;background:var(--card2);color:var(--mu2);transition:all .2s;white-space:nowrap;}
.rs.ok{background:rgba(34,197,94,.11);color:var(--green);}
.rs.err{background:rgba(244,63,94,.11);color:var(--red);}
.rhr{margin-left:auto;}
.rb{padding:10px;max-height:300px;overflow-y:auto;}
.rp{font-family:var(--mono);font-size:.65rem;color:var(--mu2);white-space:pre-wrap;word-break:break-word;margin:0;line-height:1.65;}
.jk{color:#93c5fd;} .js{color:#86efac;} .jn{color:var(--yellow);} .jb{color:#f9a8d4;} .jnl{color:var(--red);}
[data-theme="light"] .jk{color:#4338ca;} [data-theme="light"] .js{color:#166534;} [data-theme="light"] .jn{color:#b45309;} [data-theme="light"] .jb{color:#9d174d;} [data-theme="light"] .jnl{color:#b91c1c;}
[data-theme="light"] .rp{color:#374151;}
.empty{text-align:center;padding:44px 16px;font-family:var(--mono);font-size:.7rem;color:var(--mu);animation:fadeIn .35s ease;}
.empty i{font-size:1.6rem;display:block;margin-bottom:10px;color:var(--b2);}
footer{text-align:center;padding:22px 16px 8px;font-family:var(--mono);font-size:.58rem;color:var(--mu);line-height:2;}
footer a{color:var(--mu);text-decoration:none;transition:color .18s;}
footer a:hover{color:var(--p3);}
.toast{position:fixed;bottom:82px;right:14px;left:14px;max-width:280px;margin:0 auto;background:var(--card2);border:1px solid var(--p2);color:var(--p3);padding:9px 13px;border-radius:var(--rx);font-family:var(--mono);font-size:.66rem;font-weight:700;box-shadow:0 8px 24px rgba(0,0,0,.45);transform:translateY(60px) scale(.96);opacity:0;transition:transform .26s cubic-bezier(.34,1.56,.64,1),opacity .26s;z-index:9999;display:flex;align-items:center;gap:7px;}
.toast.show{transform:translateY(0) scale(1);opacity:1;}
.td{position:fixed;top:56px;right:14px;z-index:999;background:var(--card);border:1px solid var(--b2);border-radius:var(--r);padding:8px;width:168px;box-shadow:0 14px 40px rgba(0,0,0,.55);transform:scale(.88) translateY(-8px);opacity:0;pointer-events:none;transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .2s;transform-origin:top right;}
.td.open{transform:scale(1) translateY(0);opacity:1;pointer-events:all;}
.td-h{font-family:var(--mono);font-size:.52rem;font-weight:700;color:var(--mu);letter-spacing:2.5px;padding:4px 8px 7px;border-bottom:1px solid var(--b1);margin-bottom:6px;text-transform:uppercase;}
.to{display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:8px;cursor:pointer;font-family:var(--mono);font-size:.64rem;font-weight:700;color:var(--mu2);user-select:none;transition:background .15s,color .15s;}
.to:hover{background:var(--card2);color:var(--tx);}
.to.active{background:rgba(124,58,237,.13);color:var(--p3);}
.tsw{width:16px;height:16px;border-radius:4px;flex-shrink:0;border:2px solid transparent;transition:border-color .15s;}
.to.active .tsw{border-color:var(--p2);}
.tck{margin-left:auto;color:var(--p3);font-size:.6rem;opacity:0;transition:opacity .18s;}
.to.active .tck{opacity:1;}
.bdl{display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--b1);color:var(--mu2);padding:6px 12px;border-radius:var(--rx);cursor:pointer;font-family:var(--mono);font-size:.62rem;font-weight:700;text-decoration:none;margin-top:8px;transition:border-color .18s,color .18s;}
.bdl:hover{border-color:var(--p2);color:var(--p3);}
</style>
</head>
<body>

<div id="splash">
  <div class="splash-logo">TIYANZ<em>API</em></div>
  <div class="splash-bar-wrap"><div class="splash-bar-fill"></div></div>
</div>

<div class="blob b1"></div>
<div class="blob b2"></div>
<div class="particles-container" id="particles-container"></div>

<nav class="nav">
  <div class="nav-left">
    <div class="nav-logo">TIYANZ<em>API</em><span class="polling-dot" id="polling-dot" title="Memeriksa endpoint baru..."></span></div>
    <div class="total-ep-badge" id="total-ep-badge" title="Total Endpoint">
      <i class="fas fa-bolt"></i><span><?= $total_endpoints ?></span>
    </div>
  </div>
  <div class="nav-r">
    <button class="nbtn" id="tb" onclick="toggleTD()" title="Tema"><i class="fas fa-circle-half-stroke"></i></button>
  </div>
</nav>

<div class="td" id="td">
  <div class="td-h">// TEMA</div>
  <div class="to active" data-theme="dark" onclick="setTheme('dark')">
    <div class="tsw" style="background:linear-gradient(135deg,#08081a,#7c3aed)"></div>🌙 Dark<i class="fas fa-check tck"></i>
  </div>
  <div class="to" data-theme="light" onclick="setTheme('light')">
    <div class="tsw" style="background:linear-gradient(135deg,#f2f0ff,#6d28d9);border:1px solid #c4b5fd"></div>☀️ Light<i class="fas fa-check tck"></i>
  </div>
</div>

<div class="wrap">
  <div class="hero">
    <div class="vid-box" id="vid-box">
      <div class="vid-loader active" id="vid-loader"><div class="vid-load-fill"></div></div>
      <video autoplay muted loop playsinline preload="none" id="hero-vid"><source src="" type="video/mp4"></video>
    </div>
    <div class="hero-body">
      <div class="eyebrow">FREE REST API</div>
      <div class="htitle">
        TIYANZAPI<br><span>DOCS</span>
        <div class="hero-badge" id="hero-badge" title="Total Endpoint">
          <i class="fas fa-bolt"></i><span><?= $total_endpoints ?></span>
        </div>
      </div>
      <div class="hsub">Explore &amp; test live endpoints — file upload included.</div>
      <div class="hbtns">
        <a href="<?= LINK_WA_REQUEST ?>" target="_blank" class="btn-o"><i class="fas fa-code"></i> Contact Dev</a>
        <a href="<?= LINK_WA_REQUEST ?>" target="_blank" class="btn-o"><i class="fas fa-bug"></i> Feedback</a>
        <a href="<?= LINK_WA_CHANNEL ?>" target="_blank" class="btn-o"><i class="fab fa-whatsapp"></i> Channel</a>
        <a href="<?= LINK_GROUP ?>" target="_blank" class="btn-o"><i class="fas fa-users"></i> Group</a>
      </div>
    </div>
  </div>

  <div class="sec" style="margin-top:14px;animation:fadeUp .4s .1s ease both;">
    <div class="srch">
      <i class="fas fa-search srch-ic"></i>
      <input type="text" id="srch-inp" class="srch-inp" placeholder="Cari endpoint..." oninput="onSearch()">
    </div>
  </div>

  <div class="chips-wrap" id="chips-wrap"></div>
  <div id="ep-panel" class="ep-panel hidden"></div>
  <div id="search-panel" class="ep-panel hidden" style="margin-top:4px;"></div>

  <!-- BOTTOM ACTION - UKURAN DIPERKECIL -->
  <div class="bottom-actions">
    <a href="<?= LINK_NONTON_BACA ?>" target="_blank" class="btn-nonton-baca">
      <i class="fas fa-play"></i> Endpoint Nonton &amp; Baca <i class="fas fa-book-open"></i>
    </a>
    <div class="total-endpoint-inline">
      <i class="fas fa-bolt"></i><span id="bottom-ep-count"><?= $total_endpoints ?></span>
    </div>
  </div>

  <footer>
    TIYANZAPI V2 &copy; <?= date('Y') ?> · All rights reserved<br>
    <a href="#">Docs</a> · <a href="<?= LINK_WA_REQUEST ?>">Support</a>
  </footer>
</div>

<div id="toast" class="toast"></div>

<script>
window.addEventListener('DOMContentLoaded',()=>{setTimeout(()=>{document.getElementById('splash').classList.add('hidden');},2000);});

const VIDEO_DARK="<?= BANNER_VIDEO_DARK ?>",VIDEO_LIGHT="<?= BANNER_VIDEO_LIGHT ?>",vidLoader=document.getElementById('vid-loader'),video=document.getElementById('hero-vid');
function updateVideo(n){
  if(!video)return;
  const src=n==='light'?VIDEO_LIGHT:VIDEO_DARK,source=video.querySelector('source');
  if(source&&source.getAttribute('src')!==src){source.setAttribute('src',src);vidLoader.classList.add('active');video.load();}
}
video.addEventListener('loadstart',()=>vidLoader.classList.add('active'));
video.addEventListener('canplay',()=>vidLoader.classList.remove('active'));
video.addEventListener('waiting',()=>vidLoader.classList.add('active'));
video.addEventListener('playing',()=>vidLoader.classList.remove('active'));

function setTheme(n){
  document.documentElement.setAttribute('data-theme',n);localStorage.setItem('nz_t',n);
  document.querySelectorAll('.to').forEach(e=>e.classList.toggle('active',e.dataset.theme===n));
  document.getElementById('td').classList.remove('open');updateVideo(n);showToast('Tema: '+n);
}
function toggleTD(){document.getElementById('td').classList.toggle('open');}
document.addEventListener('click',e=>{const d=document.getElementById('td'),b=document.getElementById('tb');if(!d.contains(e.target)&&!b.contains(e.target))d.classList.remove('open');});
(()=>{const s=localStorage.getItem('nz_t')||'dark';document.documentElement.setAttribute('data-theme',s);document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.to').forEach(e=>e.classList.toggle('active',e.dataset.theme===s));updateVideo(s);});})();

function createParticles(){
  const container=document.getElementById('particles-container');if(!container)return;
  for(let i=0;i<12;i++){const particle=document.createElement('div');particle.classList.add('particle');particle.style.cssText=`width:${Math.random()*80+30}px;height:${Math.random()*80+30}px;left:${Math.random()*100}%;bottom:-40px;animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*8}s;`;container.appendChild(particle);}
}
document.addEventListener('DOMContentLoaded',createParticles);

let apiTree=null,responseCache={},activeChipId=null,totalEndpoints=<?= $total_endpoints ?>;
function showToast(msg,type='ok'){const t=document.getElementById('toast');t.innerHTML=`<i class="fas fa-${type==='ok'?'check-circle':'exclamation-triangle'}"></i> ${msg}`;t.classList.add('show');clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('show'),2400);}
function hl(s){if(!s)return'';return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"([^"]+)"(\s*:)/g,'<span class="jk">"$1"</span>$2').replace(/:\s*"([^"]*)"/g,': <span class="js">"$1"</span>').replace(/:\s*(-?\d+\.?\d*)/g,': <span class="jn">$1</span>').replace(/:\s*(true|false)/g,': <span class="jb">$1</span>').replace(/:\s*(null)/g,': <span class="jnl">$1</span>');}
const FI={ai:'fa-robot',admin:'fa-shield-halved',cache:'fa-database',download:'fa-download',fun:'fa-gamepad',leaderboard:'fa-trophy',library:'fa-book',maker:'fa-palette',news:'fa-newspaper',random:'fa-shuffle',search:'fa-magnifying-glass',stalk:'fa-eye',tools:'fa-wrench',image:'fa-image',video:'fa-film',audio:'fa-music',text:'fa-file-lines',user:'fa-user',data:'fa-chart-bar',media:'fa-photo-film',social:'fa-share-nodes',convert:'fa-arrows-rotate',qris:'fa-qrcode',payment:'fa-credit-card',link:'fa-link',generator:'fa-bolt',weather:'fa-cloud-sun',game:'fa-dice',anime:'fa-star',quote:'fa-quote-left',sticker:'fa-face-smile'};
function getFI(n){const k=n.toLowerCase();for(const[a,b]of Object.entries(FI))if(k.includes(a))return b;return'fa-folder';}
function countAllFiles(tree){if(!tree)return 0;let count=0;if(tree.type==='file')return 1;if(tree.children)tree.children.forEach(c=>count+=countAllFiles(c));return count;}
function updateTotalBadges(){
  const count=countAllFiles(apiTree);
  if(count!==totalEndpoints){
    totalEndpoints=count;
    const navBadge=document.getElementById('total-ep-badge');if(navBadge)navBadge.querySelector('span').textContent=count;
    const heroBadge=document.getElementById('hero-badge');if(heroBadge)heroBadge.querySelector('span').textContent=count;
    const bottomCount=document.getElementById('bottom-ep-count');if(bottomCount)bottomCount.textContent=count;
  }
}

async function fetchData(){
  try{
    const r=await fetch(location.pathname+'?fetch_api_data=1&t='+Date.now());if(!r.ok)throw new Error('HTTP '+r.status);
    const raw=await r.text(),newHash=simpleHash(raw);
    if(apiTree!==null&&newHash===lastDataHash)return;
    lastDataHash=newHash;apiTree=JSON.parse(raw).tree;updateTotalBadges();buildChips();
    if(apiTree!==null)restoreUIState();
  }catch(e){document.getElementById('chips-wrap').innerHTML=`<div class="empty" style="color:var(--red)"><i class="fas fa-triangle-exclamation"></i>Error: ${e.message}</div>`;}
}

/* ─── POLLING REAL-TIME 3 DETIK ────────────────────── */
const POLL_INTERVAL_MS=3000;
let lastDataHash=null,isPolling=false,pollTimer=null;const pollingDot=document.getElementById('polling-dot');
function simpleHash(str){let hash=0;for(let i=0;i<str.length;i++){const char=str.charCodeAt(i);hash=((hash<<5)-hash)+char;hash|=0;}return hash.toString(16);}
function captureUIState(){const state={activeChipId:activeChipId,openEndpoints:[],formValues:{}};document.querySelectorAll('.ep-item.open').forEach(item=>{state.openEndpoints.push(item.id);});state.openEndpoints.forEach(epId=>{const epItem=document.getElementById(epId);if(epItem){const inputs=epItem.querySelectorAll('input:not([type="file"]), select');inputs.forEach(input=>{const key=input.id;if(input.type!=='file')state.formValues[key]=input.value;});}});return state;}
let savedUIState=null;
function restoreUIState(){
  if(!savedUIState)return;
  if(savedUIState.activeChipId){activeChipId=savedUIState.activeChipId;document.querySelectorAll('.chip').forEach(c=>c.classList.toggle('active',c.dataset.id===activeChipId));const n=apiTree.children.find(c=>c.id===activeChipId);if(n){const p=document.getElementById('ep-panel');p.innerHTML=renderCatContent(n);p.classList.remove('hidden');}}
  savedUIState.openEndpoints.forEach(epId=>{const el=document.getElementById(epId);if(el)el.classList.add('open');});
  Object.entries(savedUIState.formValues).forEach(([key,val])=>{const el=document.getElementById(key);if(el&&el.type!=='file')el.value=val;});
  savedUIState.openEndpoints.forEach(epId=>{const ep=findEP(epId.replace('ep-',''));if(ep)upUrl(ep.id);});
  savedUIState=null;
}
async function pollData(){if(isPolling)return;isPolling=true;pollingDot.classList.add('active');try{savedUIState=captureUIState();await fetchData();}catch(e){}finally{pollingDot.classList.remove('active');isPolling=false;pollTimer=setTimeout(pollData,POLL_INTERVAL_MS);}}
function startPolling(){if(pollTimer)clearTimeout(pollTimer);pollData();}
let searchTimeout;document.getElementById('srch-inp').addEventListener('input',()=>{if(pollTimer)clearTimeout(pollTimer);clearTimeout(searchTimeout);searchTimeout=setTimeout(()=>{if(!isPolling)startPolling();},1500);});
const origExecAPI=window.execAPI;window.execAPI=async function(eid){if(pollTimer)clearTimeout(pollTimer);try{await origExecAPI(eid);}finally{startPolling();}};
const origFetchData=fetchData;fetchData=async function(){await origFetchData();if(!pollTimer)startPolling();};

function buildChips(){const w=document.getElementById('chips-wrap');if(!apiTree||!apiTree.children)return;w.innerHTML=apiTree.children.filter(c=>c.type==='folder').map((c,i)=>`<div class="chip" data-id="${c.id}" onclick="selectChip('${c.id}')" style="animation:fadeUp .3s ${i*.04}s ease both"><i class="fas ${getFI(c.name)}"></i>${c.name} <span style="opacity:.55;font-size:.52rem">${countFiles(c)}</span></div>`).join('');}
function selectChip(id){const p=document.getElementById('ep-panel'),sp=document.getElementById('search-panel');sp.classList.add('hidden');if(activeChipId===id){activeChipId=null;document.querySelectorAll('.chip').forEach(c=>c.classList.remove('active'));p.classList.add('hidden');p.innerHTML='';return;}activeChipId=id;document.querySelectorAll('.chip').forEach(c=>c.classList.toggle('active',c.dataset.id===id));const n=apiTree.children.find(c=>c.id===id);if(!n)return;p.innerHTML=renderCatContent(n);p.classList.remove('hidden');}
function renderCatContent(n){let h='';const e=n.children.filter(c=>c.type==='file'),s=n.children.filter(c=>c.type==='folder');e.forEach((x,i)=>{h+=renderEP(x,i*.03);});s.forEach((x,i)=>{h+=`<div class="sub-heading" style="animation:fadeUp .28s ${(e.length+i)*.04}s ease both"><i class="fas ${getFI(x.name)}"></i>${x.name}</div>`;x.children.filter(c=>c.type==='file').forEach((y,j)=>{h+=renderEP(y,(e.length+i+j)*.03);});x.children.filter(c=>c.type==='folder').forEach(z=>{h+=`<div class="sub-heading" style="padding-left:12px;font-size:.6rem;"><i class="fas ${getFI(z.name)}"></i>${z.name}</div>`;z.children.filter(c=>c.type==='file').forEach((y,j)=>{h+=renderEP(y,j*.03);});});});return h||`<div class="empty"><i class="fas fa-inbox"></i>Tidak ada endpoint</div>`;}
function onSearch(){const q=document.getElementById('srch-inp').value.toLowerCase().trim(),p=document.getElementById('ep-panel'),sp=document.getElementById('search-panel');if(!q){sp.classList.add('hidden');sp.innerHTML='';if(activeChipId)p.classList.remove('hidden');return;}p.classList.add('hidden');const a=[];collectAll(apiTree,a);const r=a.filter(x=>x.name.toLowerCase().includes(q));sp.innerHTML=r.length?r.map((x,i)=>renderEP(x,i*.02)).join(''):`<div class="empty"><i class="fas fa-search"></i>Tidak ada hasil untuk "${q}"</div>`;sp.classList.remove('hidden');}
function collectAll(n,a){n.children?.forEach(c=>{c.type==='file'?a.push(c):collectAll(c,a);});}
function renderEP(ep,d=0){const mc=ep.method==='GET'?'g':ep.method==='POST'?'po':'d';const params=(ep.params||[]).map(p=>{if(p.type==='file')return`<div class="fg"><div class="fl"><span class="fn">${p.name}</span><span class="ft">file</span><span class="fr">*</span><span class="fh">${p.description||'Upload'}</span></div><input type="file" class="fi" id="i-${ep.id}-${p.name}"></div>`;if(p.type==='select'){const o=p.options.map(x=>`<option value="${x}">${x}</option>`).join('');return`<div class="fg"><div class="fl"><span class="fn">${p.name}</span><span class="ft">select</span><span class="fr">*</span><span class="fh">${p.description||''}</span></div><select class="fi" id="i-${ep.id}-${p.name}" onchange="upUrl('${ep.id}')"><option value="" disabled selected>-- Pilih --</option>${o}</select></div>`;}return`<div class="fg"><div class="fl"><span class="fn">${p.name}</span><span class="ft">${p.type}</span><span class="fr">*</span><span class="fh">${p.description||''}</span></div><input type="text" class="fi" id="i-${ep.id}-${p.name}" value="${p.default_value||''}" placeholder="${p.placeholder||p.name}" oninput="upUrl('${ep.id}')"></div>`;}).join('');return`<div class="ep-item" id="ep-${ep.id}" style="animation-delay:${d}s"><div class="ep-hdr" onclick="toggleEP('ep-${ep.id}')"><span class="mb ${mc}">${ep.method}</span><span class="ep-name">${ep.name}</span><i class="fas fa-chevron-down ep-chv"></i></div><div class="ep-bw"><div class="ep-b"><div class="ep-bi"><div class="ep-info"><i class="fas fa-circle-info"></i><span>${ep.description}</span></div>${params}<span class="ul">Request URL</span><div class="ub"><span class="ut" id="u-${ep.id}">${buildURL(ep)}</span><button class="icb" onclick="cpy(document.getElementById('u-${ep.id}').textContent)"><i class="fas fa-copy"></i></button></div><button class="xbtn" id="x-${ep.id}" onclick="execAPI('${ep.id}')"><i class="fas fa-bolt"></i> Execute</button><div class="rw" id="rw-${ep.id}"><div class="rh"><div class="rd" id="rd-${ep.id}"></div><span class="rs" id="rs-${ep.id}">IDLE</span><div class="rhr"><button class="icb" id="ra-${ep.id}" onclick="cpyR('${ep.id}')"><i class="fas fa-copy"></i></button></div></div><div class="rb" id="rb-${ep.id}"><pre class="rp">// Awaiting...</pre></div></div></div></div></div></div>`;}
window.toggleEP=id=>{document.getElementById(id)?.classList.toggle('open');};
function buildURL(ep,vals={}){let u=ep.url;if(!u.startsWith('http'))u=location.origin+'/<?= API_FOLDER ?>/'+u.replace(/^\/+/,'');if(ep.method==='GET'&&ep.params?.length){const p=ep.params.filter(x=>x.type!=='file').map(x=>{const v=vals[x.name]??x.default_value;return v?`${x.name}=${encodeURIComponent(v)}`:null;}).filter(Boolean);if(p.length)u+=(u.includes('?')?'&':'?')+p.join('&');}return u;}
function findEP(id){return findNode(apiTree,id);}
function findNode(n,id){if(!n)return null;if(n.type==='file'&&n.id===id)return n;for(const c of(n.children||[])){const f=findNode(c,id);if(f)return f;}return null;}
function countFiles(n){return n.type==='file'?1:(n.children||[]).reduce((s,c)=>s+countFiles(c),0);}
window.upUrl=id=>{const ep=findEP(id);if(!ep)return;const v={};(ep.params||[]).forEach(p=>{if(p.type!=='file'){const e=document.getElementById(`i-${id}-${p.name}`);if(e?.value.trim())v[p.name]=e.value.trim();}});const b=document.getElementById('u-'+id);if(b)b.textContent=buildURL(ep,v);};

window.execAPI=async function(eid){
  const ep=findEP(eid);if(!ep)return;const btn=document.getElementById('x-'+eid),rs=document.getElementById('rs-'+eid),rd=document.getElementById('rd-'+eid),rb=document.getElementById('rb-'+eid),ra=document.getElementById('ra-'+eid);
  btn.disabled=true;btn.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Executing...';rs.className='rs';rs.textContent='FETCHING';rd.className='rd ld';rb.innerHTML='<pre class="rp">// Sending...</pre>';
  if(responseCache[eid]?.url)URL.revokeObjectURL(responseCache[eid].url);delete responseCache[eid];
  const fd=new FormData(),qp=[];let cu=ep.url;if(!cu.startsWith('http'))cu=location.origin+'/<?= API_FOLDER ?>/'+cu.replace(/^\/+/,'');let hb=false;
  (ep.params||[]).forEach(p=>{const el=document.getElementById(`i-${eid}-${p.name}`);if(!el)return;if(p.type==='file'){if(el.files?.length){fd.append(p.name,el.files[0]);hb=true;}}else{const v=el.value.trim();if(v){if(ep.method==='GET')qp.push(`${p.name}=${encodeURIComponent(v)}`);else{fd.append(p.name,v);hb=true;}}}});
  let url=cu;if(ep.method==='GET'&&qp.length)url+=(url.includes('?')?'&':'?')+qp.join('&');const opts={method:ep.method};if(ep.method==='POST'&&hb)opts.body=fd;
  try{
    const resp=await fetch(url,opts),ct=(resp.headers.get('content-type')||'').toLowerCase(),isJson=ct.includes('application/json'),isMedia=ct.includes('image/')||ct.includes('video/')||ct.includes('audio/')||ct.includes('application/pdf')||ct.includes('application/zip')||ct.includes('application/octet-stream');
    if(isMedia){const blob=await resp.blob(),ou=URL.createObjectURL(blob);responseCache[eid]={type:'media',url:ou,mime:ct};rs.textContent=resp.status+' '+resp.statusText;rs.className='rs ok';rd.className='rd ok';ra.innerHTML='<i class="fas fa-download"></i>';ra.setAttribute('onclick',`dlMedia('${eid}')`);rb.style.textAlign='center';if(ct.includes('image/'))rb.innerHTML=`<img src="${ou}" style="max-width:100%;border-radius:6px;" alt="img"/>`;else if(ct.includes('video/'))rb.innerHTML=`<video src="${ou}" controls autoplay style="max-width:100%;border-radius:6px;"></video>`;else if(ct.includes('audio/'))rb.innerHTML=`<audio src="${ou}" controls style="width:100%;margin-top:6px;"></audio>`;else if(ct.includes('application/pdf'))rb.innerHTML=`<iframe src="${ou}" style="width:100%;height:280px;border:none;border-radius:6px;"></iframe>`;else rb.innerHTML=`<div style="padding:12px;text-align:left;font-family:var(--mono);font-size:.62rem;color:var(--mu2);">Binary (${ct})<br><a href="${ou}" download="response" class="bdl"><i class="fas fa-download"></i> Download</a></div>`;}
    else if(isJson){const data=await resp.json(),apiStatus=data.hasOwnProperty('status')?Boolean(data.status):true,sc=apiStatus?200:500,st=`${sc} ${apiStatus?'OK':'Internal Server Error (status: false)'}`;rs.textContent=st;rs.className='rs '+(apiStatus?'ok':'err');rd.className='rd '+(apiStatus?'ok':'err');let formatted=JSON.stringify(data,null,2);rb.innerHTML=`<pre class="rp" id="rp-${eid}">${hl(formatted)}</pre>`;ra.innerHTML='<i class="fas fa-copy"></i>';ra.setAttribute('onclick',`cpyR('${eid}')`);responseCache[eid]={type:'text',data:formatted};}
    else{const raw=await resp.text();rs.textContent=resp.status+' '+resp.statusText;rs.className='rs '+(resp.ok?'ok':'err');rd.className='rd '+(resp.ok?'ok':'err');rb.innerHTML=`<pre class="rp" id="rp-${eid}">${hl(raw)}</pre>`;ra.innerHTML='<i class="fas fa-copy"></i>';ra.setAttribute('onclick',`cpyR('${eid}')`);responseCache[eid]={type:'text',data:raw};}
  }catch(e){rs.textContent='Network Error';rs.className='rs err';rd.className='rd err';rb.innerHTML=`<pre class="rp" style="color:var(--red)">// ${e.message}</pre>`;}
  btn.disabled=false;btn.innerHTML='<i class="fas fa-bolt"></i> Execute';
};

window.cpy=t=>navigator.clipboard?.writeText(t).then(()=>showToast('Copied!')).catch(()=>showToast('Gagal','err'));
window.cpyR=id=>{const el=document.getElementById('rp-'+id);if(el)cpy(el.innerText);else showToast('Nothing to copy','err');};
window.dlMedia=function(id){const c=responseCache[id];if(!c?.url)return;const ext={};'image/jpeg,jpg;image/png,png;image/gif,gif;image/webp,webp;video/mp4,mp4;audio/mpeg,mp3;application/pdf,pdf;application/zip,zip'.split(';').forEach(s=>{const[k,v]=s.split(',');ext[k]=v;});const a=document.createElement('a');a.href=c.url;a.download=`TiyanzApi.${ext[c.mime.split(';')[0].trim()]||'bin'}`;document.body.appendChild(a);a.click();document.body.removeChild(a);showToast('Download started!');};

document.addEventListener('DOMContentLoaded',fetchData);
</script>
</body>
</html>