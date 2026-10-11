<?php
// Smaller, faster copies of site images. img('images/bg/bg.jpg', 1920) returns the path
// of a WebP copy at most 1920px wide, made on first use and kept in uploads/cache/.
// Pass $format 'jpg' where WebP is not accepted everywhere (link preview images).
// The original file is never changed; if anything goes wrong the original is used.

function img(string $path, int $width, int $quality = 78, string $format = 'webp'): string
{
    if ($path === '' || preg_match('#^(https?:)?//#', $path)) {
        return $path;
    }
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $source = realpath(SITE_ROOT . '/' . $path);
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || !$source || !str_starts_with($source, realpath(SITE_ROOT)) || !function_exists('imagewebp')) {
        return $path;
    }

    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($path, PATHINFO_FILENAME))), '-') ?: 'image';
    $format = $format === 'jpg' ? 'jpg' : 'webp';
    $hash = substr(md5($path . '|' . filemtime($source) . '|' . filesize($source) . "|$width|$quality"), 0, 10);
    $cached = 'uploads/cache/' . substr($slug, 0, 40) . "-$width-$hash.$format";
    $cachedFile = SITE_ROOT . '/' . $cached;

    if (is_file($cachedFile)) {
        return $cached;
    }
    // A previous attempt found the original already smaller; keep using it.
    if (is_file($cachedFile . '.skip')) {
        return $path;
    }
    return make_smaller_image($source, $cachedFile, $width, $quality, $format) ? $cached : $path;
}

function make_smaller_image(string $source, string $target, int $width, int $quality, string $format): bool
{
    $dir = dirname($target);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    if (!is_writable($dir)) {
        return false;
    }

    $info = @getimagesize($source);
    if (!$info || $info[0] < 1) {
        return false;
    }
    [$w, $h] = $info;
    // Decoding a big photo needs roughly 5 bytes per pixel.
    $needed = (int) ($w * $h * 5 * 1.5) + 32 * 1024 * 1024;
    if (memory_bytes(ini_get('memory_limit')) < $needed) {
        @ini_set('memory_limit', (string) max($needed, 256 * 1024 * 1024));
    }
    @set_time_limit(30);

    $image = @imagecreatefromstring((string) file_get_contents($source));
    if (!$image) {
        return false;
    }
    if ($w > $width) {
        $newH = (int) round($h * $width / $w);
        $resized = imagecreatetruecolor($width, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $newH, $w, $h);
        imagedestroy($image);
        $image = $resized;
    } else {
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    $temp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if ($format === 'jpg') {
        // JPEG has no transparency: put the image on white.
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagealphablending($flat, true);
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        $image = $flat;
        $ok = imagejpeg($image, $temp, $quality);
    } else {
        $ok = imagewebp($image, $temp, $quality);
    }
    imagedestroy($image);
    if (!$ok || !is_file($temp)) {
        @unlink($temp);
        return false;
    }
    // Not worth it if the copy came out bigger than the original.
    if (filesize($temp) >= filesize($source)) {
        @unlink($temp);
        @touch($target . '.skip');
        return false;
    }
    return @rename($temp, $target);
}

function memory_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '-1') {
        return PHP_INT_MAX;
    }
    $number = (int) $value;
    switch (strtolower(substr($value, -1))) {
        case 'g': return $number * 1024 ** 3;
        case 'm': return $number * 1024 ** 2;
        case 'k': return $number * 1024;
    }
    return $number;
}
