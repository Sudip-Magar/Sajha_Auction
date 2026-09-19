<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Strict allowlist sanitizer for the rich-text HTML produced by the Tiptap
 * editor (product description / specifications). Only structural/formatting
 * tags survive and every attribute is stripped, so there is no way to smuggle
 * event handlers, inline styles, scripts or links through the editor.
 *
 * Attribute stripping also removes table `colspan`/`rowspan`, so merged table
 * cells would render as unmerged after saving. The editor toolbar has no
 * merge/split UI, so this never comes up in normal use.
 */
class HtmlSanitizerService
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'h2', 'h3',
        'ul', 'ol', 'li',
        'blockquote',
        'code', 'pre',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    private const DROPPED_WITH_CONTENT = ['script', 'style'];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $document->getElementsByTagName('div')->item(0);

        if (! $root) {
            return '';
        }

        self::clean($root);

        $result = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    /**
     * Renders a stored value as safe HTML for display: strict sanitization for
     * rich content, and a legacy plain-text fallback (escape + nl2br) for
     * records saved before the Tiptap editor existed.
     */
    public static function toSafeHtml(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        if ($value === strip_tags($value)) {
            return nl2br(e($value));
        }

        return self::sanitize($value);
    }

    private static function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            self::clean($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $attributeNames = [];
            foreach ($child->attributes as $attribute) {
                $attributeNames[] = $attribute->name;
            }
            foreach ($attributeNames as $name) {
                $child->removeAttribute($name);
            }
        }
    }
}
