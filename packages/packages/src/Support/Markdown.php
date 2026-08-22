<?php

namespace Froxlor\Packages\Support;

use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;

class Markdown
{
    /**
     * Renders marketplace package descriptions to HTML. Raw HTML in the source is stripped
     * rather than escaped/allowed, since this content can come from a remote marketplace.json
     * we don't fully control (or a local test fixture) and is rendered unescaped in the modal.
     */
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        return (string)new MarkdownConverter($environment)->convert($markdown);
    }
}
