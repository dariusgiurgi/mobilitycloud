<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasColor, HasLabel
{
    case Writing = 'writing';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Revise = 'revise';
    case Approved = 'approved';
    case Active = 'active';
    case PaymentOverdue = 'payment_overdue';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Writing => 'Writing',
            self::Submitted => 'Submitted',
            self::Rejected => 'Rejected',
            self::Revise => 'Revising',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::PaymentOverdue => 'Payment overdue',
            self::Completed => 'Completed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Writing => 'gray',
            self::Submitted => 'info',
            self::Rejected => 'danger',
            self::Revise => 'warning',
            self::Approved => 'success',
            self::Active => 'primary',
            self::PaymentOverdue => 'danger',
            self::Completed => 'success',
        };
    }

    /**
     * Is this project still in the application-writing stage?
     * Drives whether the Budget module shows the Estimator or the Board,
     * and whether the Application module is editable or read-only.
     */
    public function isWritingStage(): bool
    {
        return in_array($this, [self::Writing, self::Revise], true);
    }

    /**
     * Has the project been approved and is now (or will be) managed?
     */
    public function isManagementStage(): bool
    {
        return in_array($this, [self::Approved, self::Active, self::PaymentOverdue, self::Completed], true);
    }

    /**
     * Statuses a project may move to next. Used to render transition buttons
     * in the Overview module. Settings still allows a manual override.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Writing => [self::Submitted, self::Approved],
            self::Submitted => [self::Approved, self::Rejected],
            self::Rejected => [self::Revise],
            self::Revise => [self::Submitted, self::Approved],
            self::Approved => [self::Active],
            self::Active => [self::Completed],
            self::PaymentOverdue => [],
            self::Completed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Counts against the Free plan's "1 application in progress" limit.
     * Only writing-stage projects occupy the slot (per architecture §9).
     */
    public function countsAgainstFreeLimit(): bool
    {
        return $this->isWritingStage();
    }
}
