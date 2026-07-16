<?php

namespace ProcessWire;

/**
 * Schema setup: languages, fields, templates, family rules.
 * Run on the server: php setup/01-schema.php (from the PW root).
 */

include __DIR__ . '/../index.php';

// CLI bootstrap starts as guest; module installs need superuser rights
wire('users')->setCurrentUser(wire('users')->get('djam'));

$modules   = wire('modules');
$fields    = wire('fields');
$templates = wire('templates');
$languages = wire('languages');

// ---- 1. Language support --------------------------------------------------

foreach (['LanguageSupport', 'LanguageSupportFields', 'LanguageSupportPageNames', 'LanguageTabs'] as $name) {
    if (!$modules->isInstalled($name)) {
        $modules->install($name);
        echo "module: $name installed\n";
    }
}
$modules->refresh();
$languages = wire('languages'); // reload after install

$default = $languages->get('default');
if ($default->title != 'Deutsch') {
    $default->of(false);
    $default->title = 'Deutsch';
    $default->save();
}
if (!$languages->get('en')->id) {
    $en = $languages->add('en');
    $en->of(false);
    $en->title = 'English';
    $en->save();
    echo "language: en created\n";
}

// ---- 2. Fields --------------------------------------------------------------

function makeField(string $name, string $type, string $label, array $options = []): Field
{
    $fields = wire('fields');
    $field = $fields->get($name);
    if ($field) {
        return $field;
    }
    $field = new Field();
    $field->type = wire('modules')->get($type);
    $field->name = $name;
    $field->label = $label;
    foreach ($options as $key => $value) {
        $field->set($key, $value);
    }
    $field->save();
    echo "field: $name\n";
    return $field;
}

makeField('heading', 'FieldtypeTextLanguage', 'Überschrift');
makeField('heading_accent', 'FieldtypeTextLanguage', 'Betonter Teil der Überschrift');
makeField('body', 'FieldtypeTextareaLanguage', 'Text', [
    'contentType' => 1,
    'inputfieldClass' => 'InputfieldCKEditor',
    'rows' => 10,
]);
makeField('features', 'FieldtypeTextLanguage', 'Merkmalszeile (Stichworte mit / getrennt)');
makeField('slogan', 'FieldtypeText', 'Englischer Slogan');
makeField('notice', 'FieldtypeTextLanguage', 'Amtlicher Vermerk');
makeField('ticker', 'FieldtypeTextLanguage', 'Laufband-Text');
makeField('byline', 'FieldtypeTextLanguage', 'Quelle / Zuschreibung');
makeField('subtitle', 'FieldtypeTextLanguage', 'Untertitel');
makeField('summary', 'FieldtypeTextareaLanguage', 'Kurzfassung (nur englische Ausgabe)', ['rows' => 5]);
makeField('signature', 'FieldtypeText', 'Unterschrift');
makeField('date', 'FieldtypeDatetime', 'Datum', [
    'dateInputFormat' => 'd.m.Y',
    'datepicker' => 3,
    'defaultToday' => 1,
]);
makeField('time_text', 'FieldtypeText', 'Uhrzeit');
makeField('year_text', 'FieldtypeText', 'Jahr');
makeField('intervention', 'FieldtypeCheckbox', 'Behördlicher Eingriff? (gelb markiert)');
makeField('link_url', 'FieldtypeURL', 'Link');
makeField('item_text', 'FieldtypeTextLanguage', 'Punkt');
makeField('body_plain', 'FieldtypeTextareaLanguage', 'Text (ohne Formatierung)', ['rows' => 4]);
makeField('images', 'FieldtypeImage', 'Bilder', [
    'extensions' => 'jpg jpeg png webp',
    'descriptionRows' => 1,
    'outputFormat' => 0,
]);

// Status als Auswahlfeld
if (!$fields->get('status_option')) {
    $field = new Field();
    $field->type = wire('modules')->get('FieldtypeOptions');
    $field->name = 'status_option';
    $field->label = 'Status';
    $field->save();
    $manager = new SelectableOptionManager();
    $manager->setOptionsString($field, "1=geplant\n2=angemeldet|als Versammlung angemeldet\n3=abgesagt", false);
    $field->save();
    echo "field: status_option\n";
}

// ---- 3. Repeater fields -------------------------------------------------------

if (!$modules->isInstalled('FieldtypeRepeater')) {
    $modules->install('FieldtypeRepeater');
    echo "module: FieldtypeRepeater\n";
}
$modules->refresh();

function makeRepeater(string $name, string $label, array $subfieldNames): Field
{
    $fields = wire('fields');
    $field = $fields->get($name);
    if (!$field) {
        $field = new Field();
        $field->type = wire('modules')->get('FieldtypeRepeater');
        $field->name = $name;
        $field->label = $label;
        $field->save();
        echo "repeater created: $name\n";
    }

    // The repeater's backing template is created on field save but may not
    // be visible in this process yet; configure on rerun in that case.
    $template = wire('templates')->get('repeater_' . $name);
    if (!$template || !$template->fieldgroup) {
        echo "repeater template for $name not visible yet - rerun this script\n";
        return $field;
    }
    $fieldgroup = $template->fieldgroup;
    foreach ($subfieldNames as $subfieldName) {
        if (!$fieldgroup->hasField($fields->get($subfieldName))) {
            $fieldgroup->add($fields->get($subfieldName));
        }
    }
    $fieldgroup->save();
    $field->set('repeaterFields', array_map(
        fn ($n) => wire('fields')->get($n)->id,
        $subfieldNames
    ));
    $field->save();
    echo "repeater configured: $name\n";
    return $field;
}

makeRepeater('dates_list', 'Termine', ['date', 'time_text', 'heading', 'status_option', 'item_text']);
makeRepeater('entries_list', 'Chronik-Einträge', ['year_text', 'intervention', 'body_plain']);
makeRepeater('sources_list', 'Quellen', ['heading', 'link_url']);
makeRepeater('wishes_list', 'Was wir uns wünschen', ['item_text']);
makeRepeater('actions_list', 'Was du tun kannst', ['heading', 'body_plain']);

// ---- 4. Templates ---------------------------------------------------------------

function makeTemplate(string $name, string $label, array $fieldNames): Template
{
    $templates = wire('templates');
    $existing = $templates->get($name);
    if ($existing) {
        return $existing;
    }
    $fieldgroup = new Fieldgroup();
    $fieldgroup->name = $name;
    $fieldgroup->add(wire('fields')->get('title'));
    foreach ($fieldNames as $fieldName) {
        $fieldgroup->add(wire('fields')->get($fieldName));
    }
    $fieldgroup->save();

    $template = new Template();
    $template->name = $name;
    $template->label = $label;
    $template->fieldgroup = $fieldgroup;
    $template->save();
    echo "template: $name\n";
    return $template;
}

makeTemplate('ort', 'Der Ort', ['heading', 'heading_accent', 'body', 'features', 'images']);
makeTemplate('djam', 'D-Jam', ['heading', 'heading_accent', 'slogan', 'body', 'features', 'images']);
makeTemplate('bilder', 'Bildergalerie', ['heading', 'heading_accent', 'images']);
makeTemplate('termine', 'Termine', ['heading', 'body', 'dates_list']);
makeTemplate('news', 'News', ['heading']);
makeTemplate('newspost', 'News-Beitrag', ['date', 'body']);
makeTemplate('chronik', 'Chronik', ['heading', 'heading_accent', 'body', 'entries_list', 'sources_list']);
makeTemplate('aufruf', 'Aufruf', ['heading', 'heading_accent', 'body', 'wishes_list', 'actions_list']);
makeTemplate('zitat', 'Zitat', ['heading', 'heading_accent', 'byline']);
makeTemplate('dokumente', 'Dokumente', ['heading', 'heading_accent', 'body']);
makeTemplate('dokument', 'Dokument', ['subtitle', 'summary', 'body', 'signature']);
makeTemplate('feedback', 'Feedback-Formular', ['heading', 'heading_accent', 'body']);
makeTemplate('anmeldung', 'Newsletter-Anmeldung', ['heading', 'heading_accent', 'body']);
makeTemplate('textsection', 'Freier Abschnitt', ['heading', 'heading_accent', 'body', 'images']);

// Home template gets hero fields
$home = wire('templates')->get('home');
foreach (['body', 'notice', 'ticker', 'images'] as $fieldName) {
    if (!$home->fieldgroup->hasField($fieldName)) {
        $home->fieldgroup->add(wire('fields')->get($fieldName));
    }
}
$home->fieldgroup->save();

// ---- 5. Family rules ---------------------------------------------------------------

$sectionTemplates = ['ort', 'djam', 'bilder', 'termine', 'news', 'chronik', 'aufruf',
                     'zitat', 'dokumente', 'feedback', 'anmeldung', 'textsection'];

$home = wire('templates')->get('home');
$home->childTemplates = array_map(fn ($n) => wire('templates')->get($n)->id, $sectionTemplates);
$home->save();

foreach ($sectionTemplates as $name) {
    $template = wire('templates')->get($name);
    $template->parentTemplates = [$home->id];
    if ($name === 'news') {
        $template->childTemplates = [wire('templates')->get('newspost')->id];
    } elseif ($name === 'dokumente') {
        $template->childTemplates = [wire('templates')->get('dokument')->id];
    } else {
        $template->noChildren = 1;
    }
    $template->save();
}
foreach (['newspost' => 'news', 'dokument' => 'dokumente'] as $child => $parent) {
    $template = wire('templates')->get($child);
    $template->parentTemplates = [wire('templates')->get($parent)->id];
    $template->noChildren = 1;
    $template->save();
}

echo "schema done\n";
