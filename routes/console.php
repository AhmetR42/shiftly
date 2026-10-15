<?php

use App\Models\Team;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('demo:prune {--hours=24}', function () {
    $count = Team::where('is_demo', true)
        ->where('created_at', '<', now()->subHours((int) $this->option('hours')))
        ->get()
        ->each->delete()
        ->count();

    $this->info("Removed {$count} demo team(s).");
})->purpose('Delete demo teams older than the given number of hours');

Schedule::command('demo:prune')->hourly();
