<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PlainTextRenderingTest extends TestCase
{
    public function test_blade_escapes_plain_text_exactly_once(): void
    {
        $html = Blade::render(
            '<button data-title="{{ $title }}">{{ $title }}</button>',
            ['title' => 'Sales & Profit "Target" <Q1>']
        );

        $this->assertStringContainsString(
            'Sales &amp; Profit &quot;Target&quot; &lt;Q1&gt;',
            $html
        );
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;quot;', $html);
    }
}
