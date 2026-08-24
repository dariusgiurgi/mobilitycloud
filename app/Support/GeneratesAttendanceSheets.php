<?php

namespace App\Support;

use App\Models\ProjectDocument;
use Filament\Notifications\Notification;

trait GeneratesAttendanceSheets
{
    public bool $showAttendanceModal = false;

    public string $attendanceActivity = '';

    public ?string $attendanceDate = null;

    public string $attendanceLocation = '';

    public function openAttendanceGenerator(): void
    {
        $this->authorizeManagementModuleMutation('documents', 'documents', 'Documents');

        if ($this->record->exportsLockedUntilPayment()) {
            $this->notifyPaymentLockedExport();

            return;
        }

        $this->attendanceActivity = $this->attendanceActivity ?: $this->record->name;
        $this->attendanceDate = $this->attendanceDate
            ?: $this->record->mobility_start_date?->format('Y-m-d')
            ?: now()->toDateString();
        $this->showAttendanceModal = true;
    }

    public function closeAttendanceGenerator(): void
    {
        $this->showAttendanceModal = false;
    }

    public function generateAttendanceSheet()
    {
        $this->authorizeManagementModuleMutation('documents', 'documents', 'Documents');

        if ($this->record->exportsLockedUntilPayment()) {
            $this->notifyPaymentLockedExport();

            return null;
        }

        $this->validate([
            'attendanceActivity' => 'required|string|max:255',
            'attendanceDate' => 'required|date',
            'attendanceLocation' => 'nullable|string|max:255',
        ]);

        $document = ProjectDocument::create([
            'project_id' => $this->record->id,
            'type' => ProjectDocument::TYPE_ATTENDANCE,
            'title' => 'Attendance list - '.$this->attendanceActivity,
            'activity_title' => $this->attendanceActivity,
            'activity_date' => $this->attendanceDate,
            'location' => $this->attendanceLocation ?: null,
            'metadata' => ['grouping' => 'partner_organisation', 'sort' => 'last_name_first_name'],
            'generated_at' => now(),
        ]);

        $this->showAttendanceModal = false;

        return redirect()->route('project-documents.attendance', [$this->record, $document]);
    }

    private function notifyPaymentLockedExport(): void
    {
        Notification::make()
            ->title('Exports locked until payment is confirmed')
            ->body('You can keep organising project files. Generated PDFs, signed-copy downloads and final archives unlock after the fiscal invoice is marked as paid.')
            ->warning()
            ->send();
    }
}
