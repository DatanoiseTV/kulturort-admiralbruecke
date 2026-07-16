<?php

/**
 * Newsletter management as a native panel area.
 * Shares storage and behavior with the public newsletter endpoints
 * (contract: docs/api-contract.md in the repository).
 */

use Kirby\Cms\App;

class NewsletterService
{
    public const TARGET_MAIL = 'kontakt@kulturort-admiralbruecke.de';
    public const DB_FILE     = '/home/syso/kulturort-newsletter/newsletter.sqlite';
    public const HOSTS       = [
        'v2.kulturort-admiralbruecke.de',
        'kulturort-admiralbruecke.de',
        'www.kulturort-admiralbruecke.de',
        'syso.uber.space',
    ];

    public static function db(): PDO
    {
        $pdo = new PDO('sqlite:' . self::DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $pdo;
    }

    public static function baseUrl(): string
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if (!in_array($host, self::HOSTS, true)) {
            $host = self::HOSTS[1];
        }
        return 'https://' . $host;
    }

    public static function slug(string $value, int $max = 40): string
    {
        $value = mb_strtolower($value);
        $value = strtr($value, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $value = (string)preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim(mb_substr($value, 0, $max), '-');
    }

    public static function campaignLink(string $url, string $keyword): string
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

    public static function tagText(string $text, string $keyword): string
    {
        return (string)preg_replace_callback(
            '~https://(?:www\.)?(?:kulturort-admiralbruecke\.de|v2\.kulturort-admiralbruecke\.de|syso\.uber\.space)[^\s<>"]*~',
            function (array $match) use ($keyword): string {
                $url  = rtrim($match[0], '.,;:)!?»');
                $rest = substr($match[0], strlen($url));
                return self::campaignLink($url, $keyword) . $rest;
            },
            $text
        );
    }

    public static function data(): array
    {
        $pdo = self::db();
        $stats = $pdo->query('SELECT status, COUNT(*) AS n FROM abonnenten GROUP BY status')
                     ->fetchAll(PDO::FETCH_KEY_PAIR);
        $subscribers = $pdo->query(
            'SELECT email, status, sprache AS language, ts_signup AS signedUp, ts_confirm AS confirmed
             FROM abonnenten ORDER BY ts_signup DESC LIMIT 500'
        )->fetchAll(PDO::FETCH_ASSOC);
        $history = $pdo->query(
            'SELECT ts, betreff AS subject, empfaenger AS recipients
             FROM versand ORDER BY id DESC LIMIT 20'
        )->fetchAll(PDO::FETCH_ASSOC);
        return [
            'confirmed'   => (int)($stats['confirmed'] ?? 0),
            'pending'     => (int)($stats['pending'] ?? 0),
            'subscribers' => $subscribers,
            'history'     => $history,
        ];
    }

    public static function delete(string $email): bool
    {
        $statement = self::db()->prepare('DELETE FROM abonnenten WHERE email = ?');
        $statement->execute([$email]);
        return $statement->rowCount() > 0;
    }

    private static function buildBody(string $taggedText, string $keyword, string $token): string
    {
        $unsubscribe = self::baseUrl() . '/newsletter/abmelden/' . $token;
        return $taggedText . "\n\n--\n"
            . "Kulturort Admiralbrücke · dienstags auf der Brücke\n"
            . "Zur Seite: " . self::campaignLink(self::baseUrl() . '/', $keyword) . "\n"
            . "Abmelden: " . $unsubscribe . "\n";
    }

    private static function mailOne(string $to, string $subject, string $body, string $token): bool
    {
        $unsubscribe = self::baseUrl() . '/newsletter/abmelden/' . $token;
        $headers = "From: " . self::TARGET_MAIL . "\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: 8bit\r\n"
                 . "List-Unsubscribe: <" . $unsubscribe . ">\r\n";
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $encodedSubject, $body, $headers);
    }

    /** Test delivery: rendered exactly like the real thing, but only to the project mailbox. */
    public static function sendTest(string $subject, string $text): bool
    {
        $keyword = gmdate('Y-m-d') . '-' . self::slug($subject);
        $tagged  = self::tagText($text, $keyword);
        $token   = str_repeat('0', 64);
        $body    = "[TESTVERSAND – nur an kontakt@]\n\n" . self::buildBody($tagged, $keyword, $token);
        return self::mailOne(self::TARGET_MAIL, '[Test] ' . $subject, $body, $token);
    }

    /** Real delivery to every confirmed subscriber. */
    public static function sendAll(string $subject, string $text): array
    {
        $pdo = self::db();
        $recipients = $pdo->query('SELECT email, token FROM abonnenten WHERE status = "confirmed"')
                          ->fetchAll(PDO::FETCH_ASSOC);
        $keyword = gmdate('Y-m-d') . '-' . self::slug($subject);
        $tagged  = self::tagText($text, $keyword);
        $sent = 0;
        foreach ($recipients as $recipient) {
            $body = self::buildBody($tagged, $keyword, (string)$recipient['token']);
            if (self::mailOne((string)$recipient['email'], $subject, $body, (string)$recipient['token'])) {
                $sent++;
            }
            usleep(150000);
        }
        $pdo->prepare('INSERT INTO versand (ts, betreff, empfaenger) VALUES (?, ?, ?)')
            ->execute([gmdate('c'), $subject, $sent]);
        return ['sent' => $sent, 'total' => count($recipients)];
    }
}

App::plugin('kulturort/newsletter', [
    'areas' => [
        'newsletter' => function () {
            return [
                'label' => 'Newsletter',
                'icon'  => 'email',
                'menu'  => true,
                'link'  => 'newsletter',
                'views' => [
                    [
                        'pattern' => 'newsletter',
                        'action'  => function () {
                            return [
                                'component' => 'k-newsletter-view',
                                'title'     => 'Newsletter',
                                'props'     => NewsletterService::data(),
                            ];
                        },
                    ],
                ],
            ];
        },
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'newsletter/data',
                'method'  => 'GET',
                'action'  => fn () => NewsletterService::data(),
            ],
            [
                'pattern' => 'newsletter/delete',
                'method'  => 'POST',
                'action'  => function () {
                    $email = (string)App::instance()->request()->get('email');
                    return ['deleted' => NewsletterService::delete($email)];
                },
            ],
            [
                'pattern' => 'newsletter/test',
                'method'  => 'POST',
                'action'  => function () {
                    $request = App::instance()->request();
                    $subject = trim((string)$request->get('subject'));
                    $text    = trim((string)$request->get('text'));
                    if ($subject === '' || $text === '') {
                        return ['ok' => false, 'message' => 'Betreff und Text sind nötig.'];
                    }
                    $ok = NewsletterService::sendTest($subject, $text);
                    return [
                        'ok' => $ok,
                        'message' => $ok
                            ? 'Testmail an kontakt@ gesendet – bitte Postfach prüfen.'
                            : 'Testmail wurde vom Mailserver abgelehnt (Uberspace-Limit? Standard: 5 Mails/Stunde).',
                    ];
                },
            ],
            [
                'pattern' => 'newsletter/send',
                'method'  => 'POST',
                'action'  => function () {
                    $request = App::instance()->request();
                    $subject = trim((string)$request->get('subject'));
                    $text    = trim((string)$request->get('text'));
                    if ($subject === '' || $text === '') {
                        return ['ok' => false, 'message' => 'Betreff und Text sind nötig.'];
                    }
                    $result = NewsletterService::sendAll($subject, $text);
                    $message = "Newsletter an {$result['sent']} von {$result['total']} Adresse(n) gesendet.";
                    if ($result['sent'] < $result['total']) {
                        $message .= ' Achtung: Ein Teil wurde vom Mailserver abgelehnt – vermutlich das'
                                  . ' Uberspace-Limit (Standard: 5 Mails/Stunde). Später erneut senden'
                                  . ' oder bei hallo@uberspace.de eine Erhöhung erbitten.';
                    }
                    return ['ok' => true, 'message' => $message] + $result;
                },
            ],
        ],
    ],
]);
