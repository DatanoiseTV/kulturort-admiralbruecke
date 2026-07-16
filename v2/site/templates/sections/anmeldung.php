<?php namespace ProcessWire; ?>
<section class="abschnitt anmeldung" id="<?= $section->name ?>">
  <div class="abschnitt__innen anmeldung__block">
    <div class="anmeldung__text">
      <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
      <?= $section->body ?>
    </div>
    <div class="anmeldung__formular">
      <p class="formular__meldung formular__meldung--danke" id="newsletter-bestaetigen" hidden>
        <?= t('Fast geschafft! Schau in dein Postfach und klick den Bestätigungslink.',
              'Almost there! Check your inbox and click the confirmation link.') ?>
      </p>
      <p class="formular__meldung formular__meldung--danke" id="newsletter-bestaetigt" hidden>
        <?= t('Angemeldet – bis Dienstag auf der Brücke!', 'Subscribed – see you Tuesday on the bridge!') ?>
      </p>
      <p class="formular__meldung formular__meldung--danke" id="newsletter-abgemeldet" hidden>
        <?= t('Abgemeldet. Deine Adresse ist gelöscht.', 'Unsubscribed. Your address has been deleted.') ?>
      </p>
      <p class="formular__meldung formular__meldung--fehler" id="newsletter-fehler" hidden>
        <?= t('Das hat nicht geklappt – bitte prüfe die Adresse und versuch es noch einmal.',
              'That did not work – please check the address and try again.') ?>
      </p>
      <form method="post" action="<?= wire('config')->urls->root ?>newsletter/anmelden" id="newsletter-formular">
        <input type="hidden" name="sprache" value="<?= isGerman() ? 'de' : 'en' ?>">
        <p class="formular__honig" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </p>
        <div class="anmeldung__zeile">
          <label class="sr" for="newsletter-email"><?= t('E-Mail-Adresse', 'Email address') ?></label>
          <input type="email" id="newsletter-email" name="email" maxlength="320"
                 autocomplete="email" required placeholder="<?= t('deine@mail.de', 'your@mail.com') ?>">
          <button type="submit"><?= t('Anmelden', 'Subscribe') ?></button>
        </div>
      </form>
    </div>
  </div>
</section>
