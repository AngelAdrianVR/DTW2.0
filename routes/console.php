<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Revisa si hay publicaciones en landing page que tenfan fecha de expiracion para quitarlos o despubvlicarlos
Schedule::command('content:unpublish-expired')->dailyAt('01:00');

