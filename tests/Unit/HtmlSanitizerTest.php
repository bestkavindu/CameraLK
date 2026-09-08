<?php

use App\Support\HtmlSanitizer;

test('empty input becomes null', function (?string $input) {
    expect(HtmlSanitizer::clean($input))->toBeNull();
})->with([null, '', '   ', '<p></p>', '<p><br></p>', '<p>&nbsp;</p>']);

test('formatting the editor produces is preserved', function () {
    $html = '<h2>Specs</h2><p><strong>Bold</strong> <em>italic</em> <u>underline</u> <s>strike</s></p>'
        .'<ul><li>4K 240fps slow motion</li></ul><ol><li>First</li></ol>'
        .'<blockquote>Quote</blockquote><pre><code>code</code></pre><hr>';

    expect(HtmlSanitizer::clean($html))->toBe($html);
});

test('script style and frame tags are removed with their contents', function (string $input) {
    expect(HtmlSanitizer::clean('<p>Keep</p>'.$input))->toBe('<p>Keep</p>');
})->with([
    '<script>alert(1)</script>',
    '<style>body{display:none}</style>',
    '<iframe src="https://evil.test"></iframe>',
    '<object data="x"></object>',
    '<embed src="x">',
]);

test('event handler and style attributes are stripped', function () {
    $clean = HtmlSanitizer::clean('<p onclick="steal()" style="color:red" class="x">Text</p>');

    expect($clean)->toBe('<p>Text</p>');
});

test('unknown tags are unwrapped but their text survives', function () {
    expect(HtmlSanitizer::clean('<p>Hello <span class="bad">world</span></p>'))
        ->toBe('<p>Hello world</p>');
});

test('links keep safe schemes and lose dangerous ones', function (string $href, bool $kept) {
    $clean = HtmlSanitizer::clean('<p><a href="'.$href.'">Link</a></p>');

    expect(str_contains((string) $clean, 'href'))->toBe($kept);
})->with([
    ['https://example.test', true],
    ['http://example.test', true],
    ['mailto:sales@example.test', true],
    ['/relative/path', true],
    ['#anchor', true],
    ['javascript:alert(1)', false],
    ['data:text/html;base64,PHN2Zz4=', false],
]);

test('links opening a new tab get a safe rel', function () {
    expect(HtmlSanitizer::clean('<p><a href="https://example.test" target="_blank">Link</a></p>'))
        ->toContain('rel="noopener noreferrer"');
});

test('multibyte content survives sanitising', function () {
    expect(HtmlSanitizer::clean('<p>Zoom — 4K “slow motion” ± 60fps · ලංකා</p>'))
        ->toBe('<p>Zoom — 4K “slow motion” ± 60fps · ලංකා</p>');
});
