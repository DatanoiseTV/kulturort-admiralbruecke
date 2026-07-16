<?php namespace ProcessWire; ?>
<section class="abschnitt dokumente" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
    <div class="dokumente__intro"><?= $section->body ?></div>
    <?php foreach ($section->children() as $document): ?>
    <details class="dokument">
      <summary>
        <span class="dokument__titel"><?= $document->title ?></span>
        <span class="eyebrow"><?= $document->subtitle ?></span>
      </summary>
      <div class="dokument__inhalt">
        <?php if (!isGerman() && $document->summary): ?>
        <p class="dokument__zusammenfassung"><?= $document->summary ?></p>
        <?php endif ?>
        <?= $document->body ?>
        <?php if ($document->signature): ?>
        <p class="unterschrift"><?= $document->signature ?></p>
        <?php endif ?>
      </div>
    </details>
    <?php endforeach ?>
  </div>
</section>
