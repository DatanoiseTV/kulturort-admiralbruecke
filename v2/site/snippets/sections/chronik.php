<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> chronik" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
    <div class="chronik__intro"><?= $section->intro() ?></div>
    <ol class="zeitleiste">
      <?php foreach ($section->entries()->toStructure() as $entry): ?>
      <li <?= $entry->intervention()->toBool() ? 'class="zeitleiste__eingriff"' : '' ?>>
        <span class="zeitleiste__jahr"><?= $entry->year()->esc() ?></span>
        <div class="zeitleiste__text"><?= $entry->text()->kirbytextinline() ?></div>
      </li>
      <?php endforeach ?>
    </ol>
    <?php $sources = $section->sources()->toStructure(); if ($sources->count() > 0): ?>
    <p class="chronik__quellen">
      <?= $isGerman ? 'Quellen:' : 'Sources:' ?>
      <?php foreach ($sources as $index => $source): ?>
      <a href="<?= $source->url()->esc() ?>" rel="noopener"><?= $source->title()->esc() ?></a><?= $index < $sources->count() - 1 ? ' · ' : '' ?>
      <?php endforeach ?>
    </p>
    <?php endif ?>
  </div>
</section>
