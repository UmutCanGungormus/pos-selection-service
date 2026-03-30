<?php

use App\Jobs\SyncPosRatesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncPosRatesJob, 'sync')->hourly()->name('sync-pos-rates');
