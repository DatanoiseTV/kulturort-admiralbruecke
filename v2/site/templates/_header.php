<?php

namespace ProcessWire;

/** @var Page $page */
$homepage = pages('/');
$languages = wire('languages');
$currentLanguage = wire('user')->language;
$otherLanguage = $currentLanguage->isDefault() ? $languages->get('en') : $languages->getDefault();
$navSections = $homepage->children();
$heroImage = $homepage->images->first();
?><!DOCTYPE html>
<html lang="<?= isGerman() ? 'de' : 'en' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= wire('sanitizer')->entities1('Kulturort Admiralbrücke') ?> – <?= t('D-Jam & Musik in Berlin-Kreuzberg', 'D-Jam & music in Berlin-Kreuzberg') ?></title>
<meta name="description" content="<?= t(
    'Die D-Jam auf der Admiralbrücke in Berlin-Kreuzberg: offene Jam-Session aus 14 Nationen, jeden Dienstag am Landwehrkanal. Zuhören, mitspielen, unterstützen.',
    'The D-Jam on the Admiralbrücke in Berlin-Kreuzberg: an open jam session of 14 nations, every Tuesday by the Landwehrkanal. Listen, play along, support.'
) ?>">
<link rel="canonical" href="<?= $page->httpUrl() ?>">
<!-- Staging: remove noindex when v2 replaces the main site -->
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0b0b0f">
<?php foreach ($languages as $language): ?>
<link rel="alternate" hreflang="<?= $language->isDefault() ? 'de' : $language->name ?>" href="<?= $page->localHttpUrl($language) ?>">
<?php endforeach ?>
<meta property="og:title" content="Kulturort Admiralbrücke">
<?php if ($heroImage): ?>
<meta property="og:image" content="<?= $heroImage->width(1600)->httpUrl() ?>">
<?php endif ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="Kulturort Admiralbrücke">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%230B0B0F'/%3E%3Cpath d='M12 46 Q32 28 52 46' stroke='%23D9FF4B' stroke-width='5' fill='none'/%3E%3Crect x='10' y='44' width='5' height='9' fill='%238B5CF6'/%3E%3Crect x='49' y='44' width='5' height='9' fill='%238B5CF6'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="<?= wire('config')->urls->templates ?>styles/style.css?v=1" rel="stylesheet">
</head>
<body>

<header class="kopf">
  <a class="kopf__marke" href="<?= $homepage->url ?>">Kulturort Admiralbrücke</a>
  <nav class="kopf__nav" aria-label="<?= t('Hauptnavigation', 'Main navigation') ?>">
    <?php foreach ($navSections as $navSection): ?>
    <a href="<?= $homepage->url ?>#<?= $navSection->name ?>"><?= $navSection->title ?></a>
    <?php endforeach ?>
    <a class="kopf__lang" href="<?= $page->localUrl($otherLanguage) ?>"><?= isGerman() ? 'EN' : 'DE' ?></a>
  </nav>
</header>
