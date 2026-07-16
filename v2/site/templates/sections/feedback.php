<?php namespace ProcessWire; ?>
<section class="abschnitt abschnitt--rechts feedback" id="<?= $section->name ?>" data-nr="<?= $sectionNumber ?>">
  <div class="abschnitt__innen">
    <h2><?= $section->heading ?> <em><?= $section->heading_accent ?></em></h2>
    <div class="feedback__intro"><?= $section->body ?></div>
    <p class="formular__meldung formular__meldung--danke" id="feedback-danke" hidden>
      <?= t('Danke! Deine Nachricht ist angekommen.', 'Thank you! Your message has arrived.') ?>
    </p>
    <p class="formular__meldung formular__meldung--fehler" id="feedback-fehler" hidden>
      <?= t('Das hat nicht geklappt. Bitte gib eine Bewertung oder einen kurzen Text an und versuch es noch einmal.',
            'That did not work. Please give a rating or a short text and try again.') ?>
    </p>
    <form class="formular" method="post" action="<?= wire('config')->urls->root ?>feedback" id="feedback-formular">
      <input type="hidden" name="sprache" value="<?= isGerman() ? 'de' : 'en' ?>">
      <p class="formular__honig" aria-hidden="true">
        <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </p>
      <div class="formular__reihe formular__reihe--bewertung">
        <fieldset class="formular__gruppe">
          <legend class="formular__label"><?= t('Wie erlebst du die Abende auf der Brücke?', 'How do you experience the evenings on the bridge?') ?></legend>
          <div class="sterne">
            <?php foreach ([5, 4, 3, 2, 1] as $stars): ?>
            <input type="radio" id="stern<?= $stars ?>" name="sterne" value="<?= $stars ?>">
            <label for="stern<?= $stars ?>"><span class="sr"><?= $stars ?>/5</span>★</label>
            <?php endforeach ?>
          </div>
        </fieldset>
        <fieldset class="formular__gruppe">
          <legend class="formular__label"><?= t('Wie empfindest du die Lautstärke?', 'How do you find the volume?') ?></legend>
          <div class="pillen">
            <input type="radio" id="laut1" name="lautstaerke" value="passt">
            <label for="laut1"><?= t('passt', 'fine') ?></label>
            <input type="radio" id="laut2" name="lautstaerke" value="manchmal_zu_laut">
            <label for="laut2"><?= t('manchmal zu laut', 'sometimes too loud') ?></label>
            <input type="radio" id="laut3" name="lautstaerke" value="oft_zu_laut">
            <label for="laut3"><?= t('oft zu laut', 'often too loud') ?></label>
          </div>
        </fieldset>
        <fieldset class="formular__gruppe">
          <legend class="formular__label"><?= t('Ich bin …', 'I am …') ?></legend>
          <div class="pillen">
            <input type="radio" id="rolle1" name="rolle" value="anwohner">
            <label for="rolle1"><?= t('Anwohner:in', 'resident') ?></label>
            <input type="radio" id="rolle2" name="rolle" value="musiker">
            <label for="rolle2"><?= t('Musiker:in', 'musician') ?></label>
            <input type="radio" id="rolle3" name="rolle" value="gast">
            <label for="rolle3"><?= t('Gast', 'guest') ?></label>
            <input type="radio" id="rolle4" name="rolle" value="sonstiges">
            <label for="rolle4"><?= t('Sonstiges', 'other') ?></label>
          </div>
        </fieldset>
      </div>
      <div class="formular__reihe formular__reihe--texte">
        <label class="formular__feld">
          <span class="formular__label"><?= t('Was gefällt dir?', 'What do you like?') ?></span>
          <textarea name="gefaellt" rows="4" maxlength="2000"></textarea>
        </label>
        <label class="formular__feld">
          <span class="formular__label"><?= t('Was stört dich? Was sollten wir besser machen?', 'What bothers you? What should we do better?') ?></span>
          <textarea name="stoert" rows="4" maxlength="2000"></textarea>
        </label>
      </div>
      <label class="formular__feld">
        <span class="formular__label"><?= t('Sonst noch etwas?', 'Anything else?') ?></span>
        <textarea name="nachricht" rows="3" maxlength="4000"></textarea>
      </label>
      <div class="formular__reihe formular__reihe--kontakt">
        <label class="formular__feld">
          <span class="formular__label"><?= t('Name (optional)', 'Name (optional)') ?></span>
          <input type="text" name="name" maxlength="200" autocomplete="name">
        </label>
        <label class="formular__feld">
          <span class="formular__label"><?= t('E-Mail (optional, für Antwort)', 'Email (optional, for a reply)') ?></span>
          <input type="email" name="email" maxlength="320" autocomplete="email">
        </label>
      </div>
      <button class="formular__senden" type="submit"><?= t('Abschicken', 'Send') ?></button>
      <p class="formular__hinweis"><?= t(
        'Auch nur eine Bewertung ohne Text hilft uns. Deine Nachricht geht an die D-Jam-Gemeinschaft. Keine Angabe wird veröffentlicht.',
        'Even a rating without any text helps us. Your message goes to the D-Jam community. Nothing you enter is published.') ?></p>
    </form>
  </div>
</section>
