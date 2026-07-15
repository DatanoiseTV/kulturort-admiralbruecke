# API-Vertrag – Kulturort Admiralbrücke

Statische Seite mit wenigen dynamischen Endpunkten. Diese Datei ist
maßgeblich: kein Feld und kein Fehlerfall wird im Code ergänzt, ohne ihn
hier zuerst festzuhalten.

## Saubere URLs

`.htaccess`-Rewrites (kanonische Form; die `.php`-Ziele bleiben intern):

| Route                                | Ziel                                        |
|--------------------------------------|---------------------------------------------|
| `POST /feedback`                     | `feedback.php`                               |
| `POST /newsletter/anmelden`          | `newsletter.php?action=signup`               |
| `GET /newsletter/bestaetigen/<token>`| `newsletter.php?action=confirm&token=<token>`|
| `GET /newsletter/abmelden/<token>`   | `newsletter.php?action=abmelden&token=<token>`|
| `GET/POST /verwaltung`               | `verwaltung.php`                             |

## POST /feedback.php

Nimmt eine Feedback-Einsendung als klassisches HTML-Formular entgegen
(`application/x-www-form-urlencoded`), speichert sie serverseitig und
leitet zurück auf die Startseite. Kein JSON, kein JavaScript nötig.

### Request-Felder

| Feld          | Pflicht | Regeln                                                        |
|---------------|---------|---------------------------------------------------------------|
| `sterne`      | nein    | Gesamteindruck, ganzzahlig `1`–`5`; sonst leer                 |
| `lautstaerke` | nein    | eines von: `passt`, `manchmal_zu_laut`, `oft_zu_laut`; sonst leer |
| `rolle`       | nein    | eines von: `anwohner`, `musiker`, `gast`, `sonstiges`; sonst leer |
| `gefaellt`    | nein*   | "Was gefällt dir?", max. 2000 Zeichen                          |
| `stoert`      | nein*   | "Was stört dich?", max. 2000 Zeichen                           |
| `nachricht`   | nein*   | freie Nachricht, max. 4000 Zeichen                             |
| `name`        | nein    | max. 200 Zeichen                                               |
| `email`       | nein    | wenn gesetzt: gültige Adresse (`FILTER_VALIDATE_EMAIL`), max. 320 |
| `website`     | –       | Honeypot; MUSS leer sein, sonst wird die Einsendung verworfen  |
| `sprache`     | nein    | `de` oder `en` (UI-Sprache beim Absenden), sonst `de`          |

*Pflichtregel: mindestens eines von `gefaellt`, `stoert`, `nachricht` hat
nach Trim ≥ 5 Zeichen, ODER `sterne`/`lautstaerke` ist gesetzt (eine reine
Bewertung ohne Text ist eine gültige Einsendung).

### Verhalten bei Erfolg

1. Einsendung als JSON-Zeile angehängt an
   `~/kulturort-feedback/feedback.jsonl` (außerhalb des Docroots):
   `{ts, ip_hash, sterne, lautstaerke, rolle, gefaellt, stoert, nachricht,
   name, email, sprache}`.
   `ip_hash` = sha256 über IP + Tagesdatum (Spam-Diagnose ohne
   dauerhafte IP-Speicherung).
2. Mail an `kontakt@kulturort-admiralbruecke.de`
   (From: kontakt@…, Reply-To: Absenderadresse, falls angegeben).
   Mail-Fehler sind nicht fatal – die JSONL-Zeile zählt.
3. Redirect `303 See Other` auf `/?feedback=danke#feedback`.

### Verhalten bei Fehlern

| Fall                                  | Antwort                                   |
|---------------------------------------|-------------------------------------------|
| Honeypot gefüllt                      | Redirect `/?feedback=danke#feedback` (Bots bekommen Erfolg vorgegaukelt) |
| Pflichtregel verletzt (kein Text ≥ 5 Zeichen und keine Bewertung) | Redirect `/?feedback=fehler#feedback` |
| Textfeld über Maximallänge            | Redirect `/?feedback=fehler#feedback`     |
| `email` gesetzt, aber ungültig        | Redirect `/?feedback=fehler#feedback`     |
| Andere HTTP-Methode als POST          | `405 Method Not Allowed`                  |
| Speicherfehler (Datei nicht schreibbar) | Redirect `/?feedback=fehler#feedback`   |

Die Startseite zeigt bei `?feedback=danke` bzw. `?feedback=fehler` eine
zweisprachige Bestätigung/Fehlermeldung über dem Formular (per JS
eingeblendet; ohne JS bleibt die Seite schlicht ohne Meldung nutzbar).

## Newsletter (`newsletter.php`)

Double-Opt-in-Pflicht: Eine Adresse gilt erst als angemeldet, wenn der
Bestätigungslink aus der Opt-in-Mail geklickt wurde. Speicherung in
SQLite außerhalb des Docroots (`~/kulturort-newsletter/newsletter.sqlite`),
Tabelle `abonnenten(email UNIQUE, status, token, sprache, ts_signup,
ts_confirm)`; `status` ∈ `pending` | `confirmed`. Abmeldung LÖSCHT die
Zeile (Datenminimierung). Tokens: 64 Hex-Zeichen aus `random_bytes(32)`,
pro Adresse fest. Basis-URL für Links wird NUR aus einer Host-Allowlist
gebildet (`syso.uber.space`, `kulturort-admiralbruecke.de`, `www.…`) –
kein Host-Header-Injection in Mails.

### POST /newsletter.php  (`action=signup`)

| Feld      | Pflicht | Regeln                                          |
|-----------|---------|--------------------------------------------------|
| `email`   | ja      | `FILTER_VALIDATE_EMAIL`, max. 320, lowercased    |
| `website` | –       | Honeypot; gefüllt ⇒ vorgetäuschter Erfolg        |
| `sprache` | nein    | `de`/`en`, bestimmt Sprache der Opt-in-Mail      |

Verhalten: neue Adresse ⇒ `pending` anlegen + Opt-in-Mail; existierende
`pending` ⇒ Opt-in-Mail erneut senden, aber höchstens alle 15 Minuten;
existierende `confirmed` ⇒ keine Mail, trotzdem Erfolgsmeldung (kein
Adress-Oracle). Redirect immer `303` auf `/?newsletter=bestaetigen#newsletter`;
bei ungültiger Mail `/?newsletter=fehler#newsletter`.

### GET /newsletter.php?action=confirm&token=…

Gültiger Token einer `pending`-Adresse ⇒ `confirmed`, Redirect
`/?newsletter=bestaetigt#newsletter`. Unbekannter Token ⇒
`/?newsletter=fehler#newsletter`.

### GET /newsletter.php?action=abmelden&token=…

Gültiger Token ⇒ Zeile löschen, Redirect `/?newsletter=abgemeldet#newsletter`.
Unbekannter Token ⇒ ebenfalls `abgemeldet` (idempotent, kein Oracle).

### /verwaltung.php (Admin, nicht öffentlich verlinkt)

Session-Login mit eigenem Formular (kein HTTP Basic):
- Passwortabgleich per `password_verify` gegen `pass_hash` aus
  `~/kulturort-newsletter/config.php`; nur ein Passwortfeld, kein
  Nutzername.
- Session-Cookie `kulturort_session`: `Secure`, `HttpOnly`,
  `SameSite=Lax`, `use_strict_mode`; `session_regenerate_id` nach
  Login; Idle-Timeout 4 h; Logout-Button (POST).
- Brute-Force-Bremse: fehlgeschlagene Versuche pro IP-Tages-Hash in
  Tabelle `login_versuche(ip_hash, ts)`; ab 8 Fehlversuchen in 15
  Minuten wird der Login verweigert (HTTP 429-Verhalten im Formular).
  Erfolgreicher Login löscht die Zähler der IP.
- Nicht angemeldet ⇒ Loginformular (HTTP 200), keine Inhalte.

Funktionen wie gehabt: Abonnentenliste mit Status, CSV-Export der
bestätigten Adressen, Abonnent löschen, Newsletter (Betreff + Text) an
alle `confirmed` – jede Mail mit individuellem Abmeldelink und
`List-Unsubscribe`-Header. Schreibende Aktionen nur per POST mit
CSRF-Token aus der Session (`random_bytes`, `hash_equals`);
Versandhistorie in Tabelle `versand(ts, betreff, empfaenger)`.
