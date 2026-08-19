<?php

/**
 * Chatixy AI Support Agent - Flarum extension entry point.
 *
 * Every Chatixy integration does the same job: bind a widget_key and inject
 *   <script src="https://chatixy.com/source/<64-hex-key>.js?platform=flarum" async>
 *
 * Flarum's Extend\Frontend('forum')->js() loads our bundle on every forum page,
 * which is the whole injection mechanism - no template overrides, no hooks.
 *
 * NOTE ON THE EXTENSION ID. Flarum derives it from the composer name by
 * stripping a "flarum-"/"flarum-ext-" prefix from the package part and joining
 * with the vendor (Flarum\Extension\Extension::nameToId). So
 * "chatixy/flarum-chatixy" becomes "chatixy-chatixy" - which is why the setting
 * keys, the locale namespace and app.extensionData.for() all read
 * "chatixy-chatixy" rather than "chatixy-flarum-chatixy". It looks like a typo;
 * it is not.
 */

declare(strict_types=1);

use Chatixy\Flarum\ChatixyKey;
use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\Settings())
        // The admin toggle, exposed to the forum frontend as a real boolean.
        ->serializeToForum('chatixyEnabled', 'chatixy-chatixy.enabled', 'boolval', true)

        // The loader URL is built HERE, server side, and the browser only ever
        // receives a finished src. The raw key is deliberately NOT serialised:
        // whatever the admin pasted (bare key, "<key>.js", or the whole embed
        // snippet) is reduced to its 64-hex run, re-validated, and dropped
        // entirely if it does not hold up - in which case the frontend gets ''
        // and injects nothing. The origin comes from the pinned constant, never
        // from a setting: there is no host field to set.
        ->serializeToForum(
            'chatixyEmbedSrc',
            'chatixy-chatixy.widget_key',
            function ($value): string {
                $key = ChatixyKey::sanitize($value);
                if (!ChatixyKey::isValid($key)) {
                    return '';
                }

                return ChatixyKey::embedSrc(
                    ChatixyKey::sanitizeHost(ChatixyKey::DEFAULT_HOST),
                    $key,
                    'flarum'
                );
            },
            ''
        ),
];
