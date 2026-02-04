<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ContentSanitizer
{
    public static function make(): HtmlSanitizer
    {
        // Permet el que normalment genera Quill/PrimeVue Editor
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements() // permet elements “segurs” per defecte (bàsic)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            // Links segurs (si vols permetre enllaços)
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            // Opcional: limita protocols per evitar javascript:
            ->allowLinkSchemes(['http', 'https', 'mailto'])
        ;

        return new HtmlSanitizer($config);
    }

    public static function sanitize(?string $html): string
    {
        if (!$html) return '';
        return self::make()->sanitize($html);
    }
}