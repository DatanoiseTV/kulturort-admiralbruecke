<?php
/** @var Kirby\Cms\Page $page */
$isGerman = $kirby->language()->code() === 'de';
snippet('header');
$heroImage = $page->images()->first();
?>

<main>

  <section class="plakat">
    <?php if ($heroImage): ?>
    <div class="plakat__bild foto">
      <img src="<?= $heroImage->resize(2000)->url() ?>"
           srcset="<?= $heroImage->srcset('standard') ?>"
           alt="<?= $heroImage->alt()->or($isGerman ? 'Jam-Session auf der Admiralbrücke' : 'Jam session on the Admiralbrücke')->esc() ?>"
           width="<?= $heroImage->resize(2000)->width() ?>" height="<?= $heroImage->resize(2000)->height() ?>"
           fetchpriority="high">
    </div>
    <?php endif ?>
    <div class="plakat__inhalt">
      <p class="plakat__eyebrow eyebrow">Berlin-Kreuzberg · Landwehrkanal</p>
      <h1 class="plakat__titel">
        <span class="wort wort--kultur">Kultur</span>
        <span class="wort wort--ort">Ort</span>
        <span class="wort wort--bruecke">Admiralbrücke</span>
      </h1>
      <div class="plakat__unterzeile"><?= $page->intro() ?></div>
      <?php if ($page->notice()->isNotEmpty()): ?>
      <p class="plakat__vermerk">[ <?= $page->notice()->esc() ?> ]</p>
      <?php endif ?>
    </div>
    <?php if ($page->ticker()->isNotEmpty()): ?>
    <div class="ticker ticker--acid" aria-hidden="true">
      <div class="ticker__spur">
        <span><?= $page->ticker()->esc() ?> — </span>
        <span><?= $page->ticker()->esc() ?> — </span>
      </div>
    </div>
    <?php endif ?>
  </section>

  <?php
  // Data-driven one-pager: every listed child renders through its
  // section snippet; unknown templates are skipped gracefully.
  $number = 0;
  foreach ($page->children()->listed() as $section) {
      $template = $section->intendedTemplate()->name();
      $snippetFile = $kirby->root('snippets') . '/sections/' . $template . '.php';
      if (!is_file($snippetFile)) {
          continue;
      }
      $number++;
      snippet('sections/' . $template, [
          'section'   => $section,
          'number'    => str_pad((string)$number, 2, '0', STR_PAD_LEFT),
          'alignRight' => $number % 2 === 0,
          'isGerman'  => $isGerman,
      ]);
  }
  ?>

</main>

<?php snippet('footer') ?>
