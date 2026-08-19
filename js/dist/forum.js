/**
 * Chatixy AI Support Agent - Flarum forum frontend.
 *
 * Loaded on every forum page by (new Extend\Frontend('forum'))->js(...) in
 * extend.php. All it does is read the two attributes extend.php serialises onto
 * the forum payload and append the Chatixy loader <script> once.
 *
 * HAND-WRITTEN, NOT COMPILED. Flarum extensions are normally webpack builds of
 * js/src -> js/dist; this one is small enough to have no build step at all, so
 * there is deliberately no js/src, no package.json and no webpack config here.
 * That means it must not use ESM imports or JSX - it reaches Flarum's frontend
 * modules through the runtime globals the compiled bundles use, with fallbacks,
 * and does nothing at all if none of them are present.
 */
(function () {
  'use strict';

  var root = typeof window !== 'undefined' ? window : undefined;
  if (!root) {
    return;
  }

  var compat = (root.flarum && root.flarum.core && root.flarum.core.compat) || {};
  var app = compat['forum/app'] || compat['common/app'] || root.app;
  if (!app || !app.initializers) {
    return;
  }

  app.initializers.add('chatixy-chatixy', function () {
    if (!app.forum || !app.forum.attribute('chatixyEnabled')) {
      return;
    }

    var src = String(app.forum.attribute('chatixyEmbedSrc') || '');

    // extend.php already sanitised and rebuilt this URL server side. We check it
    // again here because this value is about to become a <script src> on every
    // page of the forum, and a second cheap assertion is worth more than the
    // trust: only an https Chatixy origin serving /source/<64-hex>.js passes.
    if (!/^https:\/\/([a-z0-9-]+\.)*chatixy\.com\/source\/[a-f0-9]{64}\.js(\?|$)/.test(src)) {
      return;
    }

    if (document.getElementById('chatixy-widget-loader')) {
      return;
    }

    var el = document.createElement('script');
    el.id = 'chatixy-widget-loader';
    el.async = true;
    el.src = src;
    document.head.appendChild(el);
  });
})();
