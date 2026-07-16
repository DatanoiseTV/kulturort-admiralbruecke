<?php namespace ProcessWire; ?>
<section class="abschnitt aufruf" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <div class="aufruf__block">
      <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
      <div class="aufruf__raster">
        <div class="aufruf__lage">
          <?= $section->body ?>
          <div class="aufruf__wunsch">
            <h3><?= t('Was wir uns wünschen', 'What we ask for') ?></h3>
            <ul>
              <?php foreach ($section->wishes_list as $wish): ?>
              <li><?= $wish->item_text ?></li>
              <?php endforeach ?>
            </ul>
          </div>
        </div>
        <div>
          <h3><?= t('Was du tun kannst', 'What you can do') ?></h3>
          <ol class="mitmachen">
            <?php foreach ($section->actions_list as $action): ?>
            <li>
              <strong><?= $action->heading ?></strong>
              <span><?= $action->body_plain ?></span>
            </li>
            <?php endforeach ?>
          </ol>
        </div>
      </div>
    </div>
  </div>
</section>
