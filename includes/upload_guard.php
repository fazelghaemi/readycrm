<?php
/**
 * includes/upload_guard.php
 * آپلود امن: MIME واقعی (finfo)، محدودیت حجم، مسیرهای تاریخ‌دار، حذف EXIF (بازنویسی تصویر)، نام امن
 */

declare(strict_types=1);

if (!function_exists('ug_allowed_mimes')) {
    function ug_allowed_mimes(): array {
        return [
            'image/jpeg'        => 'jpg',
            'image/png'         => 'png',
            'image/webp'        => 'webp',
            'application/pdf'   => 'pdf',
            'text/plain'        => 'txt',
            // برحسب نیاز اضافه کن
        ];
    }
}

if (!function_exists('ug_sanitize_name')) {
    function ug_sanitize_name(string $name): string {
        $name = preg_replace('/[^\w\d\-\.\p{Arabic}]+/u', '_', $name);
        return trim((string)$name, '_.');
    }
}

if (!function_exists('ug_detect_mime')) {
    function ug_detect_mime(string $tmpPath): string {
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($tmpPath);
        return $mime ?: 'application/octet-stream';
    }
}

if (!function_exists('ug_strip_exif_and_reencode_image')) {
    function ug_strip_exif_and_reencode_image(string $srcPath, string $dstPath, string $mime): bool {
        switch ($mime) {
            case 'image/jpeg':
                $img = @imagecreatefromjpeg($srcPath);
                if (!$img) return false;
                $ok = @imagejpeg($img, $dstPath, 90);
                @imagedestroy($img);
                return (bool)$ok;

            case 'image/png':
                $img = @imagecreatefrompng($srcPath);
                if (!$img) return false;
                @imagesavealpha($img, true);
                $ok = @imagepng($img, $dstPath, 6);
                @imagedestroy($img);
                return (bool)$ok;

            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) return false;
                $img = @imagecreatefromwebp($srcPath);
                if (!$img) return false;
                $ok = @imagewebp($img, $dstPath, 90);
                @imagedestroy($img);
                return (bool)$ok;

            default:
                return false;
        }
    }
}

/**
 * ug_handle_upload: returns ['ok'=>bool, 'path'=>string, 'msg'=>string]
 */
if (!function_exists('ug_handle_upload')) {
    function ug_handle_upload(array $file, string $destRoot = null, int $maxBytes = 8 * 1024 * 1024): array {
        $destRoot = $destRoot ?: (__DIR__ . '/../storage/uploads');

        if (!isset($file['tmp_name']) || !is_uploaded_file((string)$file['tmp_name'])) {
            return ['ok' => false, 'msg' => 'فایل آپلودی یافت نشد.'];
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'msg' => 'حجم فایل بیش از حد مجاز است.'];
        }

        $mime    = ug_detect_mime((string)$file['tmp_name']);
        $allowed = ug_allowed_mimes();
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'msg' => 'نوع فایل مجاز نیست.'];
        }
        $ext = $allowed[$mime];

        $dateDir = date('Y/m/d');
        $absDir  = rtrim($destRoot, '/\\') . '/' . $dateDir;
        if (!is_dir($absDir)) @mkdir($absDir, 0775, true);

        $rand     = bin2hex(random_bytes(8));
        $safeBase = ug_sanitize_name(pathinfo((string)$file['name'], PATHINFO_FILENAME));
        $absPath  = $absDir . '/' . ($safeBase ?: 'file') . "_{$rand}.{$ext}";

        $isImage = (strpos($mime, 'image/') === 0);

        if ($isImage) {
            $tmp = $absPath . '.tmp';
            if (!move_uploaded_file((string)$file['tmp_name'], $tmp)) {
                return ['ok' => false, 'msg' => 'انتقال فایل موقت ناموفق بود.'];
            }
            $ok = ug_strip_exif_and_reencode_image($tmp, $absPath, $mime);
            @unlink($tmp);
            if (!$ok) {
                return ['ok' => false, 'msg' => 'خطا در پردازش تصویر.'];
            }
        } else {
            if (!move_uploaded_file((string)$file['tmp_name'], $absPath)) {
                return ['ok' => false, 'msg' => 'انتقال فایل ناموفق بود.'];
            }
        }

        $projectRoot = realpath(__DIR__ . '/..');
        $realAbs     = realpath($absPath);
        $rel         = ($projectRoot && $realAbs) ? str_replace($projectRoot, '', $realAbs) : $absPath;

        return ['ok' => true, 'path' => $rel, 'msg' => 'آپلود موفق'];
    }
}
