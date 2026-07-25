<?php

namespace App\Platform\Core\PageBuilder;

class SchemaFirstLaravelRenderer
{
    /**
     * @param array<string, mixed> $props
     */
    public function container(array $props, string $children = ''): string
    {
        $tag = $this->allowedTag((string) ($props['semantic_tag'] ?? 'section'), ['div', 'section', 'article', 'aside', 'header', 'footer', 'main']);

        return '<'.$tag.$this->identityAttributes($props).'>'.$children.'</'.$tag.'>';
    }

    /**
     * @param array<string, mixed> $props
     */
    public function heading(array $props): string
    {
        $tag = $this->allowedTag((string) ($props['html_tag'] ?? 'h2'), ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p']);
        $text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES, 'UTF-8');
        $link = trim((string) ($props['link'] ?? ''));

        if ($link !== '') {
            $text = '<a href="'.htmlspecialchars($link, ENT_QUOTES, 'UTF-8').'">'.$text.'</a>';
        }

        return '<'.$tag.$this->identityAttributes($props).'>'.$text.'</'.$tag.'>';
    }

    /**
     * @param array<string, mixed> $props
     */
    public function text(array $props): string
    {
        $tag = $this->allowedTag((string) ($props['html_tag'] ?? 'p'), ['p', 'div', 'span']);
        $text = nl2br(htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES, 'UTF-8'), false);
        $link = trim((string) ($props['link'] ?? ''));

        if ($link !== '') {
            $text = '<a href="'.htmlspecialchars($link, ENT_QUOTES, 'UTF-8').'">'.$text.'</a>';
        }

        return '<'.$tag.$this->identityAttributes($props).'>'.$text.'</'.$tag.'>';
    }

    /**
     * @param array<string, mixed> $props
     */
    public function button(array $props): string
    {
        $text = htmlspecialchars((string) ($props['text'] ?? 'Button'), ENT_QUOTES, 'UTF-8');
        $href = trim((string) ($props['link_url'] ?? '#'));
        $href = $href === '' ? '#' : $href;
        $target = $this->allowedValue((string) ($props['link_target'] ?? '_self'), ['_self', '_blank'], '_self');
        $targetAttribute = $target === '_blank' ? ' target="_blank" rel="noopener"' : '';

        return '<div'.$this->identityAttributes($props).'><a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" class="pb-button-link"'.$targetAttribute.'>'.$text.'</a></div>';
    }

    /**
     * @param array<string, mixed> $props
     */
    public function divider(array $props): string
    {
        return '<div'.$this->identityAttributes($props).'><span class="pb-divider-line"></span></div>';
    }

    /**
     * @param array<string, mixed> $props
     */
    public function image(array $props): string
    {
        $src = htmlspecialchars((string) ($props['media_url'] ?? $props['image_url'] ?? ''), ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars((string) ($props['alt'] ?? ''), ENT_QUOTES, 'UTF-8');
        $loading = $this->allowedValue((string) ($props['loading'] ?? 'lazy'), ['lazy', 'eager'], 'lazy');
        $decoding = $this->allowedValue((string) ($props['decoding'] ?? 'async'), ['auto', 'async'], 'async');
        $captionMode = $this->allowedValue((string) ($props['caption_mode'] ?? 'none'), ['none', 'attachment', 'custom'], 'none');
        $caption = $captionMode === 'none'
            ? ''
            : htmlspecialchars((string) ($props['caption'] ?? ''), ENT_QUOTES, 'UTF-8');
        $image = '<img src="'.$src.'" alt="'.$alt.'" loading="'.$loading.'" decoding="'.$decoding.'">';
        $linkType = $this->allowedValue((string) ($props['link_type'] ?? 'none'), ['none', 'media_file', 'custom'], 'none');
        $link = $linkType === 'media_file'
            ? (string) ($props['media_url'] ?? $props['image_url'] ?? '')
            : (string) ($props['link_url'] ?? $props['link'] ?? '');
        $link = trim($link);

        if ($linkType !== 'none' && $link !== '') {
            $target = $this->allowedValue((string) ($props['link_target'] ?? '_self'), ['_self', '_blank'], '_self');
            $rel = ($props['link_nofollow'] ?? 'false') === 'true' ? ' rel="nofollow"' : '';
            $targetAttribute = $target === '_blank' ? ' target="_blank"' : '';
            $image = '<a href="'.htmlspecialchars($link, ENT_QUOTES, 'UTF-8').'"'.$targetAttribute.$rel.'>'.$image.'</a>';
        }

        return '<figure'.$this->identityAttributes($props).'>'.$image.($caption !== '' ? '<figcaption>'.$caption.'</figcaption>' : '').'</figure>';
    }

    /**
     * @param array<string, mixed> $props
     */
    private function identityAttributes(array $props): string
    {
        $attrs = '';
        $id = trim((string) ($props['css_id'] ?? ''));
        $class = trim((string) ($props['css_class'] ?? ''));

        if ($id !== '' && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id)) {
            $attrs .= ' id="'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'"';
        }

        if ($class !== '') {
            $attrs .= ' class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'"';
        }

        return $attrs;
    }

    /**
     * @param array<int, string> $allowed
     */
    private function allowedTag(string $tag, array $allowed): string
    {
        $tag = strtolower(trim($tag));

        return in_array($tag, $allowed, true) ? $tag : $allowed[0];
    }

    /**
     * @param array<int, string> $allowed
     */
    private function allowedValue(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
