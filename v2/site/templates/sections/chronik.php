<?php namespace ProcessWire; ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> chronik" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
    <div class="chronik__intro"><?= $section->body ?></div>
    <ol class="zeitleiste">
      <?php foreach ($section->entries_list as $entry): ?>
      <li <?= $entry->intervention ? 'class="zeitleiste__eingriff"' : '' ?>>
        <span class="zeitleiste__jahr"><?= $entry->year_text ?></span>
        <div class="zeitleiste__text"><?= $entry->body_plain ?></div>
      </li>
      <?php endforeach ?>
    </ol>
    <?php if ($section->sources_list->count()): ?>
    <p class="chronik__quellen">
      <?= t('Quellen:', 'Sources:') ?>
      <?php $links = [];
      foreach ($section->sources_list as $source) {
          $links[] = '<a href="' . $source->link_url . '" rel="noopener">' . $source->heading . '</a>';
      }
      echo implode(' · ', $links); ?>
    </p>
    <?php endif ?>
  </div>
</section>
