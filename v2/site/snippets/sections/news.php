<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> news" id="<?= $section->slug() ?>" data-nr="<?= $number ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading()->esc() ?></h2>
    <div class="news__liste">
      <?php foreach ($section->children()->listed()->sortBy('date', 'desc') as $post): ?>
      <article class="news__beitrag">
        <time datetime="<?= $post->date()->value() ?>" class="news__datum eyebrow">
          <?= $post->date()->toDate('dd.MM.yyyy') ?>
        </time>
        <h3><?= $post->title()->esc() ?></h3>
        <div class="news__text"><?= $post->text() ?></div>
      </article>
      <?php endforeach ?>
    </div>
  </div>
</section>
