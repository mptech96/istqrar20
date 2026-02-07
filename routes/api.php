<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1.0
|--------------------------------------------------------------------------
*/

// Public Routes

Route::namespace('App\Http\Controllers\V1')->group(function (){
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
            Route::post('logout', 'logout')->middleware('token.cookie');
            Route::get('me', 'me')->middleware('token.cookie');
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
        
        
        Route::prefix('visits')->controller(VisitController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/calendar', 'calendar');
            Route::get('/create', 'create');
            Route::post('', 'store');
            Route::put('/{visit}', 'update');
            Route::get('/{visit}', 'show');
            Route::delete('/{visit}', 'destroy');
            Route::post('/{id}/restore', 'restore');
            Route::post('/{visit}/start', 'start');
            Route::get('auditor/{auditorId}', 'auditorVisits');
            Route::get('branch/{branchId}', 'branchVisits');
            Route::post('/{visit}/complete', 'complete');
            //Route::get('/{visit}/statistics', 'statistics');
            
            Route::prefix('{visit}/questions')->group(function () {
                Route::get('', 'questions');
            });
            
            Route::prefix('{visit}/answers')->controller(AnswerController::class)->group(function () {
                Route::get('', 'index');
                Route::post('', 'store');
                Route::get('/{answer}', 'show');
                Route::put('/{answer}', 'update');
                Route::delete('/{answer}', 'destroy');
                Route::post('/{id}/restore', 'restore');
                Route::post('bulk', 'bulkStore');
            });
            
            
        });

        
        Route::get('/docs', function () {
            return redirect('/api/v1/documentation');
        });
        
    });
});


