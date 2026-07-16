<?php $isGerman = $kirby->language()->code() === 'de'; ?>
<footer class="fuss">
  <div class="ticker ticker--fuss" aria-hidden="true">
    <div class="ticker__spur">
      <span><?= $isGerman
        ? 'Bis Dienstag — auf der Brücke — bring ein Instrument — oder nur dich — '
        : 'See you Tuesday — on the bridge — bring an instrument — or just yourself — ' ?></span>
      <span><?= $isGerman
        ? 'Bis Dienstag — auf der Brücke — bring ein Instrument — oder nur dich — '
        : 'See you Tuesday — on the bridge — bring an instrument — or just yourself — ' ?></span>
    </div>
  </div>
  <div class="fuss__meta">
    <span>Admiralbrücke · Berlin-Kreuzberg</span>
    <a href="mailto:kontakt@kulturort-admiralbruecke.de">kontakt@kulturort-admiralbruecke.de</a>
    <span><?= $isGerman ? 'D-Jam · dienstags am frühen Abend' : 'D-Jam · Tuesdays in the early evening' ?></span>
    <span><?= $isGerman ? 'Fotos: D-Jam-Gemeinschaft' : 'Photos: the D-Jam community' ?></span>
  </div>
</footer>

<script>
(function () {
  // Scrollspy: highlight the menu item of the section in view
  if ("IntersectionObserver" in window) {
    var navTargets = {};
    document.querySelectorAll(".kopf__nav a[href*='#']").forEach(function (a) {
      var anchor = a.getAttribute("href").split("#")[1];
      if (anchor) { navTargets[anchor] = a; }
    });
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        var link = navTargets[entry.target.id];
        if (!link) { return; }
        Object.keys(navTargets).forEach(function (id) {
          navTargets[id].classList.remove("aktiv");
          navTargets[id].removeAttribute("aria-current");
        });
        link.classList.add("aktiv");
        link.setAttribute("aria-current", "true");
      });
    }, { rootMargin: "-20% 0px -70% 0px" });
    Object.keys(navTargets).forEach(function (id) {
      var section = document.getElementById(id);
      if (section) { spy.observe(section); }
    });
  }

  // Form feedback after redirects (?feedback=…, ?newsletter=…); hide form on success
  var params = new URLSearchParams(location.search);
  ["feedback", "newsletter"].forEach(function (kind) {
    var value = params.get(kind);
    if (!value) { return; }
    var message = document.getElementById(kind + "-" + value);
    if (message) { message.hidden = false; }
    var isSuccess = (kind === "feedback" && value === "danke")
      || (kind === "newsletter" && (value === "bestaetigen" || value === "bestaetigt"));
    if (isSuccess) {
      var form = document.getElementById(kind + "-formular");
      if (form) { form.hidden = true; }
    }
  });

  // Matomo (self-hosted, cookie-less, respects Do-Not-Track)
  var _paq = window._paq = window._paq || [];
  _paq.push(["disableCookies"]);
  _paq.push(["trackPageView"]);
  _paq.push(["enableLinkTracking"]);
  (function () {
    var trackerBase = "https://syso.uber.space/statistik/";
    _paq.push(["setTrackerUrl", trackerBase + "matomo.php"]);
    _paq.push(["setSiteId", "1"]);
    var script = document.createElement("script");
    script.async = true;
    script.src = trackerBase + "matomo.js";
    document.head.appendChild(script);
  })();

  // Scroll reveal (reduced-motion aware)
  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches
      && "IntersectionObserver" in window) {
    var elements = document.querySelectorAll(
      ".abschnitt h2, .foto, .zeitleiste li, .mitmachen li, .aufruf__block, .termine__eintrag, .news__beitrag");
    var revealer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("sichtbar");
          revealer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    elements.forEach(function (el) {
      el.classList.add("anim");
      revealer.observe(el);
    });
  }
})();
</script>

</body>
</html>
