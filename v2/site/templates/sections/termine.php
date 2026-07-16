<?php

namespace ProcessWire;

$today = strtotime('today');
$upcoming = [];
foreach ($section->dates_list as $entry) {
    $timestamp = (int)$entry->getUnformatted('date');
    if ($timestamp >= $today) {
        $upcoming[] = $entry;
    }
}
?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> termine" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?></h2>
    <?php if ($section->body): ?>
    <div class="termine__hinweis"><?= $section->body ?></div>
    <?php endif ?>
    <?php if (count($upcoming) === 0): ?>
    <p class="termine__leer"><?= t(
        'Gerade sind keine Termine eingetragen – dienstags lohnt sich trotzdem immer.',
        'No dates listed right now – Tuesdays are always worth a try anyway.') ?></p>
    <?php else: ?>
    <ol class="termine__liste">
      <?php foreach ($upcoming as $entry):
        $timestamp = (int)$entry->getUnformatted('date');
        $option = $entry->status_option->first();
        $status = $option ? ((string)$option->value !== '' ? $option->value : $option->title) : 'geplant';
        $status = $status === 'als Versammlung angemeldet' ? 'angemeldet' : $status;
      ?>
      <li class="termine__eintrag <?= $status === 'abgesagt' ? 'termine__eintrag--abgesagt' : '' ?>">
        <time datetime="<?= date('Y-m-d', $timestamp) ?>" class="termine__datum">
          <span class="termine__tag"><?= weekdayShort($timestamp) ?></span>
          <?= date('d.m.', $timestamp) ?>
        </time>
        <div class="termine__text">
          <strong><?= $entry->heading ?></strong>
          <span><?= $entry->time_text ?><?= $entry->item_text ? ' · ' . $entry->item_text : '' ?></span>
        </div>
        <span class="termine__status termine__status--<?= $status ?>"><?= statusLabel($status) ?></span>
      </li>
      <?php endforeach ?>
    </ol>
    <?php endif ?>
  </div>
</section>
