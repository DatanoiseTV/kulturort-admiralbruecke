<?php namespace ProcessWire; $image = $section->images->first(); ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> ort" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
    <div class="ort__raster">
      <div class="ort__text">
        <?= $section->body ?>
        <?php if ($section->features): ?>
        <p class="merkmale"><?= $section->features ?></p>
        <?php endif ?>
      </div>
      <?php if ($image): ?>
      <figure class="foto ort__bild">
        <img src="<?= $image->width(1600)->url ?>" alt="<?= $image->description ?>"
             width="<?= $image->width(1600)->width ?>" height="<?= $image->width(1600)->height ?>" loading="lazy">
      </figure>
      <?php endif ?>
    </div>
  </div>
</section>
