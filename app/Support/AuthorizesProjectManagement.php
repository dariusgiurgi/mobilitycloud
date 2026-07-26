<?php

namespace App\Support;

use App\Models\ProjectModuleLock;
use App\Models\ProjectPresence;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait AuthorizesProjectManagement
{
    protected int $projectPresenceSeconds = 120;

    protected int $projectLockSeconds = 180;

    protected function authorizeProjectAccess(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canBeAccessedBy(auth()->user()),
            403
        );
    }

    protected function authorizeProjectModuleAccess(string $module): void
    {
        abort_unless(
            isset($this->record) && $this->record->canAccessProjectModule(auth()->user(), $module),
            404
        );
    }

    protected function authorizeProjectManagement(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canBeCollaboratedOnBy(auth()->user()),
            403
        );
    }

    protected function authorizeApplicationEditing(?string $lockKey = null, ?string $lockLabel = null): void
    {
        abort_unless(
            isset($this->record) && $this->record->canEditApplicationBy(auth()->user()),
            403
        );

        if ($lockKey) {
            $this->authorizeProjectEditingLock('write', $lockKey, $lockLabel);
        }
    }

    protected function authorizeManagementModuleAccess(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canViewManagementModulesBy(auth()->user()),
            404
        );
    }

    protected function authorizeManagementModuleMutation(?string $module = null, ?string $lockKey = null, ?string $lockLabel = null): void
    {
        abort_unless(
            isset($this->record)
            && (
                $module
                    ? $this->record->canManageProjectModule(auth()->user(), $module)
                    : $this->record->canManageManagementModulesBy(auth()->user())
            ),
            403
        );

        if ($module && $lockKey) {
            $this->authorizeProjectEditingLock($module, $lockKey, $lockLabel);
        }
    }

    protected function touchProjectCollaboration(string $module): void
    {
        $user = auth()->user();

        if (! $user || ! isset($this->record) || ! $this->record->canAccessProjectModule($user, $module)) {
            return;
        }

        $this->pruneStaleProjectCollaboration();

        ProjectPresence::query()->updateOrCreate(
            ['project_id' => $this->record->getKey(), 'user_id' => $user->id],
            ['module' => $module, 'last_seen_at' => now()],
        );

        ProjectModuleLock::query()
            ->active()
            ->where('project_id', $this->record->getKey())
            ->where('user_id', $user->id)
            ->where('module', $module)
            ->update(['expires_at' => now()->addSeconds($this->projectLockSeconds)]);
    }

    public function refreshProjectCollaboration(string $module): void
    {
        $this->touchProjectCollaboration($module);
    }

    public function startProjectEditing(string $module, string $lockKey, ?string $lockLabel = null): void
    {
        abort_unless(
            isset($this->record) && $this->record->canManageProjectModule(auth()->user(), $module),
            403
        );

        $this->touchProjectCollaboration($module);
        $this->claimProjectEditingLock($module, $lockKey, $lockLabel);
    }

    public function canManageProjectModuleNow(string $module, ?string $lockKey = null): bool
    {
        return isset($this->record)
            && $this->record->canManageProjectModule(auth()->user(), $module)
            && ($lockKey === null || $this->projectEditingLockConflict($module, $lockKey) === null);
    }

    public function projectPresenceState(string $module, ?string $lockKey = null): array
    {
        $presences = $this->activeProjectPresences($module);
        $lock = $this->activeProjectEditingLock($module, $lockKey);

        return [
            'presences' => $presences,
            'lock' => $lock,
            'locked_by_other' => $lock !== null && (int) $lock->user_id !== (int) auth()->id(),
            'locked_by_me' => $lock !== null && (int) $lock->user_id === (int) auth()->id(),
            'module_label' => $this->projectModuleLabel($module),
        ];
    }

    public function projectLocksForModule(string $module): Collection
    {
        if (! isset($this->record)) {
            return collect();
        }

        return ProjectModuleLock::query()
            ->active()
            ->where('project_id', $this->record->getKey())
            ->where('module', $module)
            ->with('user')
            ->get()
            ->keyBy('lock_key');
    }

    public function projectUserColor(?object $user): string
    {
        $palette = [
            '#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626',
            '#7c3aed', '#db2777', '#2563eb', '#16a34a', '#ea580c',
        ];

        $id = (int) ($user?->id ?? 0);

        return $palette[$id % count($palette)];
    }

    public function projectLockBadge(ProjectModuleLock $lock): array
    {
        $color = $this->projectUserColor($lock->user);

        return [
            'name' => $lock->user?->name ?: 'Another user',
            'color' => $color,
            'background' => $color.'14',
            'border' => $color.'66',
            'label' => $lock->lock_label,
        ];
    }

    protected function activeProjectPresences(?string $module = null): Collection
    {
        if (! isset($this->record)) {
            return collect();
        }

        return ProjectPresence::query()
            ->active($this->projectPresenceSeconds)
            ->where('project_id', $this->record->getKey())
            ->when($module, fn ($query) => $query->where('module', $module))
            ->where('user_id', '!=', auth()->id())
            ->with('user')
            ->latest('last_seen_at')
            ->get();
    }

    protected function authorizeProjectEditingLock(string $module, ?string $lockKey = null, ?string $lockLabel = null): void
    {
        if ($this->claimProjectEditingLock($module, $lockKey, $lockLabel)) {
            return;
        }

        $lock = $this->projectEditingLockConflict($module, $lockKey);
        $name = $lock?->user?->name ?: 'Another user';

        Notification::make()
            ->title(($lock?->lock_label ?: $this->projectModuleLabel($module)).' is being edited')
            ->body($name.' is editing this area right now. Try again in a moment.')
            ->warning()
            ->send();

        throw ValidationException::withMessages([
            'project_lock' => $name.' is editing '.($lock?->lock_label ?: $this->projectModuleLabel($module)).' right now.',
        ]);
    }

    protected function claimProjectEditingLock(string $module, ?string $lockKey = null, ?string $lockLabel = null, bool $notify = true): bool
    {
        $user = auth()->user();
        $lockKey ??= '__module__';

        if (! $user || ! isset($this->record)) {
            return false;
        }

        $conflict = $this->projectEditingLockConflict($module, $lockKey);

        if ($conflict) {
            if ($notify) {
                Notification::make()
                    ->title(($conflict->lock_label ?: $this->projectModuleLabel($module)).' is locked')
                    ->body(($conflict->user?->name ?: 'Another user').' is currently editing it.')
                    ->warning()
                    ->send();
            }

            return false;
        }

        ProjectModuleLock::query()->updateOrCreate(
            ['project_id' => $this->record->getKey(), 'module' => $module, 'lock_key' => $lockKey],
            ['user_id' => $user->id, 'lock_label' => $lockLabel, 'expires_at' => now()->addSeconds($this->projectLockSeconds)],
        );

        return true;
    }

    protected function projectEditingLockConflict(string $module, ?string $lockKey = null): ?ProjectModuleLock
    {
        if (! isset($this->record) || ! auth()->check()) {
            return null;
        }

        $lockKey ??= '__module__';

        return ProjectModuleLock::query()
            ->active()
            ->where('project_id', $this->record->getKey())
            ->where('module', $module)
            ->where('lock_key', $lockKey)
            ->where('user_id', '!=', auth()->id())
            ->with('user')
            ->first();
    }

    protected function activeProjectEditingLock(string $module, ?string $lockKey = null): ?ProjectModuleLock
    {
        if (! isset($this->record)) {
            return null;
        }

        $lockKey ??= '__module__';

        return ProjectModuleLock::query()
            ->active()
            ->where('project_id', $this->record->getKey())
            ->where('module', $module)
            ->where('lock_key', $lockKey)
            ->with('user')
            ->first();
    }

    protected function pruneStaleProjectCollaboration(): void
    {
        ProjectPresence::query()
            ->where('last_seen_at', '<', now()->subMinutes(10))
            ->delete();

        ProjectModuleLock::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }

    protected function projectModuleLabel(string $module): string
    {
        return match ($module) {
            'write' => 'Application',
            'estimate' => 'Budget estimate',
            'board' => 'Budget',
            'participants' => 'Participants',
            'mobility' => 'Mobility',
            'documents' => 'Documents',
            'edit' => 'Settings',
            default => str($module)->headline()->toString(),
        };
    }
}
