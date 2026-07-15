<?php
/**
 * Feedback-Endpunkt – Vertrag: docs/api-contract.md
 * Speichert Einsendungen als JSONL außerhalb des Docroots und
 * schickt eine Kopie an kontakt@kulturort-admiralbruecke.de.
 */

declare(strict_types=1);

const ZIEL_MAIL    = 'kontakt@kulturort-admiralbruecke.de';
const ABLAGE_DIR   = '/home/syso/kulturort-feedback';
const ABLAGE_DATEI = ABLAGE_DIR . '/feedback.jsonl';

function weiter(string $status): never {
    header('Location: /?feedback=' . $status . '#feedback', true, 303);
    exit;
}

function textfeld(string $name, int $max): ?string {
    $wert = trim((string)($_POST[$name] ?? ''));
    if (mb_strlen($wert) > $max) {
        weiter('fehler');
    }
    return $wert;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

// Honeypot: Bots bekommen Erfolg vorgegaukelt, die Einsendung verschwindet.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    weiter('danke');
}

$gefaellt  = textfeld('gefaellt', 2000);
$stoert    = textfeld('stoert', 2000);
$nachricht = textfeld('nachricht', 4000);
$name      = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 200);
$email     = trim((string)($_POST['email'] ?? ''));

$sterne = (string)($_POST['sterne'] ?? '');
$sterne = in_array($sterne, ['1', '2', '3', '4', '5'], true) ? (int)$sterne : null;

$lautstaerke = (string)($_POST['lautstaerke'] ?? '');
if (!in_array($lautstaerke, ['passt', 'manchmal_zu_laut', 'oft_zu_laut'], true)) {
    $lautstaerke = '';
}

$rolle = (string)($_POST['rolle'] ?? '');
if (!in_array($rolle, ['anwohner', 'musiker', 'gast', 'sonstiges'], true)) {
    $rolle = '';
}

$sprache = (string)($_POST['sprache'] ?? 'de');
if (!in_array($sprache, ['de', 'en'], true)) {
    $sprache = 'de';
}

// Pflichtregel: Text ODER Bewertung – eine reine Bewertung zählt.
$hatText = mb_strlen($gefaellt) >= 5 || mb_strlen($stoert) >= 5 || mb_strlen($nachricht) >= 5;
$hatBewertung = $sterne !== null || $lautstaerke !== '';
if (!$hatText && !$hatBewertung) {
    weiter('fehler');
}

if ($email !== '') {
    if (mb_strlen($email) > 320 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        weiter('fehler');
    }
}

$eintrag = [
    'ts'      => gmdate('c'),
    // IP nur als Tages-Hash, für Spam-Diagnose ohne dauerhafte IP-Speicherung
    'ip_hash' => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . gmdate('Y-m-d')),
    'sterne'      => $sterne,
    'lautstaerke' => $lautstaerke,
    'rolle'       => $rolle,
    'gefaellt'    => $gefaellt,
    'stoert'      => $stoert,
    'nachricht'   => $nachricht,
    'name'        => $name,
    'email'       => $email,
    'sprache'     => $sprache,
];

if (!is_dir(ABLAGE_DIR) && !mkdir(ABLAGE_DIR, 0700, true) && !is_dir(ABLAGE_DIR)) {
    weiter('fehler');
}

$zeile = json_encode($eintrag, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
if (file_put_contents(ABLAGE_DATEI, $zeile, FILE_APPEND | LOCK_EX) === false) {
    weiter('fehler');
}

// Mail ist Komfort, kein Muss – die JSONL-Zeile ist die Wahrheit.
$rollen = ['anwohner' => 'Anwohner:in', 'musiker' => 'Musiker:in', 'gast' => 'Gast', 'sonstiges' => 'Sonstiges'];
$laut   = ['passt' => 'passt', 'manchmal_zu_laut' => 'manchmal zu laut', 'oft_zu_laut' => 'oft zu laut'];

$betreff = '=?UTF-8?B?' . base64_encode('Feedback Kulturort Admiralbrücke') . '?=';
$body = "Neue Einsendung über das Feedback-Formular\n"
      . "Zeit (UTC):  " . $eintrag['ts'] . "\n"
      . "Sterne:      " . ($sterne !== null ? $sterne . '/5' : 'keine Angabe') . "\n"
      . "Lautstärke:  " . ($laut[$lautstaerke] ?? 'keine Angabe') . "\n"
      . "Rolle:       " . ($rollen[$rolle] ?? 'keine Angabe') . "\n"
      . "Name:        " . ($name !== '' ? $name : 'keine Angabe') . "\n"
      . "E-Mail:      " . ($email !== '' ? $email : 'keine Angabe') . "\n"
      . "Sprache:     " . $sprache . "\n"
      . "----------------------------------------\n"
      . "Was gefällt:\n" . ($gefaellt !== '' ? $gefaellt : '-') . "\n\n"
      . "Was stört:\n" . ($stoert !== '' ? $stoert : '-') . "\n\n"
      . "Nachricht:\n" . ($nachricht !== '' ? $nachricht : '-') . "\n";

$header = "From: " . ZIEL_MAIL . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n";
if ($email !== '') {
    // FILTER_VALIDATE_EMAIL oben schließt Zeilenumbrüche aus (kein Header-Injection).
    $header .= "Reply-To: " . $email . "\r\n";
}

@mail(ZIEL_MAIL, $betreff, $body, $header);

weiter('danke');
