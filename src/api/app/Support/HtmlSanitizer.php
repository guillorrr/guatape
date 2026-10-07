<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans HTML coming from the rich-text editor (AppRichText) before it is
 * stored, so it can later be rendered with v-html without opening an XSS hole.
 *
 * Keeps exactly what the editor's toolbar produces (paragraphs, headings,
 * bold/italic/underline/strike, lists, quotes, links) and drops everything
 * else: scripts, event handlers, inline styles, iframes, images. Links are
 * forced to http(s)/mailto and get rel="noopener noreferrer".
 */
final class HtmlSanitizer
{
    private static ?SymfonySanitizer $instance = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim(self::sanitizer()->sanitize($html));

        // Nothing but empty paragraphs left: treat as empty.
        return trim(strip_tags($clean)) === '' ? null : $clean;
    }

    private static function sanitizer(): SymfonySanitizer
    {
        return self::$instance ??= new SymfonySanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('strong')
                ->allowElement('em')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('ol')
                ->allowElement('ul')
                ->allowElement('li', ['data-list'])
                ->allowElement('blockquote')
                ->allowElement('a', ['href', 'target'])
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->withMaxInputLength(200_000)
        );
    }
}
