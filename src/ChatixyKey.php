<?php

/**
 * Pure, Flarum-independent helpers (key sanitisation + URL building) so they can
 * be unit-tested with plain PHPUnit (see integrations/flarum/tests/) without a
 * Flarum install. Mirrors the hardened WordPress helper, and the
 * Drupal/Joomla/Craft/TYPO3 ones.
 */

declare(strict_types=1);

namespace Chatixy\Flarum;

/**
 * Stateless widget-key + URL utilities.
 */
final class ChatixyKey
{
    /**
     * Chatixy's default public origin, and the fallback for anything rejected.
     */
    public const DEFAULT_HOST = 'https://chatixy.com';

    /**
     * The single registrable domain this extension is ever allowed to talk to.
     *
     * Both sinks that consume a host - the loader <script src> injected on every
     * forum page, and the verify handshake - must resolve to this domain (or a
     * subdomain of it) over https. See self::isAllowedHost().
     */
    public const CANONICAL_DOMAIN = 'chatixy.com';

    /**
     * Extract a clean 64-char key from a bare key, "<key>.js", or full snippet.
     *
     * Returns '' when no valid key is present, so callers can treat '' as
     * "not configured" and emit nothing at all.
     *
     * @param mixed $raw
     */
    public static function sanitize($raw): string
    {
        $value = strtolower(trim((string) $raw));
        if ($value === '') {
            return '';
        }
        if (preg_match('/[a-f0-9]{64}/', $value, $matches)) {
            return $matches[0];
        }

        return '';
    }

    /**
     * True when $key is exactly a 64-char lowercase hex string.
     *
     * @param mixed $key
     */
    public static function isValid($key): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/D', (string) $key);
    }

    /**
     * True when $origin is an origin this extension may load from or call.
     *
     * Two conditions, both required: the scheme is https, and the host is either
     * CANONICAL_DOMAIN itself or a subdomain of it. The pattern is anchored at
     * both ends and only ever grows the host to the LEFT of a literal dot, so a
     * lookalike registration such as "evilchatixy.com" (no dot boundary) and a
     * suffix trick such as "chatixy.com.evil.example" (canonical domain used as
     * a prefix) are both rejected. This mirrors the origin pinning the Chatixy
     * API applies server side.
     *
     * There is deliberately no override - no env var, no config key, no setting.
     * An escape hatch is exactly the thing an attacker would try to reach, and
     * this extension has no self-hosting story that would need one.
     *
     * @param mixed $origin A normalised scheme://host[:port] origin.
     */
    public static function isAllowedHost($origin): bool
    {
        $parts = parse_url((string) $origin);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        if ($scheme !== 'https') {
            return false;
        }
        $host = strtolower($parts['host']);

        return (bool) preg_match(
            '/^([a-z0-9-]+\.)*' . preg_quote(self::CANONICAL_DOMAIN, '/') . '$/',
            $host
        );
    }

    /**
     * Normalise a host to a bare scheme://host[:port] origin, pinned to Chatixy.
     *
     * SECURITY: the returned value is interpolated into the loader <script src>
     * this extension injects on every forum page. A host an attacker chose would
     * therefore be arbitrary first-party JavaScript across the whole forum. The
     * settings table is not a trust boundary here - so whatever comes in,
     * anything that is not an https Chatixy origin is DROPPED and replaced with
     * DEFAULT_HOST. This function never returns an unvetted origin.
     *
     * The extension ships NO settable host field; this exists so the guard sits
     * in the live path (extend.php calls it) rather than being a comment about
     * an invariant nobody enforces.
     *
     * Empty or malformed input falls back to DEFAULT_HOST, a bare host is
     * assumed to be https, and any path/query/fragment is discarded.
     *
     * @param mixed $raw
     */
    public static function sanitizeHost($raw): string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return self::DEFAULT_HOST;
        }
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }
        $parts = parse_url($value);
        if (!is_array($parts) || empty($parts['host'])) {
            return self::DEFAULT_HOST;
        }
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
        $origin = $scheme . '://' . strtolower($parts['host']);
        if (!empty($parts['port'])) {
            $origin .= ':' . (int) $parts['port'];
        }

        return self::isAllowedHost($origin) ? $origin : self::DEFAULT_HOST;
    }

    /**
     * The public loader URL: <host>/source/<key>.js
     *
     * When $platform is a non-empty string it is appended as a ?platform=<id>
     * query so the backend can detect the hosting system. A 2-arg call returns
     * the bare URL.
     */
    public static function embedSrc(string $host, string $key, string $platform = ''): string
    {
        $src = rtrim($host, '/') . '/source/' . $key . '.js';
        if ($platform !== '') {
            $src .= '?platform=' . rawurlencode($platform);
        }

        return $src;
    }

    /**
     * The handshake URL to verify a key: <host>/api/v1/widget/verify/<key>
     */
    public static function verifyUrl(string $host, string $key): string
    {
        return rtrim($host, '/') . '/api/v1/widget/verify/' . $key;
    }
}
