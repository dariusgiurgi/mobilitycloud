<?php

namespace App\Support;

use App\Models\ProjectFinalArchive;

class FinalArchiveHelp
{
    /** Explain the same state and permissions used by the download controls. */
    public static function build(?ProjectFinalArchive $archive, bool $ready, bool $canConfigure, int $selected, bool $locked): array
    {
        if ($locked) {
            return self::answer('locked', 'Export is locked', [
                'Check the payment message beside the download controls.',
                'Contact support if the payment has already been confirmed but the export remains locked.',
            ]);
        }

        if ($selected === 0) {
            return self::answer('empty', 'Choose content first', [
                $canConfigure ? 'Select at least one category in Step 2. Your choice saves automatically.' : 'Ask the project owner or an editor to select the categories for this archive.',
                'Return to Step 3 after a category has been selected.',
            ], '#archive-selection', 'Go to content selection');
        }

        if ($ready) {
            return self::answer('ready', 'Your ZIP is ready', [
                'Select Download ZIP in Step 3 and check your browser’s downloads list.',
                'Compare the Prepared date with your latest edits. A saved ZIP does not update when the project changes.',
                $canConfigure ? 'If you need later edits, select Prepare fresh archive and wait for the new download.' : 'If you need later edits, ask the project owner or an editor to prepare a fresh archive.',
                'If the download no longer works, refresh this page to check whether the temporary copy has expired. Contact support if it is still shown as ready and the problem continues.',
            ]);
        }

        if (in_array($archive?->status, [ProjectFinalArchive::STATUS_QUEUED, ProjectFinalArchive::STATUS_PROCESSING], true)) {
            return self::answer($archive->status, $archive->status === ProjectFinalArchive::STATUS_QUEUED ? 'Your ZIP is waiting to start' : 'Your ZIP is being prepared', [
                'The archive is not downloadable until preparation finishes. This page checks its status automatically.',
                'You can leave the page and return later. There is no need to submit another preparation request.',
                'If the status stays unchanged after you return, refresh the page. Contact support with the project name and the status shown if it still does not progress.',
            ]);
        }

        if ($archive?->status === ProjectFinalArchive::STATUS_FAILED) {
            return self::answer('failed', 'Preparation needs attention', [
                'Read the failure message in Step 3. The project’s source records have not been deleted.',
                $canConfigure ? 'Select Prepare again to retry. If the same failure returns, contact support with the project name and the displayed message.' : 'Ask the project owner or an editor to retry with Prepare again and share the displayed message with them.',
                'Do not remove project evidence just to make the archive complete. If a file needs repair, keep the original and ask support for help.',
            ]);
        }

        if ($archive?->hasExpired()) {
            return self::answer('expired', 'The download has expired', [
                'The ZIP download is temporary. Your project’s source data is still available.',
                $canConfigure ? 'Select Prepare again in Step 3 and wait for a new ZIP.' : 'Ask the project owner or an editor to select Prepare again.',
                'Download the new copy when it is ready. An old download link does not become valid again.',
            ]);
        }

        if ($archive?->status === ProjectFinalArchive::STATUS_READY) {
            return self::answer('unavailable', 'The prepared file is unavailable', [
                'The saved ZIP cannot currently be downloaded. This does not mean the project’s source data was removed.',
                $canConfigure ? 'Select Prepare again to create another copy from the selected categories.' : 'Ask the project owner or an editor to prepare another copy.',
                'If the replacement is also unavailable, contact support with the project name and the displayed status.',
            ]);
        }

        return self::answer('not-prepared', 'Prepare your first ZIP', [
            'Review the categories selected in Step 2. The counts describe source records, not the number of files in the ZIP.',
            $canConfigure ? 'Select Prepare archive in Step 3. The ZIP is built in the background.' : 'Ask the project owner or an editor to select Prepare archive. Your access lets you download the completed copy.',
            'Wait until Download ZIP appears, then download and open INDEX.html inside the extracted archive.',
        ]);
    }

    private static function answer(string $state, string $title, array $steps, string $anchor = '#archive-download', string $action = 'Go to download controls'): array
    {
        return compact('state', 'title', 'steps', 'anchor', 'action');
    }
}
