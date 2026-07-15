# API-Vertrag – Kulturort Admiralbrücke

Statische Seite mit genau einem dynamischen Endpunkt. Diese Datei ist
maßgeblich: kein Feld und kein Fehlerfall wird im Code ergänzt, ohne ihn
hier zuerst festzuhalten.

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
