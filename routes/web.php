<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('necta-results',  [App\Http\Controllers\NectaResultsQueryController::class, 'index']);
Route::get('nida-names',  [App\Http\Controllers\NidaVerificationController::class, 'nida']);

Route::get('necta-api-key',  [App\Http\Controllers\NectaResultsQueryController::class, 'get_exam_results_test']);
