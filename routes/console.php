<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('subscriptions:send-alerts')
    ->dailyAt('08:15')
    ->withoutOverlapping();

Schedule::command('project-payments:mark-overdue')
    ->dailyAt('08:30')
    ->withoutOverlapping();

Schedule::command('mobilitycloud:backup')
    ->dailyAt('02:15')
    ->withoutOverlapping();

Schedule::command('mobilitycloud:prelaunch-audit')
    ->dailyAt('09:00')
    ->withoutOverlapping();
