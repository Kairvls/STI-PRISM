<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

/**
 * Generate QR code PNG bytes with PHP GD (no Imagick required).
 * SimpleSoftwareIO QrCode::format('png') needs Imagick; this is the GD fallback.
 */
final class GdQrPng
{
    public static function generate(string $content, int $size = 300, int $margin = 1): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required to generate QR PNG images.');
        }

        if ($content === '') {
            throw new RuntimeException('QR content cannot be empty.');
        }

        $size = max(21, $size);
        $margin = max(0, $margin);

        $qrCode = Encoder::encode($content, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth();
        $totalModules = $moduleCount + (2 * $margin);
        $moduleSize = max(1, intdiv($size, $totalModules));
        $pixelSize = $moduleSize * $totalModules;

        $image = imagecreatetruecolor($pixelSize, $pixelSize);
        if ($image === false) {
            throw new RuntimeException('Unable to create QR image canvas.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $x1 = ($margin + $x) * $moduleSize;
                $y1 = ($margin + $y) * $moduleSize;

                imagefilledrectangle(
                    $image,
                    $x1,
                    $y1,
                    $x1 + $moduleSize - 1,
                    $y1 + $moduleSize - 1,
                    $black
                );
            }
        }

        if ($pixelSize !== $size) {
            $scaled = imagescale($image, $size, $size, IMG_NEAREST_NEIGHBOUR);
            imagedestroy($image);

            if ($scaled === false) {
                throw new RuntimeException('Unable to scale QR image.');
            }

            $image = $scaled;
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
