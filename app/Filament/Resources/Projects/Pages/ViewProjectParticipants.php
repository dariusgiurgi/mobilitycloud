<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Support\AuthorizesProjectManagement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ViewProjectParticipants extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-participants';

    // Modal state
    public bool $showModal = false;

    public ?int $editingId = null;

    // Bound form fields
    public array $data = [];

    // Filtre lista
    public string $filterSearch = '';

    public string $filterRole = '';

    public string $filterCountry = '';

    public string $filterOrg = '';

    public bool $filterMinorsOnly = false;

    public bool $filterIncompleteOnly = false;

    public bool $showPartFilters = false;

    // Upload de documente
    public $uploadFile = null;          // fisierul temporar Livewire

    public string $uploadType = 'gdpr'; // tipul ales pentru upload

    public ?int $attachParticipantId = null; // participantul pentru care incarcam

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->record->name.' — Participants';
    }

    public function getParticipants()
    {
        $all = Participant::where('project_id', $this->record->id)
            ->with('attachments')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $all->filter(function (Participant $p) {
            // Cautare nume
            if ($this->filterSearch !== '') {
                $needle = mb_strtolower($this->filterSearch);
                if (! str_contains(mb_strtolower($p->fullName()), $needle)) {
                    return false;
                }
            }
            if ($this->filterRole !== '' && $p->role !== $this->filterRole) {
                return false;
            }
            if ($this->filterCountry !== '' && $p->country !== $this->filterCountry) {
                return false;
            }
            if ($this->filterOrg !== '' && $p->partner_organisation !== $this->filterOrg) {
                return false;
            }
            if ($this->filterMinorsOnly && ! $p->isMinor()) {
                return false;
            }
            if ($this->filterIncompleteOnly && $p->hasCompleteDocs()) {
                return false;
            }

            return true;
        })->values();
    }

    public function getRoles(): array
    {
        return Participant::ROLES;
    }

    /** Statistici pe toti participantii proiectului (nu cei filtrati). */
    public function getStats(): array
    {
        $all = Participant::where('project_id', $this->record->id)->with('attachments')->get();

        return [
            'total' => $all->count(),
            'minors' => $all->filter(fn ($p) => $p->isMinor())->count(),
            'fo' => $all->where('fewer_opportunities', true)->count(),
        ];
    }

    /** Tarile distincte existente la participanti (pentru filtru). */
    public function getCountriesInUse(): array
    {
        return Participant::where('project_id', $this->record->id)
            ->whereNotNull('country')->where('country', '!=', '')
            ->distinct()->orderBy('country')->pluck('country')->all();
    }

    /** Organizatiile distincte existente la participanti (pentru filtru). */
    public function getOrgsInUse(): array
    {
        return Participant::where('project_id', $this->record->id)
            ->whereNotNull('partner_organisation')->where('partner_organisation', '!=', '')
            ->distinct()->orderBy('partner_organisation')->pluck('partner_organisation')->all();
    }

    public function activeParticipantFilters(): int
    {
        return collect([
            $this->filterSearch, $this->filterRole, $this->filterCountry, $this->filterOrg,
        ])->filter(fn ($v) => $v !== '')->count()
        + ($this->filterMinorsOnly ? 1 : 0)
        + ($this->filterIncompleteOnly ? 1 : 0);
    }

    public function clearParticipantFilters(): void
    {
        $this->filterSearch = '';
        $this->filterRole = '';
        $this->filterCountry = '';
        $this->filterOrg = '';
        $this->filterMinorsOnly = false;
        $this->filterIncompleteOnly = false;
    }

    public function getPartnerOrgs(): array
    {
        return collect($this->record->partners)
            ->filter(fn ($p) => ! empty($p['name']))
            ->map(fn ($p) => [
                'name' => $p['name'],
                'label' => $p['name'].(! empty($p['is_coordinator']) ? ' (coordinator)' : ''),
            ])
            ->values()
            ->all();
    }

    protected function blankData(): array
    {
        return [
            'first_name' => '', 'last_name' => '', 'birth_date' => null,
            'nationality' => '', 'gender' => '',
            'partner_organisation' => '', 'country' => '', 'role' => 'participant',
            'email' => '', 'phone' => '', 'address' => '',
            'medical_conditions' => '', 'allergies' => '', 'dietary_restrictions' => '',
            'special_needs' => '', 'fewer_opportunities' => false,
            'guardian_name' => '', 'guardian_contact' => '',
        ];
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->data = $this->blankData();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $p = Participant::where('project_id', $this->record->id)->find($id);
        if (! $p) {
            return;
        }

        $this->editingId = $p->id;
        $this->attachParticipantId = $p->id;
        $this->data = [
            'first_name' => $p->first_name, 'last_name' => $p->last_name,
            'birth_date' => $p->birth_date?->format('Y-m-d'),
            'nationality' => $p->nationality, 'gender' => $p->gender,
            'partner_organisation' => $p->partner_organisation, 'country' => $p->country,
            'role' => $p->role,
            'email' => $p->email, 'phone' => $p->phone, 'address' => $p->address,
            'medical_conditions' => $p->medical_conditions, 'allergies' => $p->allergies,
            'dietary_restrictions' => $p->dietary_restrictions, 'special_needs' => $p->special_needs,
            'fewer_opportunities' => (bool) $p->fewer_opportunities,
            'guardian_name' => $p->guardian_name, 'guardian_contact' => $p->guardian_contact,
        ];
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'data.first_name' => 'required|string|max:255',
            'data.last_name' => 'required|string|max:255',
            'data.email' => 'nullable|email|max:255',
            'data.birth_date' => 'nullable|date',
        ], [], [
            'data.first_name' => 'first name',
            'data.last_name' => 'last name',
        ]);

        $payload = $this->data;
        $payload['fewer_opportunities'] = (bool) ($payload['fewer_opportunities'] ?? false);
        $payload['birth_date'] = $payload['birth_date'] ?: null;

        if ($this->editingId) {
            $p = Participant::where('project_id', $this->record->id)->find($this->editingId);
            if ($p) {
                $p->update($payload);
            }
        } else {
            $payload['project_id'] = $this->record->id;
            Participant::create($payload);
        }

        $this->closeModal();

        Notification::make()->title('Participant saved')->success()->send();
    }

    public function deleteParticipant(int $id): void
    {
        $this->authorizeProjectManagement();
        Participant::where('project_id', $this->record->id)->where('id', $id)->delete();
        Notification::make()->title('Participant removed')->success()->send();
    }

    public function setGdprConsent(int $id): void
    {
        $this->authorizeProjectManagement();
        $p = Participant::where('project_id', $this->record->id)->find($id);
        if (! $p) {
            return;
        }
        $p->gdpr_consented_at = $p->gdpr_consented_at ? null : now();
        $p->save();
    }

    public function getDocTypes(): array
    {
        return ParticipantAttachment::TYPES;
    }

    /** Atasamentele unui participant, indexate pe tip. */
    public function attachmentsFor(int $participantId)
    {
        return ParticipantAttachment::where('participant_id', $participantId)
            ->get()
            ->keyBy('type');
    }

    public function uploadAttachment(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'uploadFile' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx', // 10 MB
            'uploadType' => 'required|in:'.implode(',', array_keys(ParticipantAttachment::TYPES)),
        ], [], [
            'uploadFile' => 'file',
        ]);

        $participant = Participant::where('project_id', $this->record->id)
            ->find($this->attachParticipantId);

        if (! $participant) {
            $this->reset(['uploadFile']);

            return;
        }

        // Numele generat: {prefix}_{Nume}_{Prenume}.{ext}, fara diacritice.
        $prefix = ParticipantAttachment::FILE_PREFIXES[$this->uploadType] ?? 'document';
        $namePart = Str::ascii($participant->last_name.'_'.$participant->first_name);
        $namePart = preg_replace('/[^A-Za-z0-9_]+/', '_', $namePart);
        $namePart = trim($namePart, '_');
        $ext = $this->uploadFile->getClientOriginalExtension() ?: 'dat';
        $filename = $prefix.'_'.$namePart.'.'.strtolower($ext);

        $dir = 'participant-attachments/'.$participant->id;

        // "Doar ultimul fisier per tip": stergem atasamentul vechi de acelasi tip.
        $existing = ParticipantAttachment::where('participant_id', $participant->id)
            ->where('type', $this->uploadType)
            ->first();
        if ($existing) {
            $existing->delete(); // booted() sterge si fisierul de pe disk
        }

        // Salvam fisierul nou cu numele generat.
        $path = $this->uploadFile->storeAs($dir, $filename, 'local');

        ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => $this->uploadType,
            'path' => $path,
            'disk' => 'local',
            'original_name' => $this->uploadFile->getClientOriginalName(),
            'size' => $this->uploadFile->getSize(),
        ]);

        $this->reset(['uploadFile']);

        Notification::make()
            ->title('Document uploaded')
            ->success()
            ->send();
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->authorizeProjectManagement();
        $att = ParticipantAttachment::find($attachmentId);
        // Verificam ca apartine unui participant din acest proiect.
        if ($att && $att->participant && $att->participant->project_id === $this->record->id) {
            $att->delete();
            Notification::make()->title('Document removed')->success()->send();
        }
    }
}
