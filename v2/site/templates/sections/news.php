<?php namespace ProcessWire; ?>
<section class="abschnitt <?= $alignRight ? 'abschnitt--rechts' : '' ?> news" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?></h2>
    <div class="news__liste">
      <?php foreach ($section->children('sort=-date') as $post):
        $timestamp = (int)$post->getUnformatted('date'); ?>
      <article class="news__beitrag">
        <time datetime="<?= date('Y-m-d', $timestamp) ?>" class="news__datum eyebrow">
          <?= date('d.m.Y', $timestamp) ?>
        </time>
        <h3><?= $post->title ?></h3>
        <div class="news__text"><?= $post->body ?></div>
      </article>
      <?php endforeach ?>
    </div>
  </div>
</section>
