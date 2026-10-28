<?php

namespace Tests\Unit;

use App\Models\ProjectFinalArchive;
use App\Support\FinalArchiveHelp;
use Tests\TestCase;

class FinalArchiveHelpTest extends TestCase
{
    public function test_help_covers_download_states_without_offering_unsafe_retries(): void
    {
        $this->assertSame('empty', FinalArchiveHelp::build(null, false, true, 0, false)['state']);
        $this->assertSame('locked', FinalArchiveHelp::build(null, false, true, 1, true)['state']);
        $this->assertSame('not-prepared', FinalArchiveHelp::build(null, false, true, 1, false)['state']);

        foreach (['queued', 'processing', 'failed', 'expired'] as $status) {
            $help = FinalArchiveHelp::build(new ProjectFinalArchive(['status' => $status]), false, true, 1, false);
            $this->assertSame($status, $help['state']);
            if (in_array($status, ['queued', 'processing'], true)) {
                $this->assertStringNotContainsString('Prepare again', implode(' ', $help['steps']));
                $this->assertStringContainsString('no need to submit another', implode(' ', $help['steps']));
            }
        }

        $archive = new ProjectFinalArchive(['status' => 'ready', 'expires_at' => now()->addHour()]);
        $this->assertSame('ready', FinalArchiveHelp::build($archive, true, true, 1, false)['state']);
        $this->assertSame('unavailable', FinalArchiveHelp::build($archive, false, true, 1, false)['state']);
        $archive->expires_at = now()->subMinute();
        $this->assertSame('expired', FinalArchiveHelp::build($archive, false, true, 1, false)['state']);
    }

    public function test_read_only_visitors_are_directed_to_the_owner_for_preparation(): void
    {
        foreach ([null, 'ready', 'failed', 'expired'] as $status) {
            $archive = $status ? new ProjectFinalArchive(['status' => $status]) : null;
            $help = FinalArchiveHelp::build($archive, $status === 'ready', false, 1, false);
            $this->assertStringContainsString('ask the project owner or an editor', strtolower(implode(' ', $help['steps'])));
        }
        $empty = FinalArchiveHelp::build(null, false, false, 0, false);
        $this->assertStringContainsString('Ask the project owner', $empty['steps'][0]);
    }
}
