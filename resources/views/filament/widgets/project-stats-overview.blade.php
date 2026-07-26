@php
    $pollingInterval = $this->getPollingInterval();
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
                'mc-project-stats-overview',
            ])
    "
>
    <style>
        .mc-project-stats-overview .mc-project-stats {
            min-width: 0;
        }

        .mc-project-stats-overview .mc-project-stats .fi-wi-stats-overview-stat-content {
            min-width: 0;
        }

        .mc-project-stats-overview .mc-project-stats .fi-wi-stats-overview-stat-label,
        .mc-project-stats-overview .mc-project-stats .fi-wi-stats-overview-stat-description span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-value {
            font-size: clamp(1.55rem, 3vw, 2.35rem);
            line-height: 1.05;
            letter-spacing: -.04em;
            white-space: nowrap;
        }

        .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-label {
            white-space: nowrap;
        }

        @media (min-width: 1280px) {
            .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-value {
                font-size: clamp(1.85rem, 2.6vw, 2.75rem);
            }
        }

        @media (max-width: 760px) {
            .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-value {
                font-size: clamp(1.45rem, 6vw, 2rem);
            }
        }
    </style>

    {{ $this->content }}
</x-filament-widgets::widget>
