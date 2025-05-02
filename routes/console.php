<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:listen --tries=3 --timeout=60')
    ->everyMinute()
    ->runInBackground();
