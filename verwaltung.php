<?php
/**
 * Newsletter-Verwaltung – Vertrag: docs/api-contract.md
 * HTTP Basic Auth gegen ~/kulturort-newsletter/config.php,
 * Abonnentenliste, CSV-Export, Löschen, Versand an alle Bestätigten.
 */

declare(strict_types=1);

const ZIEL_MAIL = 'kontakt@kulturort-admiralbruecke.de';
const NL_DIR    = '/home/syso/kulturort-newsletter';
const NL_DB     = NL_DIR . '/newsletter.sqlite';
const NL_CONFIG = NL_DIR . '/config.php';
const HOSTS     = ['syso.uber.space', 'kulturort-admiralbruecke.de', 'www.kulturort-admiralbruecke.de'];

// ---- Auth -----------------------------------------------------------------

if (!is_file(NL_CONFIG)) {
    http_response_code(503);
    exit('Verwaltung nicht eingerichtet (config.php fehlt).');
}
$cfg = require NL_CONFIG;

$nutzer   = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
$passwort = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
if ($nutzer !== 'djam' || !password_verify($passwort, (string)$cfg['pass_hash'])) {
    header('WWW-Authenticate: Basic realm="Kulturort Verwaltung", charset="UTF-8"');
    http_response_code(401);
    exit('Anmeldung erforderlich.');
}

$csrf = hash_hmac('sha256', gmdate('Y-m-d'), (string)$cfg['secret']);

function csrf_pruefen(string $csrf): void {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Ungültiges Formular-Token, bitte Seite neu laden.');
    }
}

function basis_url(): string {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (!in_array($host, HOSTS, true)) {
        $host = HOSTS[0];
    }
    return 'https://' . $host;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$pdo = new PDO('sqlite:' . NL_DB, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ---- Aktionen ---------------------------------------------------------------

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
            $gesendet = 0;
            foreach ($empfaenger as $abo) {
                $abmelden = basis_url() . '/newsletter/abmelden/' . $abo['token'];
                $body = $text . "\n\n--\n"
                      . "Kulturort Admiralbrücke · dienstags auf der Brücke\n"
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
  a { color: #a78bfa; }
  .haken { margin-top: 1rem; font-size: 0.95rem; color: #f2f1f4;
           text-transform: none; letter-spacing: 0; display: flex; gap: 0.5rem; }
</style>
</head>
<body>
  <h1>Newsletter-<em>Verwaltung</em></h1>
  <p class="hinweis">Kulturort Admiralbrücke · nur für die D-Jam-Gemeinschaft</p>

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
