<?php
error_reporting(0);
ini_set('display_errors', '0');
$BASE = 'https://videystream.vip';
$sort = $_GET['sort'] ?? 'terbaru';
$page = max(1, (int)($_GET['page'] ?? 1));

function grab($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return $res;
}

// ========== DOWNLOAD MODE (AJAX) ==========
if (isset($_GET['ajax']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = $_GET['id'];
    $html = grab($BASE . '/watch.php?id=' . $id);
    $videoUrl = '';
    
    preg_match_all('/src=["\']([^"\']*\.(?:mp4|m3u8|ts|webm))["\']/i', $html, $m);
    $videoUrl = $m[1][0] ?? '';
    
    if (empty($videoUrl)) {
        preg_match_all('/["\'](https?:\/\/[^"\']*\.(?:mp4|m3u8|ts|webm))["\']/i', $html, $m);
        $videoUrl = $m[1][0] ?? '';
    }

    // Cari di iframe
    if (empty($videoUrl)) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $source = $xpath->query('//video/source')->item(0);
        if ($source) $videoUrl = $source->getAttribute('src') ?: '';
        if (empty($videoUrl)) {
            $iframe = $xpath->query('//iframe')->item(0);
            if ($iframe) {
                $iframeSrc = $iframe->getAttribute('src') ?: '';
                if (!empty($iframeSrc)) {
                    $iframeHtml = grab($iframeSrc);
                    preg_match_all('/["\'](https?:\/\/[^"\']*\.(?:mp4|m3u8|ts|webm))["\']/i', $iframeHtml, $m);
                    $videoUrl = $m[1][0] ?? '';
                }
            }
        }
    }
    
    if (!empty($videoUrl) && !preg_match('/^https?:\/\//', $videoUrl)) {
        $videoUrl = $BASE . '/' . ltrim($videoUrl, '/');
    }

    echo json_encode(['url' => $videoUrl]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= $_GET['theme'] ?? 'dark' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tiyanz Video Archive</title>
<style>
[data-theme="dark"]{--bg:#0a0a14;--bg2:#12121f;--card:#1a1a2e;--card2:#222240;--b1:rgba(124,58,237,.15);--b2:rgba(124,58,237,.3);--p:#7c3aed;--p2:#9061f9;--tx:#e8e8f0;--tx2:#a0a0c0;--mu:#555}
[data-theme="light"]{--bg:#f5f5ff;--bg2:#eee;--card:#fff;--card2:#f0f0ff;--b1:rgba(109,40,217,.1);--b2:rgba(109,40,217,.2);--p:#6d28d9;--p2:#7c3aed;--tx:#1a1a2e;--tx2:#555;--mu:#999}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--tx);font-family:system-ui,sans-serif;min-height:100vh}
.container{max-width:1000px;margin:0 auto;padding:16px}
.header{display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid var(--b1);margin-bottom:20px;flex-wrap:wrap;gap:12px}
.logo{font-size:1.5rem;font-weight:900;color:var(--p2);letter-spacing:2px}.logo span{color:#a78bfa}
.controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
select,input[type=number]{padding:8px 12px;border-radius:8px;border:1px solid var(--b2);background:var(--card);color:var(--tx);font-size:.8rem;outline:none}
select:focus,input:focus{border-color:var(--p2)}input[type=number]{width:70px}
.go-btn{padding:8px 14px;border-radius:8px;border:none;background:var(--p);color:#fff;cursor:pointer;font-size:.8rem;font-weight:600}
.go-btn:hover{background:var(--p2)}
.t-btn{padding:8px 12px;border-radius:8px;border:1px solid var(--b2);background:var(--card2);color:var(--tx2);text-decoration:none;font-size:.8rem}
.player-wrap{display:none;margin-bottom:20px}
.player-wrap.show{display:block}
.vid-box{background:#000;border-radius:16px;overflow:hidden;aspect-ratio:16/9;position:relative}
.vid-box video{width:100%;height:100%}
.vid-box .loader{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);color:#fff;font-size:1rem}
.p-info{display:flex;align-items:center;justify-content:space-between;padding:12px 0;flex-wrap:wrap;gap:8px}
.p-title{font-size:1.1rem;font-weight:700}
.btn-row{display:flex;gap:8px}
.back-btn,.dl-btn{padding:8px 16px;border-radius:8px;border:1px solid var(--b2);background:var(--card);color:var(--tx2);cursor:pointer;font-size:.8rem;text-decoration:none}
.dl-btn{background:var(--p);color:#fff;border:none;display:none}.dl-btn:hover{background:var(--p2)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
.card{background:var(--card);border:1px solid var(--b1);border-radius:12px;overflow:hidden;cursor:pointer;transition:all .2s}
.card:hover{border-color:var(--p2);transform:translateY(-2px)}
.thumb{aspect-ratio:16/9;background:var(--bg2);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.thumb img{width:100%;height:100%;object-fit:cover}
.thumb .no-img{font-size:2.5rem;color:var(--mu);position:absolute}
.info{padding:10px 12px}
.info .t{font-size:.85rem;font-weight:600;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.info .m{font-size:.7rem;color:var(--mu);margin-top:4px;display:flex;gap:8px}
.pages{display:flex;justify-content:center;gap:8px;margin-top:24px}
.p-btn{padding:8px 14px;border-radius:8px;border:1px solid var(--b2);background:var(--card);color:var(--tx2);text-decoration:none;font-size:.8rem}
.p-btn.active{background:var(--p);color:#fff;border-color:var(--p2)}
footer{text-align:center;padding:24px;font-size:.75rem;color:var(--mu)}
.empty{text-align:center;padding:40px;color:var(--mu)}
@media(max-width:600px){.controls{width:100%}.grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo">TIYANZ<span>VIDEO</span></div>
    <div class="controls">
      <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <select name="sort" onchange="this.form.submit()">
          <option value="terbaru" <?= $sort=='terbaru'?'selected':'' ?>>Terbaru</option>
          <option value="trending" <?= $sort=='trending'?'selected':'' ?>>Trending</option>
          <option value="lama" <?= $sort=='lama'?'selected':'' ?>>Terlama</option>
        </select>
        <input type="number" name="page" value="<?= $page ?>" min="1" placeholder="Halaman">
        <button type="submit" class="go-btn">Go</button>
      </form>
      <a href="?sort=<?= $sort ?>&page=<?= $page ?>&theme=<?= ($_GET['theme']??'dark')=='dark'?'light':'dark' ?>" class="t-btn">&#9681;</a>
    </div>
  </div>

  <div class="player-wrap" id="playerWrap">
    <div class="vid-box">
      <div class="loader" id="loader">Loading...</div>
      <video id="vid" controls autoplay style="display:none"></video>
    </div>
    <div class="p-info">
      <span class="p-title" id="pTitle"></span>
      <div class="btn-row">
        <a href="#" class="dl-btn" id="dlBtn" target="_blank">Download</a>
        <button class="back-btn" onclick="closePlayer()">&#8592; Kembali</button>
      </div>
    </div>
  </div>

  <div class="grid" id="grid"></div>
  <div class="empty" id="loading">Loading...</div>
  <div class="pages" id="pages"></div>
  <footer>TIYANZ VIDEO ARCHIVE &copy; 2026</footer>
</div>

<script>
const API = 'list.php';
const BASE = 'https://videystream.vip';
let sort = '<?= $sort ?>', page = <?= $page ?>;

function fixUrl(path) {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  return BASE + '/' + path.replace(/^\//, '');
}

function goPage(p){
  page = p;
  document.querySelector('[name="page"]').value = page;
  loadList();
  window.scrollTo({top:0,behavior:'smooth'});
}

async function loadList(){
  document.getElementById('grid').innerHTML = '';
  document.getElementById('loading').style.display = 'block';

  const res = await fetch(`${API}?sort=${sort}&page=${page}`);
  const data = await res.json();
  const videos = data.result?.videos || [];
  document.getElementById('loading').style.display = videos.length ? 'none' : 'block';

  const grid = document.getElementById('grid');
  videos.forEach(v => {
    const thumb = fixUrl(v.thumbnail);
    const card = document.createElement('div');
    card.className = 'card';
    card.onclick = () => playVideo(v.id, v.title);
    card.innerHTML = `
      <div class="thumb">
        ${thumb ? `<img src="${thumb}" alt="${v.title}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">` : ''}
        <span class="no-img" style="${thumb ? 'display:none' : 'display:flex'}">▶</span>
      </div>
      <div class="info">
        <div class="t">${v.title || 'No Title'}</div>
        <div class="m">
          <span>${v.views ? v.views.toLocaleString() : ''}</span>
          <span>${v.duration || ''}</span>
        </div>
      </div>`;
    grid.appendChild(card);
  });

  const pDiv = document.getElementById('pages');
  pDiv.innerHTML = '';
  if (page > 1) {
    const prev = document.createElement('a');
    prev.className = 'p-btn'; prev.textContent = '←'; prev.href = 'javascript:void(0)';
    prev.onclick = () => goPage(page-1);
    pDiv.appendChild(prev);
  }
  for (let i = Math.max(1, page-2); i <= page+2; i++) {
    const btn = document.createElement('a');
    btn.className = 'p-btn' + (i === page ? ' active' : '');
    btn.textContent = i; btn.href = 'javascript:void(0)';
    btn.onclick = () => goPage(i);
    pDiv.appendChild(btn);
  }
  const next = document.createElement('a');
  next.className = 'p-btn'; next.textContent = '→'; next.href = 'javascript:void(0)';
  next.onclick = () => goPage(page+1);
  pDiv.appendChild(next);
}

async function playVideo(id, title){
  const pw = document.getElementById('playerWrap');
  const vid = document.getElementById('vid');
  const loader = document.getElementById('loader');
  const dlBtn = document.getElementById('dlBtn');

  pw.classList.add('show');
  document.getElementById('pTitle').textContent = title;
  vid.style.display = 'none';
  loader.style.display = 'flex';
  dlBtn.style.display = 'none';
  window.scrollTo({top:0,behavior:'smooth'});

  // Fetch dari index.php sendiri (mode ajax)
  const res = await fetch(`?ajax=1&id=${id}`);
  const data = await res.json();
  loader.style.display = 'none';

  if (data.url) {
    vid.src = data.url;
    vid.style.display = 'block';
    vid.play();
    dlBtn.href = data.url;
    dlBtn.style.display = 'inline-block';
  } else {
    loader.textContent = 'Video tidak ditemukan';
  }
}

function closePlayer(){
  const pw = document.getElementById('playerWrap');
  const vid = document.getElementById('vid');
  pw.classList.remove('show');
  vid.pause(); vid.src = '';
}

loadList();
</script>
</body>
</html>