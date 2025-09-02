<?php
/**
 * ReadyCRM Iconpack
 * Inline SVG loader from /assets/icons/{name}.svg
 * Usage (PHP): echo rs_icon('dashboard', 18, 'me-2 text-primary');
 * Usage (HTML+JS): <i data-icon="dashboard"></i>  // rs-icons.js inlines it
 */

if (!defined('RS_ICON_DIR'))  define('RS_ICON_DIR', realpath(__DIR__ . '/../assets/icons'));
if (!defined('RS_ICON_CACHE_DIR')) {
  define('RS_ICON_CACHE_DIR', realpath(__DIR__ . '/../public/assets/cache/icons'));
  if (!is_dir(RS_ICON_CACHE_DIR)) @mkdir(RS_ICON_CACHE_DIR, 0775, true);
}

function rs_icon_path($name){
  $name = preg_replace('/[^a-z0-9_\-\.]/i', '', $name);
  $paths = [
    RS_ICON_DIR . DIRECTORY_SEPARATOR . $name . '.svg',
    RS_ICON_DIR . DIRECTORY_SEPARATOR . $name
  ];
  foreach ($paths as $p){ if (is_file($p)) return $p; }
  return null;
}

function rs_icon($name, $size=16, $class=''){
  $src = rs_icon_path($name);
  if(!$src){
    // Fallback placeholder (UI نشکنه)
    return '<svg class="rs-icon '.htmlspecialchars($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="icon-missing"><circle cx="8" cy="8" r="6" fill="currentColor" opacity=".5"/></svg>';
  }

  $cache = RS_ICON_CACHE_DIR . DIRECTORY_SEPARATOR . basename($src) . '.inline.svg';
  if (!file_exists($cache) || filemtime($cache) < filemtime($src)) {
    $svg = @file_get_contents($src);
    if ($svg === false) {
      return '<span class="rs-icon '.htmlspecialchars($class).' rs-icon-error" style="display:inline-block;width:'.$size.'px;height:'.$size.'px;background:#f87171;border-radius:3px"></span>';
    }
    // پاک‌سازی مقدماتی
    $svg = preg_replace('/<\?xml[^>]*>\s*/i', '', $svg);
    $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $svg);
    // همه‌ی رنگ‌ها به currentColor (به جز none)
    $svg = preg_replace('/fill\s*=\s*"(?!none)(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|[^"]+)"/i', 'fill="currentColor"', $svg);
    $svg = preg_replace('/stroke\s*=\s*"(?!none)(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|[^"]+)"/i', 'stroke="currentColor"', $svg);
    // اگر viewBox نبود، پیش‌فرض بگذار
    if (!preg_match('/viewBox\s*=\s*"[0-9\.\s]+"/i', $svg)) {
      $svg = preg_replace('/<svg\b/i', '<svg viewBox="0 0 '.$size.' '.$size.'"', $svg, 1);
    }
    // عرض/ارتفاع داخلی حذف شود (سایز را بیرون کنترل می‌کنیم)
    $svg = preg_replace('/\s(width|height)\s*=\s*"[^"]*"/i', '', $svg);
    // کلاس و سایز تزریق شود
    $svg = preg_replace('/<svg\b/i', '<svg class="rs-icon __CLASS__" width="'.$size.'" height="'.$size.'" role="img" aria-label="'.htmlspecialchars($name, ENT_QUOTES, "UTF-8").'"', $svg, 1);

    @file_put_contents($cache, $svg);
  } else {
    $svg = @file_get_contents($cache);
  }

  // کلاس نهایی را جایگزین کن
  $svg = str_replace('__CLASS__', htmlspecialchars(trim($class)), $svg);
  return $svg;
}
