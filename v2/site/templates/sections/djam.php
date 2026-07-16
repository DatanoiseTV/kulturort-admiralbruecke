<?php namespace ProcessWire; ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> djam" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
    <div class="djam__raster">
      <div class="djam__text">
        <?php if ($section->slogan): ?>
        <p class="djam__englisch" lang="en"><?= $section->slogan ?></p>
        <?php endif ?>
        <?= $section->body ?>
        <?php if ($section->features): ?>
        <p class="merkmale"><?= $section->features ?></p>
        <?php endif ?>
      </div>
      <div class="djam__bilder">
        <?php foreach ($section->images as $image): ?>
        <figure class="foto">
          <img src="<?= $image->width(1600)->url ?>" alt="<?= $image->description ?>"
               width="<?= $image->width(1600)->width ?>" height="<?= $image->width(1600)->height ?>" loading="lazy">
        </figure>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</section>
