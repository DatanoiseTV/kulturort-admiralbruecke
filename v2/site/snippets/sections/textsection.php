<?php
/** @var Kirby\Cms\Page $section */
$image = $section->images()->first();
?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> textabschnitt" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
    <div class="<?= $image ? 'ort__raster' : '' ?>">
      <div class="ort__text"><?= $section->text() ?></div>
      <?php if ($image): ?>
      <figure class="foto ort__bild">
        <img src="<?= $image->resize(1600)->url() ?>"
             srcset="<?= $image->srcset('standard') ?>"
             alt="<?= $image->alt()->esc() ?>"
             width="<?= $image->resize(1600)->width() ?>" height="<?= $image->resize(1600)->height() ?>"
             loading="lazy">
      </figure>
      <?php endif ?>
    </div>
  </div>
</section>
