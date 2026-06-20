<?php

namespace App\Support;

use App\Models\ProjectDocument;

trait GeneratesAttendanceSheets
{
    public bool $showAttendanceModal = false;

    public string $attendanceActivity = '';

    public ?string $attendanceDate = null;

    public string $attendanceLocation = '';

    public function openAttendanceGenerator(): void
    {
        $this->authorizeProjectManagement();
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
        $this->authorizeProjectManagement();
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
}
