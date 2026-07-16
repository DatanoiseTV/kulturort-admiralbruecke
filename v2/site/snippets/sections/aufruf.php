<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt aufruf" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <div class="aufruf__block">
      <h2><?= $section->heading()->esc() ?> <em><?= $section->heading_accent()->esc() ?></em></h2>
      <div class="aufruf__raster">
        <div class="aufruf__lage">
          <?= $section->text() ?>
          <div class="aufruf__wunsch">
            <h3><?= $section->wishes_title()->esc() ?></h3>
            <ul>
              <?php foreach ($section->wishes()->toStructure() as $wish): ?>
              <li><?= $wish->item()->esc() ?></li>
              <?php endforeach ?>
            </ul>
          </div>
        </div>
        <div>
          <h3><?= $section->actions_title()->esc() ?></h3>
          <ol class="mitmachen">
            <?php foreach ($section->actions()->toStructure() as $action): ?>
            <li>
              <strong><?= $action->title()->esc() ?></strong>
              <span><?= $action->text()->esc() ?></span>
            </li>
            <?php endforeach ?>
          </ol>
        </div>
      </div>
    </div>
  </div>
</section>
