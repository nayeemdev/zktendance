<?php

use App\Http\Controllers\AdmsController;
use Illuminate\Support\Facades\Route;

Route::prefix('iclock')->controller(AdmsController::class)->group(function () {
    Route::get('cdata', 'handshake');
    Route::post('cdata', 'receive');
    Route::get('getrequest', 'commands');
    Route::post('devicecmd', 'commandResult');
});
