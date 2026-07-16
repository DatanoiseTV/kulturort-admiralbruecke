<?php
/** @var Kirby\Cms\Page $section */
$image = $section->images()->first();
?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> ort" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
    <div class="ort__raster">
      <div class="ort__text">
        <?= $section->text() ?>
        <?php if ($section->features()->isNotEmpty()): ?>
        <p class="merkmale"><?= $section->features()->esc() ?></p>
        <?php endif ?>
      </div>
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
