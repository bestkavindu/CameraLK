<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Reduce rich text editor output to a known-safe subset of HTML.
 *
 * Anything outside the allow lists below is unwrapped (its text is kept) or,
 * for tags that only carry executable payloads, removed entirely.
 */
class HtmlSanitizer
{
    /**
     * Tags the editor is allowed to produce.
     *
     * @var list<string>
     */
    protected const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'code', 'pre',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'hr', 'a',
    ];

    /**
     * Attributes allowed per tag.
     *
     * @var array<string, list<string>>
     */
    protected const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
    ];

    /**
     * Tags whose contents are dropped along with the tag itself.
     *
     * @var list<string>
     */
    protected const STRIPPED_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg'];

    /**
     * URL schemes links may point at.
     *
     * @var list<string>
     */
    protected const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Sanitize a fragment of editor generated HTML.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('body')->item(0);

        if (! $root instanceof DOMElement) {
            return null;
        }

        static::cleanChildren($root);

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        $clean = trim($clean);

        return static::isEmpty($clean) ? null : $clean;
    }

    /**
     * Recursively sanitize every child of the given node.
     */
    protected static function cleanChildren(DOMNode $node): void
    {
        // Iterate over a snapshot, since the loop mutates the live child list...
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, static::STRIPPED_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            static::cleanChildren($child);

            if (! in_array($tag, static::ALLOWED_TAGS, true)) {
                static::unwrap($child);

                continue;
            }

            static::cleanAttributes($child, $tag);
        }
    }

    /**
     * Drop every attribute that is not allowed for the given tag.
     */
    protected static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = static::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->nodeName), $allowed, true)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        if ($tag !== 'a') {
            return;
        }

        if (! static::isSafeUrl($element->getAttribute('href'))) {
            $element->removeAttribute('href');

            return;
        }

        if ($element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * Replace an element with its own children.
     */
    protected static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent instanceof DOMNode) {
            return;
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            $parent->insertBefore($child, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * Determine whether a link target uses an allowed scheme.
     */
    protected static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        // Relative and anchor links carry no scheme and are safe as-is...
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), static::ALLOWED_SCHEMES, true);
    }

    /**
     * Determine whether the sanitized markup carries no visible content.
     */
    protected static function isEmpty(string $html): bool
    {
        $text = strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], ' ', $html));

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // \pZ covers the non-breaking space the parser leaves behind for &nbsp;...
        return preg_replace('/[\s\pZ\x{200B}]+/u', '', $text) === '';
    }
}
