<?php

namespace Tests\Feature;

use App\Support\GuideKnowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_and_mobi_share_answers_with_valid_unique_sources(): void
    {
        $chapters = GuideKnowledge::chapters('0.75%');
        $answers = collect($chapters)->flatMap(fn ($chapter) => $chapter['items']);
        $this->assertCount($answers->count(), $answers->unique('id'));
        $this->assertCount(count($chapters), collect($chapters)->unique('id'));
        $this->assertStringContainsString('0.75%', $answers->firstWhere('id', 'price')['answer']);
        $page = $this->get('/guide')->assertOk()->assertSee('Ask Mobi')->assertSee('No external AI service');

        foreach ($answers as $answer) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $answer['id']);
            $this->assertNotEmpty($answer['steps']);
            foreach ($answer['related'] as $relatedId) {
                $this->assertNotSame($answer['id'], $relatedId);
                $this->assertTrue($answers->contains('id', $relatedId));
            }
            $page->assertSee('id="answer-'.$answer['id'].'"', false);
        }

        $page->assertDontSee('{global_fee}');
    }
}
