/**
 * Chatixy AI Chat - Flarum admin frontend.
 *
 * Registers the extension's settings on its page in the admin panel
 * (Administration -> Extensions -> Chatixy AI Chat -> the cog / Settings), so
 * the admin never has to touch a config file. Flarum persists these straight to
 * the settings table under the keys given in `setting:`, which are the same
 * keys extend.php reads.
 *
 * HAND-WRITTEN, NOT COMPILED - see js/dist/forum.js for why. The extension id
 * "chatixy-chatixy" is what Flarum derives from "chatixy/flarum-chatixy"; see
 * the note at the top of extend.php.
 */
(function () {
  'use strict';

  var root = typeof window !== 'undefined' ? window : undefined;
  if (!root) {
    return;
  }

  var compat = (root.flarum && root.flarum.core && root.flarum.core.compat) || {};
  var app = compat['admin/app'] || compat['common/app'] || root.app;
  if (!app || !app.initializers) {
    return;
  }

  app.initializers.add('chatixy-chatixy', function () {
    app.extensionData
      .for('chatixy-chatixy')
      .registerSetting({
        setting: 'chatixy-chatixy.enabled',
        label: app.translator.trans('chatixy-chatixy.admin.settings.enabled_label'),
        help: app.translator.trans('chatixy-chatixy.admin.settings.enabled_help'),
        type: 'boolean',
      })
      .registerSetting({
        setting: 'chatixy-chatixy.widget_key',
        label: app.translator.trans('chatixy-chatixy.admin.settings.widget_key_label'),
        help: app.translator.trans('chatixy-chatixy.admin.settings.widget_key_help'),
        type: 'text',
        placeholder: '0123456789abcdef…',
      });
  });
})();
