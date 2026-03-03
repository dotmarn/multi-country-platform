<?php

use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SchemaController;
use App\Http\Controllers\Api\StepsController;
use Illuminate\Support\Facades\Route;

Route::get('/checklists', ChecklistController::class);
Route::get('/steps', StepsController::class);
Route::get('/employees', EmployeeController::class);
Route::get('/schema/{step_id}', SchemaController::class);
