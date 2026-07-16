<?php namespace ProcessWire; ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> bilder" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
    <div class="bilder__raster">
      <?php foreach ($section->images as $image): ?>
      <figure class="foto">
        <img src="<?= $image->width(1600)->url ?>" alt="<?= $image->description ?>"
             width="<?= $image->width(1600)->width ?>" height="<?= $image->width(1600)->height ?>" loading="lazy">
      </figure>
      <?php endforeach ?>
    </div>
  </div>
</section>
