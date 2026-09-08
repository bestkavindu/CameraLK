<?php

namespace Database\Seeders\Support;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Draw flat illustration placeholders for seeded catalogue records.
 *
 * Seeded demo data needs pictures, and downloading them would make seeding
 * depend on the network. These are generated with GD instead: a gradient in a
 * hue derived from the record name, plus a simple mark suggesting the kind of
 * product. Everything is drawn at double size and scaled down, because GD does
 * not antialias filled shapes.
 */
class PlaceholderImage
{
    /**
     * Marks that can be drawn on a product image.
     *
     * @var list<string>
     */
    public const KINDS = ['camera', 'lens', 'drone', 'gimbal', 'accessory'];

    /**
     * How much larger the working canvas is than the output.
     */
    private const SUPERSAMPLE = 2;

    /**
     * Render a product image and return the encoded JPEG.
     *
     * The variant reframes the same drawing rather than redrawing it, so the
     * several images of one product read as different shots of that product
     * instead of unrelated pictures.
     */
    public static function product(string $seed, string $kind, int $variant = 0, int $width = 1200, int $height = 900): string
    {
        if (! in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException("Unknown placeholder kind [{$kind}].");
        }

        $scale = self::SUPERSAMPLE;
        $canvas = self::canvas($width * $scale, $height * $scale, $seed);

        $hue = self::hue($seed);
        $ink = self::allocateHsl($canvas, $hue, 0.34, 0.22);
        $highlight = self::allocateHsl($canvas, $hue, 0.30, 0.44);
        $glass = self::allocateHsl($canvas, $hue, 0.42, 0.66);

        match ($kind) {
            'camera' => self::drawCamera($canvas, $width * $scale, $height * $scale, $ink, $highlight, $glass),
            'lens' => self::drawLens($canvas, $width * $scale, $height * $scale, $ink, $highlight, $glass),
            'drone' => self::drawDrone($canvas, $width * $scale, $height * $scale, $ink, $highlight, $glass),
            'gimbal' => self::drawGimbal($canvas, $width * $scale, $height * $scale, $ink, $highlight, $glass),
            'accessory' => self::drawAccessory($canvas, $width * $scale, $height * $scale, $ink, $highlight, $glass),
        };

        return self::encode(self::reframe($canvas, $variant), $width, $height);
    }

    /**
     * Crop in slightly for later images of the same product.
     */
    private static function reframe(GdImage $canvas, int $variant): GdImage
    {
        if ($variant <= 0) {
            return $canvas;
        }

        $width = imagesx($canvas);
        $height = imagesy($canvas);

        $inset = 0.06 * $variant;
        [$shiftX, $shiftY] = [[0.0, 0.0], [0.03, -0.02], [-0.03, 0.02], [0.01, 0.03]][$variant % 4];

        $left = max(0, (int) ($width * ($inset / 2 + $shiftX)));
        $top = max(0, (int) ($height * ($inset / 2 + $shiftY)));

        $cropped = imagecrop($canvas, [
            'x' => $left,
            'y' => $top,
            'width' => min((int) ($width * (1 - $inset)), $width - $left),
            'height' => min((int) ($height * (1 - $inset)), $height - $top),
        ]);

        if ($cropped === false) {
            return $canvas;
        }

        imagedestroy($canvas);

        return $cropped;
    }

    /**
     * Render a square monogram tile, used for brand, category and sub category
     * images where the record has a short name rather than a shape.
     */
    public static function monogram(string $seed, string $label, int $size = 600): string
    {
        $scale = self::SUPERSAMPLE;
        $canvas = self::canvas($size * $scale, $size * $scale, $seed);

        $hue = self::hue($seed);
        $ink = self::allocateHsl($canvas, $hue, 0.36, 0.20);

        self::drawLabel($canvas, $size * $scale, $size * $scale, self::initials($label), $ink);

        return self::encode($canvas, $size, $size);
    }

    /**
     * Create the gradient background every placeholder shares.
     */
    private static function canvas(int $width, int $height, string $seed): GdImage
    {
        $canvas = imagecreatetruecolor(max(1, $width), max(1, $height));

        $hue = self::hue($seed);

        // Vertical gradient, light at the top so the mark reads against it.
        for ($y = 0; $y < $height; $y++) {
            $mix = $y / max(1, $height - 1);

            imagefilledrectangle(
                $canvas, 0, $y, $width, $y,
                self::allocateHsl($canvas, $hue, 0.20, 0.94 - ($mix * 0.20)),
            );
        }

        // Off-centre glow, so no two hues look like the same flat swatch.
        $glow = self::allocateHsl($canvas, $hue, 0.34, 0.88);
        imagefilledellipse($canvas, (int) ($width * 0.72), (int) ($height * 0.24), (int) ($width * 0.62), (int) ($width * 0.62), $glow);

        return $canvas;
    }

    /**
     * Scale the working canvas down to its output size and encode it.
     */
    private static function encode(GdImage $canvas, int $width, int $height): string
    {
        // These canvases are tens of megabytes, so every exit from here has to
        // free them rather than wait for the request to end.
        try {
            $scaled = imagescale($canvas, $width, $height, IMG_BICUBIC);

            if ($scaled === false) {
                throw new RuntimeException('Unable to scale the generated placeholder.');
            }

            try {
                ob_start();
                imagejpeg($scaled, null, 82);
                $jpeg = ob_get_clean();
            } finally {
                imagedestroy($scaled);
            }
        } finally {
            imagedestroy($canvas);
        }

        if ($jpeg === false) {
            throw new RuntimeException('Unable to encode the generated placeholder.');
        }

        return $jpeg;
    }

    /**
     * Draw a mirrorless body: grip, viewfinder hump, lens and shutter button.
     */
    private static function drawCamera(GdImage $canvas, int $width, int $height, int $ink, int $highlight, int $glass): void
    {
        $bodyWidth = (int) ($width * 0.54);
        $bodyHeight = (int) ($height * 0.40);
        $left = (int) (($width - $bodyWidth) / 2);
        $top = (int) (($height - $bodyHeight) / 2 + $height * 0.04);

        // Viewfinder hump.
        $humpWidth = (int) ($bodyWidth * 0.30);
        $humpLeft = (int) ($left + ($bodyWidth - $humpWidth) / 2);
        self::roundedRect($canvas, $humpLeft, (int) ($top - $bodyHeight * 0.20), $humpLeft + $humpWidth, $top + 10, (int) ($bodyHeight * 0.08), $ink);

        self::roundedRect($canvas, $left, $top, $left + $bodyWidth, $top + $bodyHeight, (int) ($bodyHeight * 0.16), $ink);

        // Hand grip on the right shoulder of the body.
        self::roundedRect(
            $canvas,
            (int) ($left + $bodyWidth * 0.78),
            (int) ($top + $bodyHeight * 0.08),
            (int) ($left + $bodyWidth * 0.99),
            (int) ($top + $bodyHeight * 0.92),
            (int) ($bodyHeight * 0.14),
            $highlight,
        );

        // Lens barrel and front element.
        $centreX = (int) ($left + $bodyWidth * 0.42);
        $centreY = (int) ($top + $bodyHeight * 0.52);
        $lens = (int) ($bodyHeight * 0.62);

        imagefilledellipse($canvas, $centreX, $centreY, $lens, $lens, $highlight);
        imagefilledellipse($canvas, $centreX, $centreY, (int) ($lens * 0.72), (int) ($lens * 0.72), $ink);
        imagefilledellipse($canvas, $centreX, $centreY, (int) ($lens * 0.44), (int) ($lens * 0.44), $glass);

        // Shutter release.
        imagefilledellipse($canvas, (int) ($left + $bodyWidth * 0.88), (int) ($top + $bodyHeight * 0.02), (int) ($bodyHeight * 0.13), (int) ($bodyHeight * 0.13), $glass);
    }

    /**
     * Draw a lens barrel seen from a three-quarter angle.
     */
    private static function drawLens(GdImage $canvas, int $width, int $height, int $ink, int $highlight, int $glass): void
    {
        $barrelWidth = (int) ($width * 0.30);
        $barrelHeight = (int) ($height * 0.46);
        $left = (int) (($width - $barrelWidth) / 2);
        $top = (int) (($height - $barrelHeight) / 2);

        self::roundedRect($canvas, $left, $top, $left + $barrelWidth, $top + $barrelHeight, (int) ($barrelWidth * 0.10), $ink);

        // Zoom and focus rings.
        foreach ([0.26, 0.52] as $offset) {
            $ringTop = (int) ($top + $barrelHeight * $offset);

            imagefilledrectangle($canvas, $left, $ringTop, $left + $barrelWidth, (int) ($ringTop + $barrelHeight * 0.09), $highlight);
        }

        // Front element.
        imagefilledellipse($canvas, (int) ($left + $barrelWidth / 2), $top, $barrelWidth, (int) ($barrelWidth * 0.42), $highlight);
        imagefilledellipse($canvas, (int) ($left + $barrelWidth / 2), $top, (int) ($barrelWidth * 0.70), (int) ($barrelWidth * 0.30), $glass);

        // Mount flange.
        self::roundedRect(
            $canvas,
            (int) ($left - $barrelWidth * 0.08),
            (int) ($top + $barrelHeight * 0.88),
            (int) ($left + $barrelWidth * 1.08),
            $top + $barrelHeight,
            (int) ($barrelWidth * 0.04),
            $highlight,
        );
    }

    /**
     * Draw a quadcopter from above.
     */
    private static function drawDrone(GdImage $canvas, int $width, int $height, int $ink, int $highlight, int $glass): void
    {
        $centreX = (int) ($width / 2);
        $centreY = (int) ($height / 2);
        $reach = (int) ($height * 0.30);
        $armThickness = (int) ($height * 0.045);
        $rotor = (int) ($height * 0.26);

        // Arms and rotors on each diagonal.
        foreach ([[-1, -1], [1, -1], [-1, 1], [1, 1]] as [$dx, $dy]) {
            $tipX = (int) ($centreX + $dx * $reach);
            $tipY = (int) ($centreY + $dy * $reach * 0.82);

            imagefilledpolygon($canvas, [
                $centreX - $armThickness, $centreY,
                $centreX + $armThickness, $centreY,
                $tipX + $armThickness, $tipY,
                $tipX - $armThickness, $tipY,
            ], $ink);

            imagefilledellipse($canvas, $tipX, $tipY, $rotor, (int) ($rotor * 0.22), $highlight);
            imagefilledellipse($canvas, $tipX, $tipY, (int) ($armThickness * 1.6), (int) ($armThickness * 1.6), $ink);
        }

        // Fuselage and gimbal camera.
        $bodyWidth = (int) ($width * 0.20);
        $bodyHeight = (int) ($height * 0.20);

        self::roundedRect(
            $canvas,
            (int) ($centreX - $bodyWidth / 2),
            (int) ($centreY - $bodyHeight / 2),
            (int) ($centreX + $bodyWidth / 2),
            (int) ($centreY + $bodyHeight / 2),
            (int) ($bodyHeight * 0.32),
            $ink,
        );

        imagefilledellipse($canvas, $centreX, (int) ($centreY + $bodyHeight * 0.34), (int) ($bodyHeight * 0.46), (int) ($bodyHeight * 0.46), $highlight);
        imagefilledellipse($canvas, $centreX, (int) ($centreY + $bodyHeight * 0.34), (int) ($bodyHeight * 0.24), (int) ($bodyHeight * 0.24), $glass);
    }

    /**
     * Draw a three-axis gimbal on its handle.
     */
    private static function drawGimbal(GdImage $canvas, int $width, int $height, int $ink, int $highlight, int $glass): void
    {
        $centreX = (int) ($width * 0.42);
        $bar = (int) ($height * 0.055);

        // Handle, with a motor collar where the post meets it.
        self::roundedRect(
            $canvas,
            (int) ($centreX - $bar * 1.5),
            (int) ($height * 0.60),
            (int) ($centreX + $bar * 1.5),
            (int) ($height * 0.88),
            (int) ($bar * 1.4),
            $ink,
        );

        imagefilledellipse($canvas, $centreX, (int) ($height * 0.60), (int) ($bar * 3.4), (int) ($bar * 3.4), $highlight);

        // Post up from the handle, then an arm across and down to the plate.
        self::roundedRect($canvas, (int) ($centreX - $bar * 0.6), (int) ($height * 0.22), (int) ($centreX + $bar * 0.6), (int) ($height * 0.62), (int) ($bar * 0.6), $ink);
        self::roundedRect($canvas, (int) ($centreX - $bar * 0.6), (int) ($height * 0.22), (int) ($width * 0.70), (int) ($height * 0.22 + $bar * 1.2), (int) ($bar * 0.6), $ink);
        self::roundedRect($canvas, (int) ($width * 0.70 - $bar * 1.2), (int) ($height * 0.22), (int) ($width * 0.70), (int) ($height * 0.44), (int) ($bar * 0.6), $ink);

        // Camera riding on the plate.
        self::roundedRect($canvas, (int) ($width * 0.44), (int) ($height * 0.40), (int) ($width * 0.74), (int) ($height * 0.58), (int) ($bar * 0.8), $highlight);
        imagefilledellipse($canvas, (int) ($width * 0.59), (int) ($height * 0.49), (int) ($height * 0.13), (int) ($height * 0.13), $ink);
        imagefilledellipse($canvas, (int) ($width * 0.59), (int) ($height * 0.49), (int) ($height * 0.07), (int) ($height * 0.07), $glass);
    }

    /**
     * Draw a generic kit bag for accessories.
     */
    private static function drawAccessory(GdImage $canvas, int $width, int $height, int $ink, int $highlight, int $glass): void
    {
        $bagWidth = (int) ($width * 0.42);
        $bagHeight = (int) ($height * 0.40);
        $left = (int) (($width - $bagWidth) / 2);
        $top = (int) (($height - $bagHeight) / 2 + $height * 0.06);

        // Handle, drawn before the body so it tucks behind it.
        $handleWidth = (int) ($bagWidth * 0.34);
        $handleLeft = (int) ($left + ($bagWidth - $handleWidth) / 2);

        self::roundedRect($canvas, $handleLeft, (int) ($top - $bagHeight * 0.26), $handleLeft + $handleWidth, $top, (int) ($bagHeight * 0.16), $highlight);
        self::roundedRect(
            $canvas,
            (int) ($handleLeft + $handleWidth * 0.22),
            (int) ($top - $bagHeight * 0.16),
            (int) ($handleLeft + $handleWidth * 0.78),
            $top,
            (int) ($bagHeight * 0.08),
            $glass,
        );

        self::roundedRect($canvas, $left, $top, $left + $bagWidth, $top + $bagHeight, (int) ($bagHeight * 0.14), $ink);

        // Front pocket and buckle.
        self::roundedRect(
            $canvas,
            (int) ($left + $bagWidth * 0.12),
            (int) ($top + $bagHeight * 0.46),
            (int) ($left + $bagWidth * 0.88),
            (int) ($top + $bagHeight * 0.88),
            (int) ($bagHeight * 0.08),
            $highlight,
        );

        imagefilledellipse($canvas, (int) ($left + $bagWidth * 0.5), (int) ($top + $bagHeight * 0.42), (int) ($bagHeight * 0.16), (int) ($bagHeight * 0.16), $glass);
    }

    /**
     * Write initials into the centre of a monogram tile.
     */
    private static function drawLabel(GdImage $canvas, int $width, int $height, string $initials, int $ink): void
    {
        $font = self::fontPath();

        if ($font !== null) {
            $size = (int) ($height * 0.30);
            $box = imagettfbbox($size, 0, $font, $initials);

            if ($box !== false) {
                imagettftext(
                    $canvas,
                    $size,
                    0,
                    (int) (($width - ($box[2] - $box[0])) / 2 - $box[0]),
                    (int) (($height + ($box[1] - $box[7])) / 2 - $box[1]),
                    $ink,
                    $font,
                    $initials,
                );

                return;
            }
        }

        // No TrueType font on this machine: draw the built in font onto a small
        // canvas and let the supersample scale carry it up.
        $glyphWidth = imagefontwidth(5) * strlen($initials);

        imagestring($canvas, 5, (int) (($width - $glyphWidth) / 2), (int) (($height - imagefontheight(5)) / 2), $initials, $ink);
    }

    /**
     * Reduce a name to at most two initials.
     */
    private static function initials(string $label): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '??';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
    }

    /**
     * Find a TrueType font to draw monograms with, if the machine has one.
     */
    private static function fontPath(): ?string
    {
        static $resolved = false;
        static $path = null;

        if ($resolved) {
            return $path;
        }

        $resolved = true;

        foreach ([
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $path = $candidate;
            }
        }

        return null;
    }

    /**
     * Hues the placeholders are allowed to use.
     *
     * Picking from a curated list rather than the whole wheel keeps every
     * generated image looking deliberate; free rein produces muddy browns.
     *
     * @var list<float>
     */
    private const HUES = [206.0, 190.0, 168.0, 148.0, 262.0, 284.0, 338.0, 18.0, 38.0];

    /**
     * Derive a stable hue from a record name, so re-seeding redraws the same
     * picture and images never all land on the same colour.
     */
    private static function hue(string $seed): float
    {
        return self::HUES[crc32($seed) % count(self::HUES)];
    }

    /**
     * Allocate a colour given in HSL, which is easier to build palettes from.
     */
    private static function allocateHsl(GdImage $canvas, float $hue, float $saturation, float $lightness): int
    {
        [$red, $green, $blue] = self::hslToRgb($hue, $saturation, $lightness);

        $color = imagecolorallocate($canvas, $red, $green, $blue);

        return $color === false ? 0 : $color;
    }

    /**
     * Convert HSL to the 8 bit RGB triplet GD wants.
     *
     * @return array{int<0, 255>, int<0, 255>, int<0, 255>}
     */
    private static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $sector = fmod($hue / 60.0, 6.0);
        $second = $chroma * (1 - abs(fmod($sector, 2.0) - 1));
        $match = $lightness - $chroma / 2;

        [$red, $green, $blue] = match ((int) $sector) {
            0 => [$chroma, $second, 0.0],
            1 => [$second, $chroma, 0.0],
            2 => [0.0, $chroma, $second],
            3 => [0.0, $second, $chroma],
            4 => [$second, 0.0, $chroma],
            default => [$chroma, 0.0, $second],
        };

        return [
            self::channel($red + $match),
            self::channel($green + $match),
            self::channel($blue + $match),
        ];
    }

    /**
     * Round a 0..1 colour component to the 0..255 range GD accepts.
     *
     * @return int<0, 255>
     */
    private static function channel(float $value): int
    {
        return max(0, min(255, (int) round($value * 255)));
    }

    /**
     * Fill a rectangle with rounded corners.
     */
    private static function roundedRect(GdImage $canvas, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $radius = max(0, min($radius, (int) (min($x2 - $x1, $y2 - $y1) / 2)));

        if ($radius === 0) {
            imagefilledrectangle($canvas, $x1, $y1, $x2, $y2, $color);

            return;
        }

        imagefilledrectangle($canvas, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($canvas, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        $diameter = $radius * 2;

        imagefilledellipse($canvas, $x1 + $radius, $y1 + $radius, $diameter, $diameter, $color);
        imagefilledellipse($canvas, $x2 - $radius, $y1 + $radius, $diameter, $diameter, $color);
        imagefilledellipse($canvas, $x1 + $radius, $y2 - $radius, $diameter, $diameter, $color);
        imagefilledellipse($canvas, $x2 - $radius, $y2 - $radius, $diameter, $diameter, $color);
    }
}
