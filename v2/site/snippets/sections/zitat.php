<?php /** @var Kirby\Cms\Page $section */ ?>
<section class="abschnitt zitat">
  <blockquote>
    <p>„<?= $section->text()->esc() ?> <em><?= $section->accent()->esc() ?></em>“</p>
  </blockquote>
  <cite><?= $section->source()->esc() ?></cite>
</section>
