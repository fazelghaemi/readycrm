<?php
/**
 * ReadyCRM Icon Pack – Inline SVG Helper (v2)
 * - Global, framework-agnostic
 * - Caching processed SVGs
 * - Enforces currentColor, strips width/height, keeps viewBox
 * - Accessible by default (role="img" + aria-label OR aria-hidden)
 *
 * Usage (PHP):
 *   echo rs_icon('dashboard', 20, 'me-2 text-primary');
 *   echo rs_icon('users', 'md', 'rs-muted', ['aria-label' => 'کاربران']);
 *
 * Size can be int(px) or preset: xxs,xs,sm,md,lg,xl,2xl or 16,20,24,32,40 etc.
 */

if (!defined('RS_ICON_DIR'))  define('RS_ICON_DIR', __DIR__ . '/../assets/icons');
if (!defined('RS_ICON_CACHE_DIR')) define('RS_ICON_CACHE_DIR', __DIR__ . '/../public/assets/cache/icons');
if (!defined('RS_ICON_BASE_URL')) define('RS_ICON_BASE_URL', '/assets/icons'); // only used for debugging fallback

/**
 * Ensure cache directory exists.
 */
function rs_icon_ensure_cache_dir() {
    if (!is_dir(RS_ICON_CACHE_DIR)) {
        @mkdir(RS_ICON_CACHE_DIR, 0775, true);
    }
}

/**
 * Normalizes size: accepts int px OR preset keyword.
 */
function rs_icon_normalize_size($size) {
    $map = [
        'xxs' => 12,  'xs' => 14, 'sm' => 16, 'md' => 24,
        'lg'  => 32,  'xl' => 40, '2xl' => 48,
    ];
    if (is_numeric($size)) return max(8, (int)$size);
    $key = strtolower(trim((string)$size));
    return $map[$key] ?? 20; // default 20px
}

/**
 * Sanitize class string
 */
function rs_icon_sanitize_class($class) {
    $class = trim((string)$class);
    // basic whitelist-ish: letters, digits, dash, underscore, space
    return preg_replace('/[^A-Za-z0-9\-\_\s]/', '', $class);
}

/**
 * Converts arbitrary attributes array to HTML attrs safely.
 * Special handling: role, aria-*, data-* are allowed.
 */
function rs_icon_build_attrs(array $attrs = []) {
    $html = '';
    foreach ($attrs as $k => $v) {
        $k = strtolower(trim($k));
        if (!preg_match('/^(role|aria\-[a-z]+|data\-[a-z0-9\-\_]+|title)$/', $k)) continue;
        $val = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $html .= ' ' . $k . '="' . $val . '"';
    }
    return $html;
}

/**
 * Post-process raw SVG:
 * - strip xml/doctype
 * - enforce fill/stroke=currentColor (except "none")
 * - remove width/height (keep viewBox)
 * - remove on* attributes, script tags (security)
 */
function rs_icon_process_svg($svg) {
    // remove XML/Doctype
    $svg = preg_replace('/<\?xml[^>]*>\s*/i', '', $svg);
    $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $svg);

    // strip scripts and event handlers
    $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
    $svg = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $svg);

    // force currentColor (keep "none")
    $svg = preg_replace('/\sfill\s*=\s*"(?!none)([^"]+)"/i', ' fill="currentColor"', $svg);
    $svg = preg_replace('/\sstroke\s*=\s*"(?!none)([^"]+)"/i', ' stroke="currentColor"', $svg);

    // remove width/height
    $svg = preg_replace('/\s(width|height)\s*=\s*"[^"]*"/i', '', $svg);

    // ensure viewBox exists; if missing, try to infer (best-effort)
    if (!preg_match('/viewBox\s*=\s*"[0-9\.\s\-]+"/i', $svg)) {
        // fallback: add a default viewBox (assume 24)
        $svg = preg_replace('/<svg\b/i', '<svg viewBox="0 0 24 24"', $svg, 1);
    }

    // ensure xmlns present
    if (!preg_match('/\sxmlns=/', $svg)) {
        $svg = preg_replace('/<svg\b/i', '<svg xmlns="http://www.w3.org/2000/svg"', $svg, 1);
    }

    return trim($svg);
}

/**
 * Caches processed SVG to disk alongside source mtime.
 */
function rs_icon_get_svg_cached($name) {
    rs_icon_ensure_cache_dir();
    $src = rtrim(RS_ICON_DIR, '/\\') . DIRECTORY_SEPARATOR . $name . '.svg';
    if (!is_file($src)) return null;

    $mtime = filemtime($src);
    $cacheFile = rtrim(RS_ICON_CACHE_DIR, '/\\') . DIRECTORY_SEPARATOR . $name . '.' . $mtime . '.svg';

    // clear old cache files for this icon
    foreach (glob(rtrim(RS_ICON_CACHE_DIR, '/\\') . DIRECTORY_SEPARATOR . $name . '.*.svg') as $old) {
        if ($old !== $cacheFile) @unlink($old);
    }

    if (is_file($cacheFile)) {
        return file_get_contents($cacheFile);
    }

    $raw = file_get_contents($src);
    if ($raw === false) return null;

    $processed = rs_icon_process_svg($raw);
    file_put_contents($cacheFile, $processed);
    return $processed;
}

/**
 * Renders an inline SVG icon wrapped in a <span class="rs-icon">.
 * @param string $name  icon file name (without .svg), kebab-case recommended
 * @param int|string $size pixel or preset keyword (xxs,xs,sm,md,lg,xl,2xl)
 * @param string $class extra CSS classes for wrapper <span>
 * @param array  $attrs extra attributes (role/aria/data/title)
 */
function rs_icon($name, $size = 20, $class = '', array $attrs = []) {
    $name = trim((string)$name);
    if ($name === '') return '';

    $svg = rs_icon_get_svg_cached($name);
    $sizePx = rs_icon_normalize_size($size);
    $class  = rs_icon_sanitize_class($class);

    // Accessibility logic
    $hasAriaLabel = isset($attrs['aria-label']) && trim($attrs['aria-label']) !== '';
    if (!$hasAriaLabel) {
        $attrs['aria-hidden'] = 'true';
        $attrs['role'] = 'img';
    } else {
        $attrs['role'] = 'img';
    }

    $attrs['data-icon-name'] = $name;

    // wrapper classes: rs-icon + rs-[size]
    $sizeClass = 'rs-' . $sizePx;
    $wrapperClass = trim('rs-icon ' . $sizeClass . ' ' . $class);

    $attrHtml = rs_icon_build_attrs($attrs);

    if ($svg === null) {
        // fallback empty box to avoid layout shifts
        return '<span class="' . htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') . '"' . $attrHtml . '></span>';
    }

    // inject style="width:..;height:..;" on wrapper to guarantee exact size
    $style = ' style="width:' . $sizePx . 'px;height:' . $sizePx . 'px;"';

    return '<span class="' . htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') . '"' . $style . $attrHtml . '>' . $svg . '</span>';
}
