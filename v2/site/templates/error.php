<?php
/** @var Kirby\Cms\Page $page */
$isGerman = $kirby->language()->code() === 'de';
snippet('header');
?>
<main>
  <section class="abschnitt">
    <div class="abschnitt__innen">
      <h2><?= $isGerman ? 'Seite nicht gefunden' : 'Page not found' ?></h2>
      <p><a href="<?= $site->url() ?>"><?= $isGerman ? 'Zur Startseite' : 'Back to the start page' ?></a></p>
    </div>
  </section>
</main>
<?php snippet('footer') ?>
