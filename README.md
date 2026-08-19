# Chatixy AI Chat - Flarum extension

Adds the [Chatixy](https://chatixy.com) AI chat widget to every page of a
[Flarum](https://flarum.org) forum. Install, enable, paste your widget key in the
admin panel, save. No template overrides, no theme edits, no custom header or
footer HTML to maintain.

## Requirements

| | |
| --- | --- |
| Flarum | **1.8+** (`composer.json` requires `flarum/core: ^1.8`) |
| PHP | no floor of its own - whatever your Flarum install already needs |
| Build tools | none. The two frontend bundles ship ready to run |

## Install

```bash
composer require chatixy/flarum-chatixy
```

Forums running Flarum's built-in **Extension Manager** can install it from that
panel by package name instead of using the CLI.

Then open **Administration -> Extensions -> Chatixy AI Chat**, enable the
extension, open its settings (the cog), and fill in:

- **Chatixy widget key** - the key from your Chatixy dashboard (*Install* tab).
- **Show the Chatixy chat widget on the forum** - the on/off switch. It defaults
  to on, and turning it off hides the widget without disabling the extension or
  clearing the key.

Flarum stores those two under the setting keys `chatixy-chatixy.widget_key` and
`chatixy-chatixy.enabled`. The extension id is `chatixy-chatixy`, not
`chatixy-flarum-chatixy`: Flarum builds the id by stripping the `flarum-` prefix
off the package part of `chatixy/flarum-chatixy` and joining it to the vendor.
It looks like a typo; it is the correct value.

The key field accepts any of the three shapes the dashboard hands out:

- the bare 64-character key,
- `<key>.js`,
- the whole `<script src="https://chatixy.com/source/<key>.js" async></script>`
  snippet.

The key is extracted from whatever you paste. Leave it empty and the extension
does nothing at all.

No Chatixy account yet? Start at <https://chatixy.com/register>.

## What it does

On every forum page it appends one tag to `<head>`:

```html
<script id="chatixy-widget-loader"
        src="https://chatixy.com/source/<key>.js?platform=flarum" async></script>
```

That is the whole payload. No CSS, no additional requests from the server side,
nothing written into your templates.

## Where the loader is added

`(new Extend\Frontend('forum'))->js(...)` loads the extension's forum bundle on
**every forum page** - that is the entire injection mechanism, so there are no
template overrides, no event hooks and no DOM patching to conflict with your
theme.

The loader URL itself is built **server side** in `extend.php` and put on the
forum payload as a finished `src`. Whatever the admin pasted is reduced to its
64-hex run and re-validated; if it does not hold up, the frontend receives an
empty string and injects nothing rather than emitting a broken tag.

Also true, and visible in the source:

- **Nothing is injected when the toggle is off**, or when no valid key is stored.
- **Exactly one tag per page.** The bundle bails out if
  `#chatixy-widget-loader` already exists.
- **The admin panel gets no widget.** The admin bundle only registers the two
  settings; the loader lives in the forum bundle.
- The frontend bundle re-checks the URL it was handed before assigning it, and
  only an `https` Chatixy origin serving `/source/<64-hex>.js` passes.

## Security: the origin is pinned

The widget key is public; the **host** is not configurable. There is no
"advanced host" field, no setting and no environment variable, because a stored
host would build a first-party `<script src>` on every page of the forum - i.e.
forum-wide stored XSS if it could be influenced. `ChatixyKey::sanitizeHost()`
accepts only an **https** origin whose host is `chatixy.com` or a subdomain of
it, and returns the canonical origin for anything else. The pattern is anchored
at both ends and only grows the host to the left of a literal dot, so
`evilchatixy.com`, `chatixy.com.evil.example`, `chatixy.com@evil.example` and
`http://chatixy.com` are all rejected.

## Support

- Docs and account: <https://chatixy.com>
- Help: <https://chatixy.com/support>
- Email: <support@chatixy.com>

MIT licensed. See [LICENSE](LICENSE).
