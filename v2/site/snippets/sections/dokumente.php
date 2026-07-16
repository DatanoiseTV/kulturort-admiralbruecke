<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt dokumente" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
    <div class="dokumente__intro"><?= $section->intro() ?></div>
    <?php foreach ($section->children()->listed() as $document): ?>
    <details class="dokument">
      <summary>
        <span class="dokument__titel"><?= $document->title()->esc() ?></span>
        <span class="eyebrow"><?= $document->subtitle_line()->esc() ?></span>
      </summary>
      <div class="dokument__inhalt">
        <?php if (!$isGerman && $document->summary()->isNotEmpty()): ?>
        <p class="dokument__zusammenfassung"><?= $document->summary()->esc() ?></p>
        <?php endif ?>
        <?= $document->text() ?>
        <?php if ($document->signature()->isNotEmpty()): ?>
        <p class="unterschrift"><?= $document->signature()->esc() ?></p>
        <?php endif ?>
      </div>
    </details>
    <?php endforeach ?>
  </div>
</section>
