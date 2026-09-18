<?php

namespace Tests\Unit;

use App\Models\OkrObjective;
use App\Support\PlainTextNormalizer;
use PHPUnit\Framework\TestCase;

class PlainTextNormalizerTest extends TestCase
{
    public function test_it_decodes_html_entities_until_plain_text_remains(): void
    {
        $this->assertSame(
            'Sales & Profit "Target" <Q1>',
            PlainTextNormalizer::normalize('Sales &amp;amp; Profit &amp;quot;Target&amp;quot; &lt;Q1&gt;')
        );
    }

    public function test_it_decodes_numeric_entities_and_removes_control_characters(): void
    {
        $this->assertSame(
            "A 'quoted' value B",
            PlainTextNormalizer::normalize("A &#039;quoted&#039;\u{00A0}value\u{0007} B")
        );
    }

    public function test_nullable_returns_null_for_empty_normalized_text(): void
    {
        $this->assertNull(PlainTextNormalizer::nullable('&nbsp;'));
    }

    public function test_it_preserves_line_breaks_in_objective_titles(): void
    {
        $this->assertSame(
            "Award Company\nยกระดับบริษัทสู่มาตรฐานสากล\n[Global Standard]",
            PlainTextNormalizer::normalize("Award Company\nยกระดับบริษัทสู่มาตรฐานสากล\n[Global Standard]")
        );
    }

    public function test_plain_text_model_cast_normalizes_writes_and_legacy_reads(): void
    {
        $objective = new OkrObjective();
        $objective->title = 'Sales &amp; Profit';

        $this->assertSame('Sales & Profit', $objective->getAttributes()['title']);

        $objective->setRawAttributes(['title' => 'Quality &amp;quot;Target&amp;quot;']);

        $this->assertSame('Quality "Target"', $objective->title);
    }
}
