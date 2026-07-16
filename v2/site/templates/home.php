<?php

namespace ProcessWire;

/** @var Page $page */

include_once __DIR__ . '/_functions.php';
include __DIR__ . '/_header.php';

$heroImage = $page->images->first();
?>

<main>

  <section class="plakat">
    <?php if ($heroImage): ?>
    <div class="plakat__bild foto">
      <img src="<?= $heroImage->width(2000)->url ?>"
           alt="<?= $heroImage->description ?>"
           width="<?= $heroImage->width(2000)->width ?>" height="<?= $heroImage->width(2000)->height ?>"
           fetchpriority="high">
    </div>
    <?php endif ?>
    <div class="plakat__inhalt">
      <p class="plakat__eyebrow eyebrow">Berlin-Kreuzberg · Landwehrkanal</p>
      <h1 class="plakat__titel">
        <span class="wort wort--kultur">Kultur</span>
        <span class="wort wort--ort">Ort</span>
        <span class="wort wort--bruecke">Admiralbrücke</span>
      </h1>
      <div class="plakat__unterzeile"><?= $page->body ?></div>
      <?php if ($page->notice): ?>
      <p class="plakat__vermerk">[ <?= $page->notice ?> ]</p>
      <?php endif ?>
    </div>
    <?php if ($page->ticker): ?>
    <div class="ticker ticker--acid" aria-hidden="true">
      <div class="ticker__spur">
        <span><?= $page->ticker ?> — </span>
        <span><?= $page->ticker ?> — </span>
      </div>
    </div>
    <?php endif ?>
  </section>

  <?php
  // Data-driven one-pager: each visible child renders via its section include.
  $number = 0;
  foreach ($page->children() as $section) {
      $includeFile = __DIR__ . '/sections/' . $section->template->name . '.php';
      if (!is_file($includeFile)) {
          continue;
      }
      $number++;
      $sectionNumber = str_pad((string)$number, 2, '0', STR_PAD_LEFT);
      $alignRight = $number % 2 === 0;
      include $includeFile;
  }
  ?>

</main>

<?php include __DIR__ . '/_footer.php'; ?>
