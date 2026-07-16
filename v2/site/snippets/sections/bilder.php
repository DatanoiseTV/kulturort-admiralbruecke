<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> bilder" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
    <div class="bilder__raster">
      <?php foreach ($section->images()->sorted() as $image): ?>
      <figure class="foto">
        <img src="<?= $image->resize(1600)->url() ?>"
             srcset="<?= $image->srcset('standard') ?>"
             alt="<?= $image->alt()->esc() ?>"
             width="<?= $image->resize(1600)->width() ?>" height="<?= $image->resize(1600)->height() ?>"
             loading="lazy">
      </figure>
      <?php endforeach ?>
    </div>
  </div>
</section>
