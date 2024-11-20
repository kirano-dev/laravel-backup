<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('kirano/backup', fn () => Artisan::call('app:backup'));