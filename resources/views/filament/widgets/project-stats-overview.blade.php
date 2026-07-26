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
            padding: 1.15rem 1.25rem;
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

        .mc-project-stats-overview .mc-project-stats .fi-wi-stats-overview-stat-label {
            font-size: .9rem;
            line-height: 1.25;
        }

        .mc-project-stats-overview .mc-project-stats .fi-wi-stats-overview-stat-value {
            font-size: clamp(1.7rem, 2.4vw, 2.15rem);
            line-height: 1.05;
        }

        .mc-project-stats-overview .mc-project-stats .fi-wi-stats-overview-stat-description {
            font-size: .9rem;
            line-height: 1.35;
        }

        .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-value {
            font-size: clamp(1.45rem, 2.5vw, 2.05rem);
            line-height: 1.05;
            letter-spacing: -.035em;
            white-space: nowrap;
        }

        .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-label {
            white-space: nowrap;
        }

        @media (min-width: 1280px) {
            .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-value {
                font-size: clamp(1.75rem, 2vw, 2.25rem);
            }
        }

        @media (max-width: 760px) {
            .mc-project-stats-overview .mc-project-stats {
                padding: 1rem;
            }

            .mc-project-stats-overview .mc-project-stats-money .fi-wi-stats-overview-stat-value {
                font-size: clamp(1.35rem, 5.2vw, 1.8rem);
            }
        }
    </style>

    {{ $this->content }}
</x-filament-widgets::widget>
