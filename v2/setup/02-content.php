<?php

namespace ProcessWire;

/**
 * Content import: home hero, all sections in German and English,
 * photos with bilingual alt texts. Idempotent: skips existing pages.
 * Run on the server: php setup/02-content.php (from the PW root).
 */

include __DIR__ . '/../index.php';

wire('users')->setCurrentUser(wire('users')->get('djam'));

const PHOTO_DIR = '/var/www/virtual/syso/html/assets/img/';

$languages = wire('languages');
$de = $languages->get('default');
$en = $languages->get('en');

function setML(Page $page, string $field, $deValue, $enValue = null): void
{
    global $de, $en;
    $page->of(false);
    $value = $page->get($field);
    if ($value instanceof LanguagesPageFieldValue) {
        $value->setLanguageValue($de, $deValue);
        $value->setLanguageValue($en, $enValue ?? $deValue);
    } else {
        $page->set($field, $deValue);
    }
}

function activateEnglish(Page $page): void
{
    global $en;
    $page->of(false);
    $page->set("status$en->id", 1);
    $page->set("name$en->id", $page->name);
}

function addImage(Page $page, string $filename, string $altDe, string $altEn): void
{
    global $de, $en;
    if (!$page->images) {
        return;
    }
    foreach ($page->images as $existing) {
        if (str_starts_with($existing->name, pathinfo($filename, PATHINFO_FILENAME))) {
            return;
        }
    }
    $page->of(false);
    $page->images->add(PHOTO_DIR . $filename);
    $image = $page->images->last();
    $image->description($de, $altDe);
    $image->description($en, $altEn);
    $page->save('images');
    echo "  image: $filename\n";
}

function makePage(Page $parent, string $template, string $name, string $titleDe, string $titleEn): Page
{
    $existing = $parent->child("name=$name, include=all");
    if ($existing->id) {
        return $existing;
    }
    $page = new Page();
    $page->template = $template;
    $page->parent = $parent;
    $page->name = $name;
    $page->title = $titleDe;
    $page->save();
    setML($page, 'title', $titleDe, $titleEn);
    activateEnglish($page);
    $page->save();
    echo "page: $name\n";
    return $page;
}

$pages = wire('pages');
$home = $pages->get('/');
$home->of(false);

// ---- Home / Hero -------------------------------------------------------------

setML($home, 'title', 'Startseite', 'Home');
setML($home, 'body',
    '<p><strong>Musik als verbindende Sprache.</strong> Ein offenes Musizieren für alle – jung und alt, zuhörend, mitspielend, tanzend. Seit Jahren, mitten in Kreuzberg.</p>',
    '<p><strong>Music as a language that connects.</strong> Open music-making for everyone – young and old, listening, playing along, dancing. For years, in the middle of Kreuzberg.</p>');
setML($home, 'notice', 'als Versammlung angemeldet', 'registered as an assembly');
setML($home, 'ticker',
    'D-Jam — jeden Dienstag — Admiralbrücke — am frühen Abend — alle willkommen',
    'D-Jam — every Tuesday — Admiralbrücke — early evening — everyone welcome');
activateEnglish($home);
$home->save();
addImage($home, 'blaue-stunde.jpg',
    'Jam-Session in der blauen Stunde auf der Admiralbrücke: Musikerinnen und Musiker mit Gitarren unter Straßenlaternen am Landwehrkanal',
    'Blue-hour jam session on the Admiralbrücke: musicians with guitars under street lamps by the Landwehrkanal');

// ---- Der Ort ------------------------------------------------------------------

$ort = makePage($home, 'ort', 'ort', 'Der Ort', 'The place');
setML($ort, 'heading', 'Eine Brücke ist zum Überqueren da.', 'A bridge is for crossing.');
setML($ort, 'heading_accent', 'Diese hier ist zum Bleiben.', 'This one is for staying.');
setML($ort, 'body',
    '<p>Die Admiralbrücke steht sinnbildlich wie historisch für das Überwinden von Gegensätzen – für Begegnung und Verständigung. Verkehrsberuhigt, mit Sitzpollern, Wasser und Abendsonne, umgeben vom ständigen Treiben dieses Knotenpunkts, ist sie seit Jahren einer der ganz wenigen Orte der Stadt, an denen man einfach verweilen, Menschen kennenlernen und Freude teilen kann.</p><p>Die Menschen wechseln, unter den Zuhörenden wie unter den Musizierenden – es ist ein Kommen und Gehen mit immer wieder überraschenden Momenten: Kinder tanzen, staunen, bekommen ein Instrument in die Hand. Jemand fängt an zu rappen, nur für ein Lied. Eine Frau kommt dazu und singt, man begleitet sie. Alle verstehen diese Sprache – auch und besonders die Kinder.</p>',
    '<p>Symbolically and historically, the Admiralbrücke stands for overcoming divides – for encounter and understanding. Traffic-calmed, with bollards to sit on, water and evening sun, surrounded by the constant bustle of this crossing point, it has been one of the very few places in the city where you can simply linger, meet people and share joy.</p><p>People come and go, among the listeners as among the musicians – with surprising moments again and again: children dance, marvel, get handed an instrument. Someone starts rapping, just for one song. A woman joins in and sings, and the circle accompanies her. Everyone understands this language – especially the children.</p>');
setML($ort, 'features',
    'verkehrsberuhigt / wasser + abendsonne / sitzpoller / kioske, eisdiele, pizzeria / öffentliche toilette / ständiges flanieren',
    'traffic-calmed / water + evening sun / bollards to sit on / kiosks, ice cream, pizza / public toilet / constant strolling');
$ort->save();
addImage($ort, 'geige-kanal.jpg',
    'Eine Geigerin singt mit geschlossenen Augen am Ufer des Landwehrkanals',
    'A violinist sings with closed eyes on the bank of the Landwehrkanal');

// ---- D-Jam ---------------------------------------------------------------------

$djam = makePage($home, 'djam', 'djam', 'D-Jam', 'D-Jam');
setML($djam, 'heading', 'D-Jam: Vierzehn Nationen,', 'D-Jam: Fourteen nations,');
setML($djam, 'heading_accent', 'ein Rhythmus.', 'one rhythm.');
$djam->set('slogan', 'An open jam. All ages, all levels, all languages. Bring an instrument – or just yourself.');
setML($djam, 'body',
    '<p>Die D-Jam ist ein gemeinschaftliches Musizieren in moderater Lautstärke, in das alle Menschen am Ort einbezogen werden – durch Zuhören, Mitwirken, Kontakteknüpfen, Tanzen, Genießen. Musiker und Musikerinnen aus vierzehn Nationen, aus Ost und West, Nord und Süd, kommen hier regelmäßig zusammen, um Gemeinsamkeit zu erleben und Lebendigkeit in die Welt zu tragen.</p><p>Das in Zeiten des Krieges, der gesellschaftlichen Verhärtungen, der sozialen Verarmung. Es hat sich gezeigt: Musik ist unser aller Sprache, durch die Menschen in Kontakt treten können – direkt, menschlich, respektvoll.</p>',
    '<p>The D-Jam is communal music-making at moderate volume that draws in everyone around – through listening, joining in, making contact, dancing, enjoying. Musicians from fourteen nations, from East and West, North and South, come together here regularly to experience community and carry liveliness into the world.</p><p>And that in times of war, of social hardening, of social impoverishment. One thing has become clear: music is everyone\'s language, a way for people to connect – directly, humanly, respectfully.</p>');
setML($djam, 'features',
    'alle altersgruppen / moderate lautstärke / offen für alle / handgemachte musik',
    'all ages / moderate volume / open to everyone / handmade music');
$djam->save();
addImage($djam, 'come-together.jpg',
    'Große Jam-Runde auf dem Kopfsteinpflaster der Brücke, vorne ein handgemaltes Schild mit der Aufschrift Come Together',
    'Large jam circle on the cobblestones of the bridge, in front a hand-painted sign reading Come Together');
addImage($djam, 'gitarre.jpg',
    'Gitarrist im bunten Hemd, konzentriert beim Spielen',
    'Guitarist in a colourful shirt, concentrating on his playing');
addImage($djam, 'geige-piano.jpg',
    'Geigerin und Keyboarder musizieren gemeinsam am Brückengeländer',
    'Violinist and keyboard player making music together at the bridge railing');

// ---- Termine ----------------------------------------------------------------------

$termine = makePage($home, 'termine', 'termine', 'Termine', 'Dates');
setML($termine, 'heading', 'Nächste Termine', 'Upcoming dates');
setML($termine, 'body',
    '<p>Wir spielen <strong>jeden Dienstag am frühen Abend</strong> – bei brauchbarem Wetter. Jeder Abend wird als Versammlung angemeldet.</p>',
    '<p>We play <strong>every Tuesday in the early evening</strong> – weather permitting. Every evening is registered as an assembly.</p>');
$termine->save();

if ($termine->dates_list->count() === 0) {
    $statuses = ['2026-07-21' => 'angemeldet', '2026-07-28' => 'geplant',
                 '2026-08-04' => 'geplant', '2026-08-11' => 'geplant'];
    foreach ($statuses as $dateValue => $statusName) {
        $item = $termine->dates_list->getNew();
        $item->save();
        $item->of(false);
        $item->set('date', strtotime($dateValue));
        $item->set('time_text', 'ab 18 Uhr');
        setML($item, 'heading', 'D-Jam auf der Brücke', 'D-Jam on the bridge');
        $statusField = wire('fields')->get('status_option');
        $options = $statusField->type->getOptions($statusField);
        foreach ($options as $option) {
            // options defined as "id=title" leave value empty; match either
            if ($option->value === $statusName || $option->title === $statusName) {
                $item->set('status_option', $option);
                break;
            }
        }
        $item->save();
    }
    $termine->save();
    echo "  dates added\n";
}

// ---- News ------------------------------------------------------------------------

$news = makePage($home, 'news', 'news', 'News', 'News');
setML($news, 'heading', 'Neuigkeiten', 'News');
$news->save();

$post = makePage($news, 'newspost', 'website-online', 'Unsere Website ist online', 'Our website is online');
$post->of(false);
$post->set('date', strtotime('2026-07-15'));
setML($post, 'body',
    '<p>Der Kulturort Admiralbrücke hat jetzt ein Zuhause im Netz: mit der Geschichte der Brücke, den Hintergründen zur D-Jam, einem Feedback-Formular – gerade auch für Anwohnerinnen und Anwohner – und einem Newsletter für Termine und Neuigkeiten.</p>',
    '<p>The Kulturort Admiralbrücke now has a home on the web: with the history of the bridge, the background of the D-Jam, a feedback form – especially for residents – and a newsletter for dates and news.</p>');
$post->save();

// ---- Bilder -------------------------------------------------------------------------

$bilder = makePage($home, 'bilder', 'bilder', 'Bilder', 'Pictures');
setML($bilder, 'heading', 'Ein Kommen', 'A coming');
setML($bilder, 'heading_accent', 'und Gehen', 'and going');
$bilder->save();
addImage($bilder, 'session.jpg',
    'Sängerinnen und Gitarrist in einer Jam-Runde auf dem Kopfsteinpflaster der Admiralbrücke',
    'Singers and a guitarist in a jam circle on the cobblestones of the Admiralbrücke');
addImage($bilder, 'gesang.jpg',
    'Drei Frauen singen gemeinsam, dahinter das gusseiserne Brückengeländer',
    'Three women singing together, the cast-iron bridge railing behind them');
addImage($bilder, 'singen-lachen.jpg',
    'Lachende Sängerinnen mit Mikrofonen im Abendlicht',
    'Laughing singers with microphones in the evening light');
addImage($bilder, 'begegnung.jpg',
    'Junger Musiker mit Locken und Paisleyhemd im Gespräch auf der Brücke',
    'Young musician with curls and a paisley shirt in conversation on the bridge');
addImage($bilder, 'geige-kanal.jpg',
    'Geigerin singt mit ihrer Geige in der Hand am Ufer des Landwehrkanals',
    'Violinist sings, violin in hand, on the bank of the Landwehrkanal');
addImage($bilder, 'blaue-stunde.jpg',
    'Jam in der blauen Stunde unter Straßenlaternen',
    'Jam in the blue hour under street lamps');

// ---- Chronik ------------------------------------------------------------------------

$chronik = makePage($home, 'chronik', 'chronik', 'Chronik', 'History');
setML($chronik, 'heading', 'Mediation, Lärmomat, 22-Uhr-Regel:', 'Mediation, noise meter, 10 p.m. rule:');
setML($chronik, 'heading_accent', 'alles schon versucht.', 'all been tried.');
setML($chronik, 'body',
    '<p>Der Streit um die Brücke ist viel älter als die D-Jam. Seit bald zwanzig Jahren probiert die Stadt Antworten auf dieselbe Frage aus – und eine hat noch niemand versucht: den Ort als das anzuerkennen, was er längst ist. Ein Kulturort.</p>',
    '<p>The argument about this bridge is much older than the D-Jam. For almost twenty years the city has been trying answers to the same question – and one answer no one has tried yet: recognizing the place as what it has long been. A place of culture.</p>');
$chronik->save();

$chronikEntries = [
    ['1882', false,
     'Die Admiralbrücke wird fertig – eine genietete Eisenkonstruktion von Georg Pinkenburg. Heute steht sie unter Denkmalschutz.',
     'The Admiralbrücke is completed – a riveted iron arch by engineer Georg Pinkenburg. Today it is a listed monument.'],
    ['1990er', false,
     'Der Bezirk sperrt die Brücke mit Pollern für den Durchgangsverkehr. Die Verkehrsberuhigung macht sie erst zu dem Treffpunkt, der sie heute ist.',
     'The district closes the bridge to through traffic with bollards. That traffic calming is what turns it into the meeting place it is today.'],
    ['2007', false,
     'Ein rbb-Film macht die Brücke stadtbekannt; sie taucht in Reiseführern auf und wird zum Ort für den Sonnenuntergang.',
     'An rbb documentary makes the bridge famous city-wide; it shows up in travel guides and becomes the sunset spot.'],
    ['2008', false,
     'An Sommerabenden sitzen bis zu 250 Menschen auf der Brücke. Erste Beschwerden, eine Anwohnerinitiative gründet sich. Eine Anwohnerversammlung beschließt einen Kompromiss – die Verkehrsberuhigung bleibt.',
     'On summer evenings up to 250 people sit on the bridge. First complaints, a residents\' initiative forms. A residents\' assembly agrees on a compromise – the traffic calming stays.'],
    ['2010', true,
     'Senatsfinanzierte Mediatoren arbeiten direkt auf der Brücke, dreimal pro Woche. Im August eskaliert ein Einsatz: rund hundert Polizisten räumen die Brücke, tags darauf kommt der Regierende Bürgermeister persönlich vorbei. Seitdem gilt die Praxis: um 22 Uhr ist Schluss.',
     'Senate-funded mediators work directly on the bridge, three times a week. In August one operation escalates: around a hundred officers clear the bridge; the next day Berlin\'s Governing Mayor visits in person. Since then the practice stands: at 10 p.m. it\'s over.'],
    ['2023', true,
     'Der Bezirk stellt den „Lärmomat“ auf: eine Mooswand mit Lärmampel, die nachts gegen einen 55-dB(A)-Schwellwert misst und bei Überschreitung rot leuchtet. Anwohner halten ihn für überflüssig – „die Polizei kam doch sowieso schon jeden Abend“.',
     'The district installs the "Lärmomat": a moss wall with a noise traffic light, measuring nights against a 55 dB(A) threshold and glowing red when exceeded. Residents call it pointless – "the police came every evening anyway".'],
    ['2024', true,
     'Bilanz des Bezirksamts nach drei Monaten Lärmomat: 63 Stunden über dem Schwellwert, keine nachweisbare Lärmminderung. Das Projekt wird beendet, das Gerät abgebaut.',
     'The district\'s verdict after three months of Lärmomat: 63 hours above the threshold, no demonstrable noise reduction. The project is ended, the device removed.'],
    ['2026', true,
     'Jam-Sessions werden regelmäßig polizeilich beendet; im Juni wird eine Musikerin festgenommen. Die D-Jam meldet ihre Abende seither als Versammlung an – und beantragt beim Kulturausschuss die Anerkennung der Brücke als Kulturort.',
     'Jam sessions are regularly shut down by police; in June a musician is arrested. Since then the D-Jam registers its evenings as assemblies – and asks the district culture committee to recognize the bridge as a place of culture.'],
];

if ($chronik->entries_list->count() === 0) {
    foreach ($chronikEntries as [$year, $isIntervention, $textDe, $textEn]) {
        $item = $chronik->entries_list->getNew();
        $item->save();
        $item->of(false);
        $item->set('year_text', $year);
        $item->set('intervention', $isIntervention ? 1 : 0);
        setML($item, 'body_plain', $textDe, $textEn);
        $item->save();
    }
    $chronik->save();
    echo "  chronik entries added\n";
}

$chronikSources = [
    ['Wikipedia', 'https://de.wikipedia.org/wiki/Admiralbr%C3%BCcke'],
    ['taz 2010', 'https://taz.de/Mediation-auf-der-Admiralbruecke/!5139320/'],
    ['Tagesspiegel 2011', 'https://www.tagesspiegel.de/berlin/feiern-auf-der-admiralbruecke-es-ist-22-uhr-gehen-sie-bitte/4411684.html'],
    ['Berliner Zeitung 2023', 'https://www.berliner-zeitung.de/mensch-metropole/berlin-admiralbruecke-laermomat-veraergert-kreuzberger-anwohner-bunt-und-bloed-li.435401'],
    ['Bezirksamt 2024', 'https://www.berlin.de/ba-friedrichshain-kreuzberg/aktuelles/pressemitteilungen/2024/pressemitteilung.1406189.php'],
    ['Tagesspiegel 2026', 'https://www.tagesspiegel.de/berlin/bezirke/friedrichshain-kreuzberg/hobby-musikerin-bei-offentlicher-session-festgenommen-immer-wieder-streit-auf-der-berliner-admiralbrucke-15751849.html'],
];

if ($chronik->sources_list->count() === 0) {
    foreach ($chronikSources as [$title, $url]) {
        $item = $chronik->sources_list->getNew();
        $item->save();
        $item->of(false);
        setML($item, 'heading', $title, $title);
        $item->set('link_url', $url);
        $item->save();
    }
    $chronik->save();
    echo "  chronik sources added\n";
}

// ---- Aufruf -----------------------------------------------------------------------

$aufruf = makePage($home, 'aufruf', 'aufruf', 'Aufruf', 'Support');
setML($aufruf, 'heading', 'Wir wollen keinen Streit.', 'We don\'t want a fight.');
setML($aufruf, 'heading_accent', 'Wir wollen spielen.', 'We want to play.');
setML($aufruf, 'body',
    '<p>Vier bis fünf Anwohner eines benachbarten Hauses – so sagt es die Polizei – haben sich für einen Kampf gegen die Musik entschieden. Regelmäßig, oft schon am Nachmittag, wird die Polizei gerufen und eine Anzeige verlangt. Das führt zum Abbruch der Musik, manchmal zur Räumung der Brücke, zu Festnahmen und angedrohter Konfiszierung von Instrumenten.</p><p>Aus Gesprächen mit der Polizei wird deutlich: <strong>Die Lautstärke ist nicht das Problem.</strong> Die Einsatzkräfte reagieren auf die wiederholten Anrufe immer derselben wenigen Personen. Gesprächsbereitschaft, die eine friedliche Lösung erst möglich machen würde, gibt es von dieser Seite bislang nicht.</p><p>Deshalb melden wir unsere Musik inzwischen jede Woche als Versammlung an – Kultur unter Demonstrationsschutz. Die Admiralbrücke war immer ein Ort der darstellenden Kunst. Wir möchten, dass sie es bleibt: friedlich, gemeinsam, für alle.</p>',
    '<p>Four to five residents of a neighbouring building – according to the police – have decided to fight the music. Regularly, often as early as the afternoon, the police are called and criminal complaints are demanded. The music gets broken off; sometimes the bridge is cleared, people are arrested, and the confiscation of instruments is threatened.</p><p>Conversations with the police make one thing clear: <strong>the volume is not the problem.</strong> Officers are responding to repeated calls from the same few people. So far there has been no willingness on that side to talk – the very thing a peaceful solution would need.</p><p>That is why we now register our music as an assembly every week – culture under the protection of the right to demonstrate. The Admiralbrücke has always been a place of performing arts. We want it to stay that way: peaceful, together, for everyone.</p>');
$aufruf->save();

$wishes = [
    ['Einen wohlwollenden, verständnisvollen Umgang der Ordnungskräfte', 'Goodwill and understanding from the police on site'],
    ['Schutz vor willkürlicher Anzeigenerstattung', 'Protection against arbitrary criminal complaints'],
    ['Die Anerkennung der Initiative als sinnstiftend und gemeinschaftsbildend – statt ihrer Einstufung als zu ahndende Ordnungswidrigkeit', 'Recognition of the initiative as meaningful and community-building – instead of classifying it as an offence to be punished'],
    ['Vor allem: ein Gespräch. Wir sind da.', 'Above all: a conversation. We are here.'],
];
if ($aufruf->wishes_list->count() === 0) {
    foreach ($wishes as [$wishDe, $wishEn]) {
        $item = $aufruf->wishes_list->getNew();
        $item->save();
        $item->of(false);
        setML($item, 'item_text', $wishDe, $wishEn);
        $item->save();
    }
    $aufruf->save();
    echo "  wishes added\n";
}

$actions = [
    ['Komm dienstags vorbei.', 'Jede Person auf der Brücke zeigt, dass dieser Ort gebraucht wird – zuhören genügt.',
     'Come by on a Tuesday.', 'Every person on the bridge shows this place is needed – listening is enough.'],
    ['Bring ein Instrument oder deine Stimme mit.', 'Die D-Jam ist offen. Shaker, Rassel, ein Lied – alles zählt.',
     'Bring an instrument or your voice.', 'The D-Jam is open. A shaker, a rattle, one song – everything counts.'],
    ['Wende dich an die Bezirkspolitik.', 'Ein Antrag liegt dem Kulturausschuss der BVV Friedrichshain-Kreuzberg vor. Unterstützung aus der Nachbarschaft gibt ihm Gewicht.',
     'Contact district politics.', 'An application is before the culture committee of the Friedrichshain-Kreuzberg district assembly (BVV). Support from the neighbourhood gives it weight.'],
    ['Erzähl davon.', 'Teile die Geschichte der Brücke – mit Nachbarn, Freundinnen, der Presse. Öffentlichkeit schützt.',
     'Tell people about it.', 'Share the story of the bridge – with neighbours, friends, the press. Publicity protects.'],
];
if ($aufruf->actions_list->count() === 0) {
    foreach ($actions as [$titleDe, $textDe, $titleEn, $textEn]) {
        $item = $aufruf->actions_list->getNew();
        $item->save();
        $item->of(false);
        setML($item, 'heading', $titleDe, $titleEn);
        setML($item, 'body_plain', $textDe, $textEn);
        $item->save();
    }
    $aufruf->save();
    echo "  actions added\n";
}

// ---- Feedback, Zitat, Anmeldung ------------------------------------------------------

$feedback = makePage($home, 'feedback', 'feedback', 'Feedback', 'Feedback');
setML($feedback, 'heading', 'Sag uns,', 'Tell us');
setML($feedback, 'heading_accent', 'was du denkst.', 'what you think.');
setML($feedback, 'body',
    '<p>Ob Anwohnerin, Musiker, Gast oder einfach neugierig: Was gefällt dir an den Abenden auf der Brücke – und was stört dich? Gerade kritische Rückmeldungen aus der Nachbarschaft helfen uns, eine friedliche Lösung zu finden. Wir lesen alles und antworten, wenn du uns eine Adresse dalässt.</p>',
    '<p>Resident, musician, guest or just curious: what do you like about the evenings on the bridge – and what bothers you? Critical feedback from the neighbourhood especially helps us find a peaceful solution. We read everything and reply if you leave an address.</p>');
$feedback->save();

$zitat = makePage($home, 'zitat', 'zitat', 'Zitat', 'Quote');
setML($zitat, 'heading', 'Komm herunter! Mach mit! Lass uns gemeinsam das Leben feiern –', 'Come down! Join in! Let\'s celebrate life together –');
setML($zitat, 'heading_accent', 'wir haben nur dieses!', 'we only have this one!');
setML($zitat, 'byline', 'Annette Prüfer, Leserinnenbrief, Juni 2026', 'Annette Prüfer, letter to the editor, June 2026');
$zitat->save();

$anmeldung = makePage($home, 'anmeldung', 'anmeldung', 'Newsletter', 'Newsletter');
setML($anmeldung, 'heading', 'Bleib auf dem', 'Stay in');
setML($anmeldung, 'heading_accent', 'Laufenden.', 'the loop.');
setML($anmeldung, 'body',
    '<p>Jam-Termine, Neues von der Initiative, gelegentliche Einladungen – selten, aber herzlich. Mit Bestätigungslink, jederzeit abmeldbar, Adresse wird für nichts anderes verwendet.</p>',
    '<p>Jam dates, news from the initiative, occasional invitations – rare but heartfelt. Confirmation link, unsubscribe any time, your address is used for nothing else.</p>');
$anmeldung->save();

// ---- Dokumente -----------------------------------------------------------------------

$dokumente = makePage($home, 'dokumente', 'dokumente', 'Dokumente', 'Documents');
setML($dokumente, 'heading', 'Zum Nachlesen –', 'The');
setML($dokumente, 'heading_accent', 'im Wortlaut', 'full texts');
setML($dokumente, 'body',
    '<p>Der Antrag an den Kulturausschuss und der Leserinnenbrief zur Berichterstattung, ungekürzt.</p>',
    '<p>The application to the culture committee and the letter to the editor, unabridged – in the German original, each with a short English summary.</p>');
$dokumente->save();

$antrag = makePage($dokumente, 'dokument', 'antrag', 'Kulturprojekt: Admiralbrücke', 'Culture project: Admiralbrücke');
setML($antrag, 'subtitle',
    'Antrag · Kulturausschuss der BVV Friedrichshain-Kreuzberg',
    'Application · Culture committee, BVV Friedrichshain-Kreuzberg');
setML($antrag, 'summary', '',
    'Summary: The application asks the district culture committee to politically support the regular music evenings on the Admiralbrücke as a cultural project ("music as a connecting language", 14 nations, all ages, moderate volume). It describes why the bridge is uniquely suited, documents that a handful of neighbours repeatedly call the police although volume is not the issue, and asks for goodwill from the authorities, protection from arbitrary complaints, and recognition of the initiative as community-building instead of an offence. The original follows in German.');
$antragText = '<p>Antrag vor dem Kulturausschuss der BVV Friedrichshain-Kreuzberg, das im Folgenden dargestellte Projekt kulturpolitisch zu unterstützen und damit das kulturelle Leben im Bezirk zu fördern.</p><h3>Projektbeschreibung: „Musik als verbindende Sprache“</h3><p>Es handelt sich um ein regelmäßig stattfindendes kulturelles Ereignis auf der Admiralbrücke: ein gemeinschaftliches Musizieren in moderater Lautstärke, in das alle Menschen am Ort, ob jung oder alt, miteinbezogen werden. Sei es durch Zuhören, Mitwirken, Kontaktknüpfen, Tanzen, Genießen. Es hat sich gezeigt, dass Musik unser aller Sprache ist, durch welche die Menschen in Kontakt treten können, direkt, menschlich und respektvoll.</p><p>An den musikalischen Abenden kommen Menschen aller Nationen zusammen – Musiker und Musikerinnen aus Argentinien, Mexiko, der Türkei, den USA, dem Jemen, Norwegen, Panama, der Schweiz, Spanien, Chile, dem Iran, Kolumbien, dem Libanon und Deutschland. 14 Nationen aus Ost und West, Nord und Süd kommen hier regelmäßig zusammen, um Gemeinsamkeit zu erleben, Lebendigkeit und Freude in die Welt zu tragen. Das in Zeiten des Krieges, der gesellschaftlichen Verhärtungen, der sozialen Verarmung.</p><h3>Warum gerade hier, auf der Admiralbrücke?</h3><p>Die Admiralbrücke eignet sich aus vielerlei Gründen als idealer Standpunkt:</p><ul><li>Die Brücke steht sowohl sinnbildlich als auch historisch-politisch für das Überwinden von Gegensätzen, für Begegnung und Verständigung.</li><li>Die besonderen Verhältnisse durch: Verkehrsberuhigung, die vielen Sitzmöglichkeiten, das Wasser und die Abendsonne, die Nähe zu Kiosken, Eisdiele und Pizzerien, das Vorhandensein einer öffentlichen Toilette, vorhandene große Mülleimer und die regelmäßige Entsorgung von Pfandgut, das ständige Treiben und Flanieren über diesen Knotenpunkt.</li></ul><p>All das macht diesen Ort attraktiv, durchlässig und kommunikativ. Die Menschen wechseln, sowohl unter den Zuhörerinnen als auch unter den Musizierenden – es ist ein Kommen und Gehen mit immer wieder überraschenden Ereignissen: Kinder tanzen, staunen, hören gespannt zu, bekommen ein Instrument in die Hand, sind Teil des Geschehens. Plötzlich fängt jemand an zu singen, ein Rap, nur für ein Lied. Andere wieder bekommen Rhythmusinstrumente wie Shaker oder Rassel, wieder jemand beginnt einen Tanz in der Mitte, inspiriert, ungeplant. Eine Frau kommt dazu und singt, man begleitet sie. Alle diese Menschen, auch und besonders die Kinder, verstehen diese Sprache.</p><p>Die Admiralbrücke ist ein besonderer, inspirierender und seit Jahren etablierter Ort zum Verweilen, Menschenkennenlernen und Freudeteilen. Leider einer der ganz wenigen in unserer Stadt.</p><h3>Anwohnerproblematik</h3><p>Leider haben sich 4 oder 5 Anwohner eines benachbarten Hauses (so sagt es die Polizei) für einen Kampf gegen die Musiker entschieden. Regelmäßig, schon am Nachmittag oder frühen Abend, wird von dort aus die Polizei benachrichtigt und die Erstattung einer Anzeige verlangt. Dies wiederum führt zu einem Abbruch der Musik, manchmal zur Räumung der Brücke, zu Festnahmen und möglicherweise zu Geldstrafen. Auch wird regelmäßig die Konfiszierung von Instrumenten angedroht.</p><p>Aus Gesprächen mit der Polizei wird hingegen klar, dass die Lautstärke nicht das Problem darstellt. Die Ordnungskräfte reagieren lediglich auf die wiederholten Anrufe der hierfür schon bekannten Anwohnerschaft, bestehend aus immer denselben vier bis fünf Personen, die das lebendige Geschehen in der Nähe ihrer Wohnung bereits in den späten Nachmittagsstunden als gesetzwidrige Ruhestörung unterbunden haben wollen. Leider gibt es von dieser Seite keine Gesprächsbereitschaft oder Kontaktaufnahme, die eine friedliche Lösung unter den Beteiligten erst ermöglichen würde.</p><h3>Bitte an die BVV – Kulturausschuss</h3><p>Um das friedliche und lebendige Miteinander im Bezirk zu erhalten, wünschen wir uns eine größere öffentliche, auch kulturpolitische Wahrnehmung der Situation, die wir im Privaten nicht lösen können, da die Menschen, welche sich gestört fühlen (es ist auch nur eine kleine Minderheit unter den Anwohnern), nicht mit uns in Verbindung treten wollen, um eine gemeinsame Lösung zu finden.</p><p>Um die Konfrontation zwischen den Parteien zu befrieden, wünschen wir uns einerseits einen wohlwollenden und verständnisvollen Umgang durch Polizeikräfte, einen gewissen Schutz vor willkürlicher Anzeigeerstattung und politisch den Wechsel der Perspektive von der Einstufung als eine zu ahndende Ordnungswidrigkeit hin zu einer ausdrücklichen Anerkennung und Befürwortung der Initiative als sinnstiftend und gemeinschaftsbildend, als ein gelungenes Beispiel für ein lebendiges und buntes Kreuzberg, das wir uns alle wünschen.</p>';
setML($antrag, 'body', $antragText, $antragText);
$antrag->set('signature', 'Annette Prüfer · Musikerin');
$antrag->save();

$brief = makePage($dokumente, 'dokument', 'leserinnenbrief', 'Leserinnenbrief', 'Letter to the editor');
setML($brief, 'subtitle',
    'Juni 2026 · zum Tagesspiegel-Artikel von Robert Klages, 25.06.2026',
    'June 2026 · on the Tagesspiegel article by Robert Klages, 25.06.2026');
setML($brief, 'summary', '',
    'Summary: A reply to the newspaper article "Hobby musician arrested at public session". It challenges the outrage at handmade music on the bridge – noise from traffic and construction is accepted as a fact of city life, while people making music, dancing and laughing together are reported to the police – and ends with an invitation: come down and join in. The original follows in German.');
$briefText = '<p>Immer wieder löst das öffentliche Musizieren auf der Admiralbrücke eine gewaltige Entrüstung aus. Nicht nur bei einzelnen Anwohnern in bester Wohnlage, auch Leser:innen des „Tagesspiegel“ eifern in zahlreichen Kommentaren über die Unverfrorenheit von Musiker:innen, welche mit ihrem unzumutbaren „Lärm“ die ruhebedürftigen Nachbarn zu terrorisieren wagen. Handgemachte Musik wird gleichgesetzt mit Motorgeräuschen, lautstarkem Hupen, Sirenengeheul und Baustellenlärm. Nein – schlimmer! Während man das letztere in seiner Gesamtheit als unabänderliche, quasi natürliche Geräuschkulisse einer Großstadt schweigend in Kauf nimmt, ist angesichts friedlichen Musizierens die Geduld jäh am Ende: Was fällt diesen Leuten nur ein, freiheraus ihre Privatinteressen auszuleben? Dieses öffentliche Zur-Schau-Stellen? Menschengruppen zu bilden, zur Musik zu tanzen und zu lachen? Wohl gar aus allzugroßer Lebensfreude die Scham zu verlieren, die uns stets befällt, wenn wir einfach nur SIND.</p><p>Unterbunden muss es werden! Polizei! Hier ist Gefahr im Verzug! Die Gefahr nämlich, angesichts dieser freudvollen Menschen die eigene Traurigkeit zu empfinden. Ein nicht gelebtes Leben, unfähig, sich aus selbstauferlegten Fesseln zu befreien. Man möchte rufen: „Komm herunter! Mach mit! Lass uns gemeinsam das Leben feiern – Wir haben nur dieses!“</p><p>So einfach könnte es sein.</p>';
setML($brief, 'body', $briefText, $briefText);
$brief->set('signature', 'Annette Prüfer');
$brief->save();

// ---- Section order --------------------------------------------------------------------

$order = ['ort', 'djam', 'termine', 'news', 'bilder', 'chronik', 'aufruf', 'feedback', 'zitat', 'dokumente', 'anmeldung'];
foreach ($order as $index => $name) {
    $page = $home->child("name=$name, include=all");
    if ($page->id) {
        $page->of(false);
        $page->sort = $index;
        $page->save('sort');
    }
}

echo "content done\n";
