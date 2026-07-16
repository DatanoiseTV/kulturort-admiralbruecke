<?php
/**
 * Newsletter: Anmeldung (Double-Opt-in), Bestätigung, Abmeldung.
 * Vertrag: docs/api-contract.md
 */

declare(strict_types=1);

const ZIEL_MAIL = 'kontakt@kulturort-admiralbruecke.de';
const NL_DIR    = '/home/syso/kulturort-newsletter';
const NL_DB     = NL_DIR . '/newsletter.sqlite';
const HOSTS     = ['v2.kulturort-admiralbruecke.de', 'syso.uber.space', 'kulturort-admiralbruecke.de', 'www.kulturort-admiralbruecke.de'];
const RESEND_SPERRE_S = 15 * 60;

function weiter(string $status): never {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $base = (string)preg_replace('~(feedback|newsletter)(\.php)?(/[^?]*)?(\?.*)?$~', '', $uri);
    header('Location: ' . $base . '?newsletter=' . $status . '#newsletter', true, 303);
    exit;
}

function basis_url(): string {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (!in_array($host, HOSTS, true)) {
        $host = HOSTS[0];
    }
    return 'https://' . $host;
}

function db(): PDO {
    if (!is_dir(NL_DIR) && !mkdir(NL_DIR, 0700, true) && !is_dir(NL_DIR)) {
        weiter('fehler');
    }
    $pdo = new PDO('sqlite:' . NL_DB, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS abonnenten (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        status TEXT NOT NULL DEFAULT "pending",
        token TEXT NOT NULL UNIQUE,
        sprache TEXT NOT NULL DEFAULT "de",
        ts_signup TEXT NOT NULL,
        ts_confirm TEXT,
        ts_mail TEXT
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS versand (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ts TEXT NOT NULL,
        betreff TEXT NOT NULL,
        empfaenger INTEGER NOT NULL
    )');
    return $pdo;
}

function optin_mail(string $email, string $token, string $sprache): void {
    $link_bestaetigen = basis_url() . '/newsletter/bestaetigen/' . $token;
    $link_abmelden    = basis_url() . '/newsletter/abmelden/' . $token;

    if ($sprache === 'en') {
        $betreff = 'Please confirm: Kulturort Admiralbruecke newsletter';
        $body = "Hello,\n\n"
              . "someone (hopefully you) signed this address up for news from the\n"
              . "Kulturort Admiralbruecke - jam dates, updates on the initiative,\n"
              . "occasional invitations.\n\n"
              . "To confirm, open this link:\n$link_bestaetigen\n\n"
              . "If this wasn't you, simply ignore this mail - the address will\n"
              . "not be stored permanently without confirmation.\n\n"
              . "Unsubscribe any time:\n$link_abmelden\n\n"
              . "See you on the bridge,\nthe D-Jam community\n";
    } else {
        $betreff = 'Bitte bestätigen: Newsletter Kulturort Admiralbrücke';
        $body = "Hallo,\n\n"
              . "jemand (hoffentlich du) hat diese Adresse für Neuigkeiten vom\n"
              . "Kulturort Admiralbrücke eingetragen – Jam-Termine, Neues von der\n"
              . "Initiative, gelegentliche Einladungen.\n\n"
              . "Zum Bestätigen diesen Link öffnen:\n$link_bestaetigen\n\n"
              . "Wenn du das nicht warst, ignoriere diese Mail einfach – ohne\n"
              . "Bestätigung wird die Adresse nicht dauerhaft gespeichert.\n\n"
              . "Jederzeit abmelden:\n$link_abmelden\n\n"
              . "Bis Dienstag auf der Brücke,\ndie D-Jam-Gemeinschaft\n";
    }

    $betreff_kodiert = '=?UTF-8?B?' . base64_encode($betreff) . '?=';
    $header = "From: " . ZIEL_MAIL . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n";
    @mail($email, $betreff_kodiert, $body, $header);
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'signup') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        weiter('bestaetigen'); // Honeypot: Erfolg vortäuschen
    }
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if ($email === '' || mb_strlen($email) > 320
        || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        weiter('fehler');
    }
    $sprache = (string)($_POST['sprache'] ?? 'de');
    if (!in_array($sprache, ['de', 'en'], true)) {
        $sprache = 'de';
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT status, token, ts_mail FROM abonnenten WHERE email = ?');
    $stmt->execute([$email]);
    $bestand = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bestand === false) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO abonnenten (email, status, token, sprache, ts_signup, ts_mail)
                       VALUES (?, "pending", ?, ?, ?, ?)')
            ->execute([$email, $token, $sprache, gmdate('c'), gmdate('c')]);
        optin_mail($email, $token, $sprache);
    } elseif ($bestand['status'] === 'pending') {
        // erneut senden, aber gedrosselt
        if (strtotime((string)$bestand['ts_mail']) < time() - RESEND_SPERRE_S) {
            $pdo->prepare('UPDATE abonnenten SET ts_mail = ? WHERE email = ?')
                ->execute([gmdate('c'), $email]);
            optin_mail($email, (string)$bestand['token'], $sprache);
        }
    }
    // status "confirmed": nichts tun, aber Erfolg melden (kein Adress-Oracle)
    weiter('bestaetigen');
}

if ($action === 'confirm') {
    $token = (string)($_GET['token'] ?? '');
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
        weiter('fehler');
    }
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE abonnenten SET status = "confirmed", ts_confirm = ?
                           WHERE token = ? AND status = "pending"');
    $stmt->execute([gmdate('c'), $token]);
    if ($stmt->rowCount() === 0) {
        // schon bestätigt? Dann trotzdem freundlich sein.
        $check = $pdo->prepare('SELECT 1 FROM abonnenten WHERE token = ? AND status = "confirmed"');
        $check->execute([$token]);
        if ($check->fetchColumn() === false) {
            weiter('fehler');
        }
    }
    weiter('bestaetigt');
}

if ($action === 'abmelden') {
    $token = (string)($_GET['token'] ?? '');
    if (preg_match('/^[0-9a-f]{64}$/', $token)) {
        // Datenminimierung: Abmeldung löscht die Zeile komplett.
        db()->prepare('DELETE FROM abonnenten WHERE token = ?')->execute([$token]);
    }
    weiter('abgemeldet'); // idempotent, kein Oracle
}

http_response_code(400);
exit('Unbekannte Aktion');
