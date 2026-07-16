<?php
/**
 * Newsletter-Verwaltung – Vertrag: docs/api-contract.md
 * Session-Login, Abonnentenliste, CSV-Export, Löschen,
 * Versand an alle Bestätigten.
 */

declare(strict_types=1);

const ZIEL_MAIL = 'kontakt@kulturort-admiralbruecke.de';
const NL_DIR    = '/home/syso/kulturort-newsletter';
const NL_DB     = NL_DIR . '/newsletter.sqlite';
const NL_CONFIG = NL_DIR . '/config.php';
const HOSTS     = ['syso.uber.space', 'kulturort-admiralbruecke.de', 'www.kulturort-admiralbruecke.de'];
const IDLE_TIMEOUT_S   = 4 * 3600;
const SPERRE_FENSTER_S = 15 * 60;
const SPERRE_VERSUCHE  = 8;

if (!is_file(NL_CONFIG)) {
    http_response_code(503);
    exit('Verwaltung nicht eingerichtet (config.php fehlt).');
}
$cfg = require NL_CONFIG;

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function slug(string $s, int $max = 40): string {
    $s = mb_strtolower($s);
    $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $s = (string)preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(mb_substr($s, 0, $max), '-');
}

/** Link um Matomo-Kampagnenparameter ergänzen (vor einem #Fragment). */
function kampagnen_link(string $url, string $kw): string {
    $fragment = '';
    $raute = strpos($url, '#');
    if ($raute !== false) {
        $fragment = substr($url, $raute);
        $url = substr($url, 0, $raute);
    }
    $sep = str_contains($url, '?') ? '&' : '?';
    return $url . $sep . 'mtm_campaign=newsletter&mtm_kwd=' . rawurlencode($kw) . $fragment;
}

/** Alle Links auf eigene Hosts im Text mit Kampagnenparametern versehen. */
function text_mit_kampagne(string $text, string $kw): string {
    return (string)preg_replace_callback(
        '~https://(?:www\.)?(?:kulturort-admiralbruecke\.de|syso\.uber\.space)[^\s<>"]*~',
        function (array $m) use ($kw): string {
            $url  = rtrim($m[0], '.,;:)!?»');
            $rest = substr($m[0], strlen($url));
            return kampagnen_link($url, $kw) . $rest;
        },
        $text
    );
}

function basis_url(): string {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (!in_array($host, HOSTS, true)) {
        $host = HOSTS[0];
    }
    return 'https://' . $host;
}

$pdo = new PDO('sqlite:' . NL_DB, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE IF NOT EXISTS login_versuche (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_hash TEXT NOT NULL,
    ts INTEGER NOT NULL
)');

// ---- Session ------------------------------------------------------------------

session_name('kulturort_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

$ip_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . (string)$cfg['secret']);

$angemeldet = ($_SESSION['angemeldet'] ?? false) === true;
if ($angemeldet && time() - (int)($_SESSION['letzte_aktion'] ?? 0) > IDLE_TIMEOUT_S) {
    session_unset();
    session_destroy();
    $angemeldet = false;
}
if ($angemeldet) {
    $_SESSION['letzte_aktion'] = time();
}

$login_fehler = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && (string)($_POST['aktion'] ?? '') === 'login' && !$angemeldet) {

    $pdo->prepare('DELETE FROM login_versuche WHERE ts < ?')
        ->execute([time() - SPERRE_FENSTER_S]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_versuche WHERE ip_hash = ?');
    $stmt->execute([$ip_hash]);

    if ((int)$stmt->fetchColumn() >= SPERRE_VERSUCHE) {
        $login_fehler = 'Zu viele Fehlversuche – bitte in einer Viertelstunde noch einmal.';
    } elseif (password_verify((string)($_POST['passwort'] ?? ''), (string)$cfg['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['angemeldet']    = true;
        $_SESSION['letzte_aktion'] = time();
        $_SESSION['csrf']          = bin2hex(random_bytes(32));
        $pdo->prepare('DELETE FROM login_versuche WHERE ip_hash = ?')->execute([$ip_hash]);
        header('Location: verwaltung', true, 303);
        exit;
    } else {
        $pdo->prepare('INSERT INTO login_versuche (ip_hash, ts) VALUES (?, ?)')
            ->execute([$ip_hash, time()]);
        $login_fehler = 'Falsches Passwort.';
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && (string)($_POST['aktion'] ?? '') === 'logout' && $angemeldet) {
    session_unset();
    session_destroy();
    header('Location: verwaltung', true, 303);
    exit;
}

// ---- Loginformular ----------------------------------------------------------------

if (!$angemeldet) {
    http_response_code(200);
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Anmeldung – Kulturort Admiralbrücke</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: "Space Grotesk", "Helvetica Neue", sans-serif;
    background: #0b0b0f; color: #f2f1f4;
    min-height: 100svh;
    display: grid; place-items: center;
    padding: 1.5rem;
  }
  .karte {
    width: 100%; max-width: 24rem;
    border: 1px solid #26252e; background: #131318;
    padding: 2.4rem 2.2rem 2.2rem;
  }
  .marke {
    font-family: "IBM Plex Mono", monospace;
    font-size: 0.68rem; letter-spacing: 0.2em; text-transform: uppercase;
    color: #9a98a3; margin-bottom: 1.2rem;
  }
  h1 {
    font-family: "Bricolage Grotesque", sans-serif; font-weight: 800;
    font-size: 1.9rem; text-transform: uppercase; line-height: 1;
    margin-bottom: 0.4rem;
  }
  h1 em { font-style: normal; color: #d9ff4b; }
  .hinweis { font-size: 0.88rem; color: #9a98a3; margin-bottom: 1.8rem; }
  label {
    display: block; font-family: "IBM Plex Mono", monospace;
    font-size: 0.66rem; letter-spacing: 0.14em; text-transform: uppercase;
    color: #9a98a3; margin-bottom: 0.45rem;
  }
  input[type="password"] {
    width: 100%; font: inherit; font-size: 1.05rem; color: #f2f1f4;
    background: #0b0b0f; border: 1px solid #26252e; padding: 0.75em 0.9em;
  }
  input[type="password"]:focus-visible { outline: 2px solid #d9ff4b; border-color: #d9ff4b; }
  button {
    width: 100%; margin-top: 1.2rem; cursor: pointer;
    font-family: "Bricolage Grotesque", sans-serif; font-weight: 800;
    font-size: 1rem; text-transform: uppercase; letter-spacing: 0.04em;
    color: #0b0b0f; background: #d9ff4b; border: 0; padding: 0.85em;
  }
  button:hover { background: #8b5cf6; color: #f2f1f4; }
  .fehler {
    border: 1px solid #ff7a6e; color: #ff7a6e;
    font-size: 0.9rem; padding: 0.7rem 0.9rem; margin-bottom: 1.2rem;
  }
  .zurueck { margin-top: 1.6rem; font-size: 0.82rem; }
  .zurueck a { color: #a78bfa; }
</style>
</head>
<body>
  <main class="karte">
    <p class="marke">Kulturort Admiralbrücke</p>
    <h1>Verwal<em>tung</em></h1>
    <p class="hinweis">Interner Bereich der D-Jam-Gemeinschaft.</p>
    <?php if ($login_fehler !== ''): ?>
      <p class="fehler"><?= e($login_fehler) ?></p>
    <?php endif; ?>
    <form method="post" action="verwaltung">
      <input type="hidden" name="aktion" value="login">
      <label for="passwort">Passwort</label>
      <input type="password" id="passwort" name="passwort"
             autocomplete="current-password" autofocus required>
      <button type="submit">Anmelden</button>
    </form>
    <p class="zurueck"><a href="/">Zurück zur Seite</a></p>
  </main>
</body>
</html>
    <?php
    exit;
}

// ---- Ab hier: angemeldet ------------------------------------------------------------

$csrf = (string)$_SESSION['csrf'];

function csrf_pruefen(string $csrf): void {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Ungültiges Formular-Token, bitte Seite neu laden.');
    }
}

$meldung = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $aktion = (string)($_POST['aktion'] ?? '');

    if ($aktion === 'loeschen') {
        csrf_pruefen($csrf);
        $stmt = $pdo->prepare('DELETE FROM abonnenten WHERE email = ?');
        $stmt->execute([(string)($_POST['email'] ?? '')]);
        $meldung = $stmt->rowCount() > 0 ? 'Adresse gelöscht.' : 'Adresse nicht gefunden.';
    }

    if ($aktion === 'senden') {
        csrf_pruefen($csrf);
        $betreff = trim((string)($_POST['betreff'] ?? ''));
        $text    = trim((string)($_POST['text'] ?? ''));
        if ($betreff === '' || $text === '' || !isset($_POST['sicher'])) {
            $meldung = 'Versand abgebrochen: Betreff, Text und Bestätigungshaken sind nötig.';
        } else {
            $empfaenger = $pdo->query('SELECT email, token FROM abonnenten WHERE status = "confirmed"')
                              ->fetchAll(PDO::FETCH_ASSOC);
            $kampagne = gmdate('Y-m-d') . '-' . slug($betreff);
            $text_getaggt = text_mit_kampagne($text, $kampagne);
            $gesendet = 0;
            foreach ($empfaenger as $abo) {
                $abmelden = basis_url() . '/newsletter/abmelden/' . $abo['token'];
                $body = $text_getaggt . "\n\n--\n"
                      . "Kulturort Admiralbrücke · dienstags auf der Brücke\n"
                      . "Zur Seite: " . kampagnen_link(basis_url() . '/', $kampagne) . "\n"
                      . "Abmelden: " . $abmelden . "\n";
                $header = "From: " . ZIEL_MAIL . "\r\n"
                        . "Content-Type: text/plain; charset=UTF-8\r\n"
                        . "Content-Transfer-Encoding: 8bit\r\n"
                        . "List-Unsubscribe: <" . $abmelden . ">\r\n";
                $betreff_kodiert = '=?UTF-8?B?' . base64_encode($betreff) . '?=';
                if (@mail((string)$abo['email'], $betreff_kodiert, $body, $header)) {
                    $gesendet++;
                }
                usleep(150000); // Versand entzerren
            }
            $pdo->prepare('INSERT INTO versand (ts, betreff, empfaenger) VALUES (?, ?, ?)')
                ->execute([gmdate('c'), $betreff, $gesendet]);
            $gesamt = count($empfaenger);
            $meldung = "Newsletter an $gesendet von $gesamt Adresse(n) gesendet.";
            if ($gesendet < $gesamt) {
                $meldung .= ' Achtung: Ein Teil wurde vom Mailserver abgelehnt –'
                          . ' vermutlich das Uberspace-Limit (Standard: 5 Mails/Stunde).'
                          . ' Später erneut senden oder bei hallo@uberspace.de eine'
                          . ' Erhöhung erbitten.';
            }
        }
    }
}

// ---- CSV-Export ----------------------------------------------------------------

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="newsletter-abonnenten.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'sprache', 'angemeldet_seit'], ',', '"', '\\');
    $stmt = $pdo->query('SELECT email, sprache, ts_confirm FROM abonnenten
                         WHERE status = "confirmed" ORDER BY ts_confirm');
    foreach ($stmt as $zeile) {
        fputcsv($out, [$zeile['email'], $zeile['sprache'], $zeile['ts_confirm']], ',', '"', '\\');
    }
    exit;
}

// ---- Daten für die Anzeige --------------------------------------------------------

$statistik = $pdo->query('SELECT status, COUNT(*) AS anzahl FROM abonnenten GROUP BY status')
                 ->fetchAll(PDO::FETCH_KEY_PAIR);
$abonnenten = $pdo->query('SELECT email, status, sprache, ts_signup, ts_confirm
                           FROM abonnenten ORDER BY ts_signup DESC LIMIT 500')
                  ->fetchAll(PDO::FETCH_ASSOC);
$historie = $pdo->query('SELECT ts, betreff, empfaenger FROM versand ORDER BY id DESC LIMIT 20')
                ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Newsletter-Verwaltung – Kulturort Admiralbrücke</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: "Space Grotesk", "Helvetica Neue", sans-serif;
    background: #0b0b0f; color: #f2f1f4;
    padding: 2.5rem clamp(1rem, 4vw, 4rem);
    line-height: 1.6;
  }
  .kopfzeile { display: flex; justify-content: space-between; align-items: baseline;
               flex-wrap: wrap; gap: 1rem; }
  h1 { font-size: 1.6rem; text-transform: uppercase; margin-bottom: 0.3rem; }
  h1 em { font-style: normal; color: #d9ff4b; }
  h2 { font-size: 1.05rem; text-transform: uppercase; letter-spacing: 0.04em;
       margin: 2.5rem 0 0.9rem; color: #a78bfa; }
  .hinweis { color: #9a98a3; font-size: 0.9rem; }
  .meldung { border: 1px solid #d9ff4b; color: #d9ff4b; padding: 0.8rem 1rem;
             margin: 1.4rem 0; max-width: 46rem; }
  .zahlen { display: flex; gap: 2.5rem; margin-top: 1.4rem; }
  .zahlen strong { display: block; font-size: 2rem; color: #d9ff4b; }
  table { border-collapse: collapse; width: 100%; max-width: 60rem; font-size: 0.92rem; }
  th, td { text-align: left; padding: 0.5rem 0.8rem 0.5rem 0;
           border-bottom: 1px solid #26252e; }
  th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: #9a98a3; }
  .status-confirmed { color: #d9ff4b; }
  .status-pending { color: #9a98a3; }
  form.inline { display: inline; }
  input[type="text"], textarea {
    width: 100%; max-width: 46rem; font: inherit; color: #f2f1f4;
    background: #131318; border: 1px solid #26252e; padding: 0.6em 0.8em;
  }
  textarea { min-height: 14rem; }
  label { display: block; margin: 1rem 0 0.35rem; font-size: 0.8rem;
          text-transform: uppercase; letter-spacing: 0.08em; color: #9a98a3; }
  button {
    font: inherit; font-weight: 700; text-transform: uppercase; cursor: pointer;
    background: #d9ff4b; color: #0b0b0f; border: 0; padding: 0.6em 1.6em; margin-top: 1rem;
  }
  button.klein { padding: 0.15em 0.7em; margin: 0; font-size: 0.75rem;
                 background: transparent; color: #ff7a6e; border: 1px solid #ff7a6e; }
  button.neutral { background: transparent; color: #9a98a3; border: 1px solid #26252e;
                   margin: 0; padding: 0.35em 1em; font-size: 0.8rem; }
  button.neutral:hover { color: #f2f1f4; border-color: #9a98a3; }
  a { color: #a78bfa; }
  .haken { margin-top: 1rem; font-size: 0.95rem; color: #f2f1f4;
           text-transform: none; letter-spacing: 0; display: flex; gap: 0.5rem; }
</style>
</head>
<body>
  <div class="kopfzeile">
    <div>
      <h1>Newsletter-<em>Verwaltung</em></h1>
      <p class="hinweis">Kulturort Admiralbrücke · nur für die D-Jam-Gemeinschaft</p>
    </div>
    <form method="post" action="verwaltung">
      <input type="hidden" name="aktion" value="logout">
      <button class="neutral" type="submit">Abmelden</button>
    </form>
  </div>

  <?php if ($meldung !== ''): ?>
    <p class="meldung"><?= e($meldung) ?></p>
  <?php endif; ?>

  <div class="zahlen">
    <div><strong><?= (int)($statistik['confirmed'] ?? 0) ?></strong> bestätigt</div>
    <div><strong><?= (int)($statistik['pending'] ?? 0) ?></strong> unbestätigt</div>
    <div><strong><?= count($historie) ?></strong> Aussendungen</div>
  </div>

  <h2>Newsletter senden</h2>
  <p class="hinweis">Geht als reine Text-Mail an alle bestätigten Adressen,
  mit persönlichem Abmeldelink am Ende.</p>
  <form method="post" action="verwaltung">
    <input type="hidden" name="aktion" value="senden">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <label for="betreff">Betreff</label>
    <input type="text" id="betreff" name="betreff" maxlength="200" required>
    <label for="text">Text</label>
    <textarea id="text" name="text" maxlength="20000" required></textarea>
    <label class="haken"><input type="checkbox" name="sicher" required>
      Ja, wirklich an alle bestätigten Abonnent:innen senden.</label>
    <button type="submit">Senden</button>
  </form>

  <h2>Abonnent:innen</h2>
  <p class="hinweis"><a href="verwaltung?export=csv">Bestätigte Adressen als CSV exportieren</a></p>
  <table>
    <tr><th>E-Mail</th><th>Status</th><th>Sprache</th><th>Angemeldet</th><th>Bestätigt</th><th></th></tr>
    <?php foreach ($abonnenten as $abo): ?>
      <tr>
        <td><?= e((string)$abo['email']) ?></td>
        <td class="status-<?= e((string)$abo['status']) ?>"><?= e((string)$abo['status']) ?></td>
        <td><?= e((string)$abo['sprache']) ?></td>
        <td><?= e(substr((string)$abo['ts_signup'], 0, 10)) ?></td>
        <td><?= e(substr((string)($abo['ts_confirm'] ?? ''), 0, 10)) ?></td>
        <td>
          <form class="inline" method="post" action="verwaltung"
                onsubmit="return confirm('Adresse wirklich löschen?')">
            <input type="hidden" name="aktion" value="loeschen">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="email" value="<?= e((string)$abo['email']) ?>">
            <button class="klein" type="submit">löschen</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h2>Versandhistorie</h2>
  <table>
    <tr><th>Datum</th><th>Betreff</th><th>Empfänger</th></tr>
    <?php foreach ($historie as $v): ?>
      <tr>
        <td><?= e(substr((string)$v['ts'], 0, 16)) ?></td>
        <td><?= e((string)$v['betreff']) ?></td>
        <td><?= (int)$v['empfaenger'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>
</html>
