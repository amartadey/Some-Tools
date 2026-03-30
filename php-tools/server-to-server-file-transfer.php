<?php
// ============================================================
//  CONFIGURATION — change these before uploading
// ============================================================
define('SECRET_PASSWORD', 'YourStrongPassword123!');   // <-- CHANGE THIS
define('SOURCE_URL',      'https://drive.usercontent.google.com/download?id=1eDyWBjp7m-P7Te_6gog8J5hR6QuwDWUH&export=download&authuser=0&confirm=t&uuid=ab418124-1d67-4bfb-accd-a2f22afecf52&at=AGN2oQ3bSyOGpHGXeFHfEPn2-tei%3A1774870114627');
define('SAVE_AS',         __DIR__ . '/file.zip');       // where to save on THIS server
define('CHUNK_BYTES',     1024 * 512);                  // 512 KB per chunk
// ============================================================

session_start();

// ---------- handle AJAX progress-poll request ----------
if (isset($_GET['action']) && $_GET['action'] === 'progress') {
    header('Content-Type: application/json');
    $prog = $_SESSION['dl_progress'] ?? ['status' => 'idle'];
    echo json_encode($prog);
    exit;
}

// ---------- handle AJAX start-download request ----------
if (isset($_GET['action']) && $_GET['action'] === 'start') {
    header('Content-Type: application/json');

    if (!isset($_POST['password']) || $_POST['password'] !== SECRET_PASSWORD) {
        echo json_encode(['ok' => false, 'error' => 'Wrong password.']);
        exit;
    }

    // Mark session so the download script knows it's authorised
    $_SESSION['dl_auth']     = true;
    $_SESSION['dl_progress'] = ['status' => 'starting', 'bytes' => 0, 'total' => 0, 'pct' => 0];
    echo json_encode(['ok' => true]);
    exit;
}

// ---------- handle AJAX run-download request (streams via ignore_user_abort) ----------
if (isset($_GET['action']) && $_GET['action'] === 'run') {
    // Must be authenticated
    if (empty($_SESSION['dl_auth'])) {
        exit('Unauthorised');
    }

    ignore_user_abort(true);
    set_time_limit(0);
    ini_set('memory_limit', '128M');

    $update = function(array $data) {
        $_SESSION['dl_progress'] = $data;
        session_write_close();
        session_start();
    };

    // --- open remote file ---
    $ctx = stream_context_create([
        'http' => ['timeout' => 30],
        'ssl'  => ['verify_peer' => false],
    ]);

    $remote = @fopen(SOURCE_URL, 'rb', false, $ctx);
    if (!$remote) {
        $update(['status' => 'error', 'message' => 'Could not open remote URL.']);
        exit;
    }

    // Try to get Content-Length from headers
    $meta  = stream_get_meta_data($remote);
    $total = 0;
    foreach ($meta['wrapper_data'] ?? [] as $h) {
        if (stripos($h, 'Content-Length:') === 0) {
            $total = (int) trim(substr($h, 15));
        }
    }

    // --- open local file ---
    $local = @fopen(SAVE_AS, 'wb');
    if (!$local) {
        fclose($remote);
        $update(['status' => 'error', 'message' => 'Cannot write to destination: ' . SAVE_AS]);
        exit;
    }

    $downloaded = 0;
    $start      = time();

    while (!feof($remote)) {
        $chunk       = fread($remote, CHUNK_BYTES);
        if ($chunk === false) break;
        fwrite($local, $chunk);
        $downloaded += strlen($chunk);

        $pct     = $total > 0 ? round($downloaded / $total * 100, 1) : 0;
        $elapsed = max(1, time() - $start);
        $speed   = $downloaded / $elapsed; // bytes/sec

        $update([
            'status'   => 'downloading',
            'bytes'    => $downloaded,
            'total'    => $total,
            'pct'      => $pct,
            'speed'    => $speed,
            'elapsed'  => $elapsed,
        ]);
    }

    fclose($remote);
    fclose($local);

    $update([
        'status'  => 'done',
        'bytes'   => $downloaded,
        'total'   => $total,
        'pct'     => 100,
        'elapsed' => max(1, time() - $start),
    ]);

    unset($_SESSION['dl_auth']);
    exit;
}

// ---------- default: serve the HTML page ----------
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Server File Transfer</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Space+Grotesk:wght@300;500;700&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:       #0a0d14;
    --surface:  #111622;
    --border:   #1e2a42;
    --accent:   #00e5ff;
    --accent2:  #7b61ff;
    --green:    #00ff94;
    --red:      #ff4d6a;
    --yellow:   #ffd166;
    --text:     #c8d6f0;
    --muted:    #4a5878;
    --mono:     'IBM Plex Mono', monospace;
    --sans:     'Space Grotesk', sans-serif;
  }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background-image:
      radial-gradient(ellipse 80% 50% at 50% -10%, rgba(0,229,255,.07) 0%, transparent 70%),
      repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(255,255,255,.02) 39px, rgba(255,255,255,.02) 40px),
      repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(255,255,255,.02) 39px, rgba(255,255,255,.02) 40px);
  }

  .card {
    width: 100%;
    max-width: 680px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 0 60px rgba(0,229,255,.06), 0 24px 60px rgba(0,0,0,.5);
  }

  /* ---- header ---- */
  .card-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(0,229,255,.04), rgba(123,97,255,.04));
  }
  .header-icon {
    width: 40px; height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
  }
  .card-header h1 { font-size: 1.1rem; font-weight: 700; letter-spacing: .02em; }
  .card-header p  { font-size: .78rem; color: var(--muted); font-family: var(--mono); margin-top:.15rem; }

  /* ---- body ---- */
  .card-body { padding: 2rem; }

  /* ---- form ---- */
  .auth-form { display: flex; flex-direction: column; gap: 1rem; }
  .field label { display: block; font-size: .78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .4rem; }
  .field input[type=password] {
    width: 100%;
    padding: .75rem 1rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: var(--mono);
    font-size: .95rem;
    outline: none;
    transition: border-color .2s;
  }
  .field input[type=password]:focus { border-color: var(--accent); }

  .btn {
    padding: .8rem 1.5rem;
    border: none; border-radius: 8px; cursor: pointer;
    font-family: var(--sans); font-size: .9rem; font-weight: 600;
    transition: opacity .2s, transform .1s;
  }
  .btn:active { transform: scale(.98); }
  .btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #0a0d14;
  }
  .btn:disabled { opacity: .4; cursor: not-allowed; }

  .error-msg {
    background: rgba(255,77,106,.1);
    border: 1px solid rgba(255,77,106,.3);
    border-radius: 6px;
    padding: .6rem 1rem;
    font-size: .82rem;
    color: var(--red);
    display: none;
  }

  /* ---- progress panel ---- */
  #progress-panel { display: none; }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .75rem;
    margin-bottom: 1.5rem;
  }
  .info-cell {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: .75rem 1rem;
  }
  .info-cell .label { font-size: .7rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; }
  .info-cell .value { font-family: var(--mono); font-size: 1rem; font-weight: 600; color: var(--accent); margin-top: .25rem; }

  /* progress bar */
  .pbar-wrap {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 100px;
    height: 18px;
    overflow: hidden;
    margin-bottom: .5rem;
    position: relative;
  }
  .pbar-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    border-radius: 100px;
    transition: width .4s linear;
    position: relative;
  }
  .pbar-fill::after {
    content: '';
    position: absolute; right: 0; top: 0; bottom: 0; width: 40px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.25));
    animation: shimmer 1.2s infinite;
  }
  @keyframes shimmer { 0%,100%{opacity:1} 50%{opacity:.4} }
  .pbar-pct {
    text-align: right;
    font-family: var(--mono);
    font-size: .85rem;
    color: var(--accent);
    margin-bottom: 1.5rem;
  }

  /* status badge */
  .status-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .3rem .75rem;
    border-radius: 100px;
    font-size: .75rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .07em;
    margin-bottom: 1.5rem;
  }
  .status-badge.downloading { background: rgba(0,229,255,.1); color: var(--accent); border: 1px solid rgba(0,229,255,.2); }
  .status-badge.done        { background: rgba(0,255,148,.1); color: var(--green); border: 1px solid rgba(0,255,148,.2); }
  .status-badge.error       { background: rgba(255,77,106,.1); color: var(--red);  border: 1px solid rgba(255,77,106,.2); }
  .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: pulse 1s infinite; }
  .done .dot, .error .dot { animation: none; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

  /* ---- console ---- */
  .console-label {
    font-size: .72rem; text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted); margin-bottom: .5rem;
  }
  #console {
    background: #060810;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1rem;
    font-family: var(--mono);
    font-size: .78rem;
    line-height: 1.6;
    max-height: 220px;
    overflow-y: auto;
    color: #8fa5cc;
  }
  #console .line { margin: 0; padding: 0; }
  #console .ts   { color: var(--muted); }
  #console .info { color: #8fa5cc; }
  #console .ok   { color: var(--green); }
  #console .warn { color: var(--yellow); }
  #console .err  { color: var(--red); }
  #console .hi   { color: var(--accent); }
</style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <div class="header-icon">⬇</div>
    <div>
      <h1>Server-to-Server Transfer</h1>
      <p>successlifecreation.com / file receiver</p>
    </div>
  </div>

  <div class="card-body">

    <!-- AUTH FORM -->
    <div id="auth-panel">
      <div class="auth-form">
        <div class="field">
          <label>Access Password</label>
          <input type="password" id="pwd" placeholder="Enter password…" autocomplete="current-password">
        </div>
        <div class="error-msg" id="err-msg">Incorrect password. Please try again.</div>
        <button class="btn btn-primary" id="start-btn" onclick="startDownload()">
          Initiate Transfer
        </button>
      </div>
    </div>

    <!-- PROGRESS PANEL -->
    <div id="progress-panel">
      <div class="status-badge downloading" id="status-badge">
        <span class="dot"></span>
        <span id="status-text">Connecting…</span>
      </div>

      <div class="info-grid">
        <div class="info-cell">
          <div class="label">Downloaded</div>
          <div class="value" id="inf-bytes">—</div>
        </div>
        <div class="info-cell">
          <div class="label">Total Size</div>
          <div class="value" id="inf-total">—</div>
        </div>
        <div class="info-cell">
          <div class="label">Speed</div>
          <div class="value" id="inf-speed">—</div>
        </div>
      </div>

      <div class="pbar-wrap"><div class="pbar-fill" id="pbar"></div></div>
      <div class="pbar-pct" id="pbar-pct">0%</div>

      <div class="console-label">Transfer Log</div>
      <div id="console"></div>
    </div>

  </div><!-- /card-body -->
</div><!-- /card -->

<script>
// ─── helpers ───────────────────────────────────────────────
function fmt(bytes) {
  if (!bytes || bytes < 0) return '—';
  const u = ['B','KB','MB','GB'];
  let i = 0, v = bytes;
  while (v >= 1024 && i < 3) { v /= 1024; i++; }
  return v.toFixed(i>1?2:0) + ' ' + u[i];
}
function fmtSpeed(bps) {
  return fmt(bps) + '/s';
}
function ts() {
  return new Date().toLocaleTimeString('en-US',{hour12:false});
}

const $console = document.getElementById('console');
function log(cls, msg) {
  const p = document.createElement('p');
  p.className = 'line';
  p.innerHTML = `<span class="ts">[${ts()}]</span> <span class="${cls}">${msg}</span>`;
  $console.appendChild(p);
  $console.scrollTop = $console.scrollHeight;
  // also mirror to browser console
  const fn = cls === 'err' ? console.error : cls === 'warn' ? console.warn : console.log;
  fn(`[${ts()}] ${msg}`);
}

// ─── start download ────────────────────────────────────────
let pollTimer = null;

async function startDownload() {
  const pwd = document.getElementById('pwd').value;
  if (!pwd) return;

  const btn = document.getElementById('start-btn');
  btn.disabled = true;

  // 1) authenticate + arm the session
  const fd = new FormData();
  fd.append('password', pwd);
  const r = await fetch('?action=start', { method: 'POST', body: fd });
  const j = await r.json();

  if (!j.ok) {
    document.getElementById('err-msg').style.display = 'block';
    btn.disabled = false;
    return;
  }

  // 2) show progress panel
  document.getElementById('auth-panel').style.display  = 'none';
  document.getElementById('progress-panel').style.display = 'block';

  log('hi', 'Authentication successful.');
  log('info', 'Source  → <?= htmlspecialchars(SOURCE_URL) ?>');
  log('info', 'Dest    → <?= htmlspecialchars(SAVE_AS) ?>');
  log('info', 'Initiating transfer…');

  // 3) kick off the actual download in the background (fire & forget)
  fetch('?action=run').catch(() => {});   // response doesn't matter — we poll

  // 4) start polling for progress
  pollTimer = setInterval(pollProgress, 800);
}

// ─── poll for progress ─────────────────────────────────────
let lastBytes = 0;

async function pollProgress() {
  let data;
  try {
    const r = await fetch('?action=progress');
    data = await r.json();
  } catch(e) {
    log('warn', 'Poll failed — retrying…');
    return;
  }

  const { status, bytes, total, pct, speed, elapsed, message } = data;

  // update bar
  if (pct >= 0) {
    document.getElementById('pbar').style.width = pct + '%';
    document.getElementById('pbar-pct').textContent = pct + '%';
  }

  // update info cells
  document.getElementById('inf-bytes').textContent = fmt(bytes);
  document.getElementById('inf-total').textContent = total > 0 ? fmt(total) : '—';
  document.getElementById('inf-speed').textContent = speed > 0 ? fmtSpeed(speed) : '—';

  // console log every ~5 % or state change
  if (bytes - lastBytes > 1024 * 1024 * 10) {   // every ~10 MB
    log('info', `Progress: ${pct}% | ${fmt(bytes)} of ${fmt(total)} | ${fmtSpeed(speed)}`);
    lastBytes = bytes;
  }

  const badge = document.getElementById('status-badge');
  const stxt  = document.getElementById('status-text');

  if (status === 'done') {
    clearInterval(pollTimer);
    document.getElementById('pbar').style.width = '100%';
    document.getElementById('pbar-pct').textContent = '100%';
    badge.className = 'status-badge done';
    stxt.textContent = 'Transfer Complete';
    log('ok', `✔ File saved successfully! Total: ${fmt(bytes)} | Time: ${elapsed}s`);
    console.info('%c✔ Transfer complete!', 'color:#00ff94;font-weight:bold;font-size:14px');
  } else if (status === 'error') {
    clearInterval(pollTimer);
    badge.className = 'status-badge error';
    stxt.textContent = 'Error';
    log('err', 'Transfer failed: ' + (message || 'Unknown error'));
    console.error('Transfer failed:', message);
  } else if (status === 'downloading') {
    stxt.textContent = `Downloading… ${pct}%`;
  } else if (status === 'starting') {
    stxt.textContent = 'Starting…';
  }
}

// also allow pressing Enter in the password field
document.getElementById('pwd').addEventListener('keydown', e => {
  if (e.key === 'Enter') startDownload();
});
</script>
</body>
</html>
