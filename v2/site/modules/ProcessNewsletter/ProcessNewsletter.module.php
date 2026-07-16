<?php

namespace ProcessWire;

/**
 * Newsletter management inside the ProcessWire admin.
 * Shares storage and behavior with the public newsletter endpoints
 * (contract: docs/api-contract.md in the repository).
 */
class ProcessNewsletter extends Process
{
    private const TARGET_MAIL = 'kontakt@kulturort-admiralbruecke.de';
    private const DB_FILE     = '/home/syso/kulturort-newsletter/newsletter.sqlite';
    private const HOSTS      = [
        'v2.kulturort-admiralbruecke.de',
        'kulturort-admiralbruecke.de',
        'www.kulturort-admiralbruecke.de',
        'syso.uber.space',
    ];

    public static function getModuleInfo(): array
    {
        return [
            'title'      => 'Newsletter',
            'summary'    => 'Abonnentenliste, Testversand und Newsletter-Versand',
            'version'    => 1,
            'icon'       => 'envelope',
            'permission' => 'newsletter-admin',
            'permissions' => [
                'newsletter-admin' => 'Newsletter verwalten und versenden',
            ],
            'page' => [
                'name'   => 'newsletter',
                'parent' => 'admin',
                'title'  => 'Newsletter',
            ],
        ];
    }

    private function db(): \PDO
    {
        return new \PDO('sqlite:' . self::DB_FILE, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function baseUrl(): string
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if (!in_array($host, self::HOSTS, true)) {
            $host = self::HOSTS[1];
        }
        return 'https://' . $host;
    }

    private function campaignSlug(string $value): string
    {
        $value = mb_strtolower($value);
        $value = strtr($value, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $value = (string)preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim(mb_substr($value, 0, 40), '-');
    }

    private function campaignLink(string $url, string $keyword): string
    {
        $fragment = '';
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        }
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'mtm_campaign=newsletter&mtm_kwd=' . rawurlencode($keyword) . $fragment;
    }

    private function tagText(string $text, string $keyword): string
    {
        return (string)preg_replace_callback(
            '~https://(?:www\.)?(?:kulturort-admiralbruecke\.de|v2\.kulturort-admiralbruecke\.de|syso\.uber\.space)[^\s<>"]*~',
            function (array $match) use ($keyword): string {
                $url  = rtrim($match[0], '.,;:)!?»');
                $rest = substr($match[0], strlen($url));
                return $this->campaignLink($url, $keyword) . $rest;
            },
            $text
        );
    }

    private function buildBody(string $taggedText, string $keyword, string $token): string
    {
        $unsubscribe = $this->baseUrl() . '/newsletter/abmelden/' . $token;
        return $taggedText . "\n\n--\n"
            . "Kulturort Admiralbrücke · dienstags auf der Brücke\n"
            . "Zur Seite: " . $this->campaignLink($this->baseUrl() . '/', $keyword) . "\n"
            . "Abmelden: " . $unsubscribe . "\n";
    }

    private function mailOne(string $to, string $subject, string $body, string $token): bool
    {
        $unsubscribe = $this->baseUrl() . '/newsletter/abmelden/' . $token;
        $headers = "From: " . self::TARGET_MAIL . "\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: 8bit\r\n"
                 . "List-Unsubscribe: <" . $unsubscribe . ">\r\n";
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $encodedSubject, $body, $headers);
    }

    public function ___execute(): string
    {
        $input = $this->wire('input');
        $notices = [];

        if ($input->post('action')) {
            $this->wire('session')->CSRF->validate();
            $notices[] = $this->handleAction((string)$input->post('action'));
        }

        $pdo = $this->db();
        $stats = $pdo->query('SELECT status, COUNT(*) AS n FROM abonnenten GROUP BY status')
                     ->fetchAll(\PDO::FETCH_KEY_PAIR);
        $subscribers = $pdo->query(
            'SELECT email, status, sprache, ts_signup, ts_confirm
             FROM abonnenten ORDER BY ts_signup DESC LIMIT 500'
        )->fetchAll(\PDO::FETCH_ASSOC);
        $history = $pdo->query(
            'SELECT ts, betreff, empfaenger FROM versand ORDER BY id DESC LIMIT 20'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = '';
        foreach (array_filter($notices) as $notice) {
            $out .= "<p class='uk-alert uk-alert-primary'>" .
                    $this->wire('sanitizer')->entities($notice) . "</p>";
        }

        $confirmed = (int)($stats['confirmed'] ?? 0);
        $pending   = (int)($stats['pending'] ?? 0);
        $out .= "<p><strong>$confirmed</strong> bestätigt · <strong>$pending</strong> unbestätigt</p>";

        $out .= $this->renderComposeForm($confirmed);
        $out .= $this->renderSubscriberTable($subscribers);
        $out .= $this->renderHistoryTable($history);
        return $out;
    }

    private function handleAction(string $action): string
    {
        $input = $this->wire('input');
        $subject = trim((string)$input->post('subject'));
        $text    = trim((string)$input->post('bodytext'));

        if ($action === 'delete') {
            $email = (string)$input->post('email');
            $statement = $this->db()->prepare('DELETE FROM abonnenten WHERE email = ?');
            $statement->execute([$email]);
            return $statement->rowCount() > 0 ? "Adresse gelöscht: $email" : 'Adresse nicht gefunden.';
        }

        if ($subject === '' || $text === '') {
            return 'Betreff und Text sind nötig.';
        }
        $keyword = gmdate('Y-m-d') . '-' . $this->campaignSlug($subject);
        $tagged  = $this->tagText($text, $keyword);

        if ($action === 'test') {
            $token = str_repeat('0', 64);
            $body = "[TESTVERSAND – nur an kontakt@]\n\n" . $this->buildBody($tagged, $keyword, $token);
            $ok = $this->mailOne(self::TARGET_MAIL, '[Test] ' . $subject, $body, $token);
            return $ok
                ? 'Testmail an kontakt@ gesendet – bitte Postfach prüfen.'
                : 'Testmail wurde vom Mailserver abgelehnt (Uberspace-Limit? Standard: 5 Mails/Stunde).';
        }

        if ($action === 'send') {
            $pdo = $this->db();
            $recipients = $pdo->query('SELECT email, token FROM abonnenten WHERE status = "confirmed"')
                              ->fetchAll(\PDO::FETCH_ASSOC);
            $sent = 0;
            foreach ($recipients as $recipient) {
                $body = $this->buildBody($tagged, $keyword, (string)$recipient['token']);
                if ($this->mailOne((string)$recipient['email'], $subject, $body, (string)$recipient['token'])) {
                    $sent++;
                }
                usleep(150000);
            }
            $pdo->prepare('INSERT INTO versand (ts, betreff, empfaenger) VALUES (?, ?, ?)')
                ->execute([gmdate('c'), $subject, $sent]);
            $total = count($recipients);
            $message = "Newsletter an $sent von $total Adresse(n) gesendet.";
            if ($sent < $total) {
                $message .= ' Achtung: Ein Teil wurde vom Mailserver abgelehnt – vermutlich das'
                          . ' Uberspace-Limit (Standard: 5 Mails/Stunde). Später erneut senden oder'
                          . ' bei hallo@uberspace.de eine Erhöhung erbitten.';
            }
            return $message;
        }

        return '';
    }

    private function renderComposeForm(int $confirmed): string
    {
        $modules = $this->wire('modules');

        /** @var InputfieldForm $form */
        $form = $modules->get('InputfieldForm');
        $form->action = './';
        $form->method = 'post';

        $field = $modules->get('InputfieldText');
        $field->name = 'subject';
        $field->label = 'Betreff';
        $field->required = true;
        $form->add($field);

        $field = $modules->get('InputfieldTextarea');
        $field->name = 'bodytext';
        $field->label = 'Text';
        $field->description = 'Reine Text-Mail. Links zur Seite werden automatisch für die Statistik markiert; Abmeldelink wird angehängt.';
        $field->rows = 12;
        $field->required = true;
        $form->add($field);

        $button = $modules->get('InputfieldSubmit');
        $button->name = 'action';
        $button->value = 'test';
        $button->text = 'Testmail an kontakt@';
        $form->add($button);

        $button = $modules->get('InputfieldSubmit');
        $button->name = 'action';
        $button->value = 'send';
        $button->text = "An alle $confirmed Bestätigten senden";
        $button->attr('onclick', "return confirm('Wirklich an alle $confirmed bestätigten Abonnent:innen senden?')");
        $form->add($button);

        return '<h2>Newsletter schreiben</h2>' . $form->render();
    }

    private function renderSubscriberTable(array $subscribers): string
    {
        $table = $this->wire('modules')->get('MarkupAdminDataTable');
        $table->setEncodeEntities(false);
        $table->headerRow(['E-Mail', 'Status', 'Sprache', 'Seit', 'Aktion']);
        $sanitizer = $this->wire('sanitizer');
        $token = $this->wire('session')->CSRF->renderInput();

        foreach ($subscribers as $subscriber) {
            $email = $sanitizer->entities((string)$subscriber['email']);
            $deleteForm = "<form method='post' action='./' style='display:inline'"
                . " onsubmit=\"return confirm('Adresse $email wirklich löschen?')\">"
                . $token
                . "<input type='hidden' name='email' value='$email'>"
                . "<button type='submit' name='action' value='delete' class='uk-button uk-button-small uk-button-danger'>löschen</button>"
                . '</form>';
            $table->row([
                $email,
                $subscriber['status'] === 'confirmed' ? 'bestätigt' : 'unbestätigt',
                $sanitizer->entities((string)$subscriber['sprache']),
                substr((string)($subscriber['ts_confirm'] ?? $subscriber['ts_signup']), 0, 10),
                $deleteForm,
            ]);
        }
        return '<h2>Abonnent:innen</h2>' . $table->render();
    }

    private function renderHistoryTable(array $history): string
    {
        $table = $this->wire('modules')->get('MarkupAdminDataTable');
        $table->headerRow(['Datum', 'Betreff', 'Empfänger']);
        foreach ($history as $entry) {
            $table->row([
                str_replace('T', ' ', substr((string)$entry['ts'], 0, 16)),
                (string)$entry['betreff'],
                (string)$entry['empfaenger'],
            ]);
        }
        return '<h2>Versandhistorie</h2>' . $table->render();
    }
}
