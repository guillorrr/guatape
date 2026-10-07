<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_what_the_editor_produces(): void
    {
        $html = '<h2>Title</h2><p><strong>bold</strong> <em>it</em> <u>u</u> <s>s</s></p>'
            .'<ol><li data-list="bullet">one</li></ol><blockquote>q</blockquote>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_strips_scripts_handlers_styles_and_unsafe_links(): void
    {
        $clean = HtmlSanitizer::clean(
            '<p onclick="steal()" style="color:red">hi<script>alert(1)</script></p>'
            .'<img src=x onerror="alert(1)"><iframe src="https://evil.test"></iframe>'
            .'<a href="javascript:alert(1)">bad</a><a href="https://ok.test">ok</a>'
        );

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('style', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('<a href="https://ok.test" rel="noopener noreferrer">ok</a>', $clean);
    }

    public function test_empty_documents_become_null(): void
    {
        $this->assertNull(HtmlSanitizer::clean('<p><br></p>'));
        $this->assertNull(HtmlSanitizer::clean('   '));
        $this->assertNull(HtmlSanitizer::clean(null));
    }
}
