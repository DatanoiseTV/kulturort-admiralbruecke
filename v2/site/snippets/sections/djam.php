<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> djam" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
    <div class="djam__raster">
      <div class="djam__text">
        <?php if ($section->slogan()->isNotEmpty()): ?>
        <p class="djam__englisch" lang="en"><?= $section->slogan()->esc() ?></p>
        <?php endif ?>
        <?= $section->text() ?>
        <?php if ($section->features()->isNotEmpty()): ?>
        <p class="merkmale"><?= $section->features()->esc() ?></p>
        <?php endif ?>
      </div>
      <div class="djam__bilder">
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
  </div>
</section>
