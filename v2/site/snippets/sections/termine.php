<?php
/** @var Kirby\Cms\Page $section */
// Dates are stored as ISO strings (YYYY-MM-DD), so plain string compare works.
$today = date('Y-m-d');
$upcoming = $section->dates()->toStructure()->filter(
    fn ($entry) => $entry->date()->value() >= $today
);
$statusLabels = [
    'geplant'    => $isGerman ? 'geplant' : 'planned',
    'angemeldet' => $isGerman ? 'als Versammlung angemeldet' : 'registered as assembly',
    'abgesagt'   => $isGerman ? 'abgesagt' : 'cancelled',
];
?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> termine" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?></h2>
    <?php if ($section->note()->isNotEmpty()): ?>
    <div class="termine__hinweis"><?= $section->note() ?></div>
    <?php endif ?>
    <?php if ($upcoming->count() === 0): ?>
    <p class="termine__leer"><?= $isGerman
        ? 'Gerade sind keine Termine eingetragen – dienstags lohnt sich trotzdem immer.'
        : 'No dates listed right now – Tuesdays are always worth a try anyway.' ?></p>
    <?php else: ?>
    <ol class="termine__liste">
      <?php foreach ($upcoming as $entry): ?>
      <li class="termine__eintrag <?= $entry->status()->value() === 'abgesagt' ? 'termine__eintrag--abgesagt' : '' ?>">
        <time datetime="<?= $entry->date()->value() ?>" class="termine__datum">
          <span class="termine__tag"><?= $entry->date()->toDate('EEE') ?></span>
          <?= $entry->date()->toDate('dd.MM.') ?>
        </time>
        <div class="termine__text">
          <strong><?= $entry->title()->esc() ?></strong>
          <span><?= $entry->time()->esc() ?><?= $entry->note()->isNotEmpty() ? ' · ' . $entry->note()->esc() : '' ?></span>
        </div>
        <span class="termine__status termine__status--<?= $entry->status()->esc() ?>">
          <?= $statusLabels[$entry->status()->value()] ?? $entry->status()->esc() ?>
        </span>
      </li>
      <?php endforeach ?>
    </ol>
    <?php endif ?>
  </div>
</section>
