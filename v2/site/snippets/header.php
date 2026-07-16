<?php
/** @var Kirby\Cms\Page $page */
$isGerman = $kirby->language()->code() === 'de';
$home = $site->homePage();
$otherLanguage = $isGerman ? $kirby->language('en') : $kirby->language('de');
// Nav derives from the listed section pages; titles are translatable content.
$navSections = $home->children()->listed();
?>
<!DOCTYPE html>
<html lang="<?= $kirby->language()->code() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $site->title()->esc() ?> – <?= $isGerman ? 'D-Jam & Musik in Berlin-Kreuzberg' : 'D-Jam & music in Berlin-Kreuzberg' ?></title>
<meta name="description" content="<?= $site->description()->esc() ?>">
<link rel="canonical" href="<?= $page->url() ?>">
<meta name="theme-color" content="#0b0b0f">
<?php foreach ($kirby->languages() as $language): ?>
<link rel="alternate" hreflang="<?= $language->code() ?>" href="<?= $page->url($language->code()) ?>">
<?php endforeach ?>
<meta property="og:title" content="<?= $site->title()->esc() ?>">
<meta property="og:description" content="<?= $site->description()->esc() ?>">
<?php if ($heroImage = $home->images()->first()): ?>
<meta property="og:image" content="<?= $heroImage->resize(1600)->url() ?>">
<?php endif ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $site->title()->esc() ?>">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%230B0B0F'/%3E%3Cpath d='M12 46 Q32 28 52 46' stroke='%23D9FF4B' stroke-width='5' fill='none'/%3E%3Crect x='10' y='44' width='5' height='9' fill='%238B5CF6'/%3E%3Crect x='49' y='44' width='5' height='9' fill='%238B5CF6'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<?= css('assets/css/style.css?v=1') ?>
</head>
<body>

<header class="kopf">
  <a class="kopf__marke" href="<?= $home->url() ?>"><?= $site->title()->esc() ?></a>
  <nav class="kopf__nav" aria-label="<?= $isGerman ? 'Hauptnavigation' : 'Main navigation' ?>">
    <?php foreach ($navSections as $navSection): ?>
    <a href="<?= $home->url() ?>#<?= $navSection->slug() ?>"><?= $navSection->title()->esc() ?></a>
    <?php endforeach ?>
    <a class="kopf__lang" href="<?= $page->url($otherLanguage->code()) ?>"><?= $isGerman ? 'EN' : 'DE' ?></a>
  </nav>
</header>
