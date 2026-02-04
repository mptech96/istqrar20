<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CompanyController;
use App\Http\Controllers\V1\BranchController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\VisitController;
use App\Http\Controllers\V1\CriteriaController;
use App\Http\Controllers\V1\ChecklistController;
use App\Http\Controllers\V1\QuestionController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1.0
|--------------------------------------------------------------------------
*/

// Public Routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::prefix('companies')->controller(CompanyController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('/{company}', 'update');
        Route::get('/{company}', 'show');
        Route::delete('/{company}', 'destroy');
        Route::post('/{id}/restore', 'restore');
    });
    Route::prefix('branches')->controller(BranchController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/create', 'create');
        Route::post('/', 'store');
        Route::put('/{branch}', 'update');
        Route::get('/{branch}', 'show');
        Route::delete('/{branch}', 'destroy');
        Route::post('/{id}/restore', 'restore');
    });
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/create', 'create');
        Route::post('/', 'store');
        Route::get('/{user}/documents-expiry', 'documentsExpiry');
        Route::put('/{user}', 'update');
        Route::get('/{user}', 'show');
        Route::delete('/{user}', 'destroy');
        Route::post('/{id}/restore', 'restore');
    });
    
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('logout', 'logout')->middleware('auth:sanctum');
        Route::get('me', 'me')->middleware('auth:sanctum');
    });
    
    Route::prefix('criterias')->controller(CriteriaController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/categories', 'categories');
        Route::post('/', 'store');
        Route::get('/{criteria}', 'show');
        Route::put('/{criteria}', 'update');
        Route::delete('/{criteria}', 'destroy');
        Route::post('/{id}/restore', 'restore');
        Route::post('/{criteria}/evaluate', 'evaluate');
    });
    
    Route::prefix('checklists')->controller(ChecklistController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('{checklist}', 'show');
        Route::post('/', 'store');
        Route::put('/{checklist}', 'update');
        Route::delete('/{checklist}', 'destroy');
        Route::post('/{id}/restore', 'restore');
        
        Route::get('/industry-types', 'industryTypes');
        Route::get('{checklist}/statistics', 'statistics');
        Route::post('/{checklist}/duplicate', 'duplicate');
        
        Route::prefix('{checklist}/questions')->controller(QuestionController::class)->group(function () {
            Route::get('', 'index');
            Route::get('types', 'questionTypes');
            Route::put('reorder', 'reorder');
            Route::post('/', 'store');
            Route::get('{question}', 'show');
            Route::put('/{question}', 'update');
            Route::delete('/{question}', 'destroy');
            Route::post('/{id}/restore', 'restore');
            
            Route::post('{question}/duplicate', 'reorder');
            Route::get('{question}/evaluate', 'evaluate');
            
        });

        
    });
    
    
    Route::get('/docs', function () {
        return redirect('/api/v1/documentation');
    });
});


