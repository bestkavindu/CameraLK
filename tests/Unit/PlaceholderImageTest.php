<?php

use Database\Seeders\Support\PlaceholderImage;

test('every product mark renders a decodable jpeg at the requested size', function (string $kind) {
    $binary = PlaceholderImage::product("Seed for {$kind}", $kind, 0, 240, 180);

    $size = getimagesizefromstring($binary);

    expect($size)->not->toBeFalse()
        ->and($size[0])->toBe(240)
        ->and($size[1])->toBe(180)
        ->and($size['mime'])->toBe('image/jpeg');
})->with(PlaceholderImage::KINDS);

test('an unknown mark is rejected rather than drawn blank', function () {
    expect(fn () => PlaceholderImage::product('Whatever', 'spaceship'))
        ->toThrow(InvalidArgumentException::class, 'Unknown placeholder kind [spaceship]');
});

test('variants of one product keep the output size but differ from each other', function () {
    $first = PlaceholderImage::product('Sony Alpha 7 IV', 'camera', 0, 240, 180);
    $second = PlaceholderImage::product('Sony Alpha 7 IV', 'camera', 1, 240, 180);
    $third = PlaceholderImage::product('Sony Alpha 7 IV', 'camera', 3, 240, 180);

    foreach ([$first, $second, $third] as $binary) {
        expect(getimagesizefromstring($binary)[0])->toBe(240);
    }

    expect($second)->not->toBe($first)
        ->and($third)->not->toBe($second);
});

test('the same seed always redraws the same image', function () {
    expect(PlaceholderImage::product('Nikon Z6 III', 'camera', 0, 240, 180))
        ->toBe(PlaceholderImage::product('Nikon Z6 III', 'camera', 0, 240, 180));
});

test('different products are drawn in different colours', function () {
    // Only worth asserting because the hue comes from a hash of the name: a
    // catalogue where every card looked identical would still "work".
    $images = collect(['Sony Alpha 7 IV', 'DJI Mavic 4 Pro', 'Canon EOS R50', 'Fujifilm X-T5'])
        ->map(fn (string $name): string => PlaceholderImage::product($name, 'camera', 0, 120, 90));

    expect($images->unique()->count())->toBeGreaterThan(1);
});

test('a monogram renders a square tile', function () {
    $size = getimagesizefromstring(PlaceholderImage::monogram('Peak Design', 'Peak Design', 160));

    expect($size[0])->toBe(160)->and($size[1])->toBe(160);
});
