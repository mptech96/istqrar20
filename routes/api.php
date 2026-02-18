<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1.0
|--------------------------------------------------------------------------
*/

// Public Routes

Route::namespace('App\Http\Controllers\V1')->middleware(['throttle:60,1', 'api'])->group(function (){
    Route::prefix('v1')->group(function () {
        Route::middleware(['api-token-priority'])->group(function () {
            // Authentication
            Route::prefix('companies')->controller(CompanyController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store')->middleware(['user.role:super_admin']);
                Route::put('/{company}', 'update')->middleware(['user.role:super_admin']);
                Route::get('/{company}', 'show');
                Route::delete('/{company}', 'destroy')->middleware(['user.role:super_admin']);
                Route::post('/{id}/restore', 'restore')->middleware(['user.role:super_admin']);
                Route::get('/{company}/statistics', 'statistics');
    
            });
            
            Route::prefix('branches')->controller(BranchController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/create', 'create')->middleware(['user.role:company_admin']);
                Route::post('/', 'store')->middleware(['user.role:company_admin']);
                Route::put('/{branch}', 'update')->middleware(['user.role:company_admin']);
                Route::get('/{branch}', 'show');
                Route::delete('/{branch}', 'destroy')->middleware(['user.role:company_admin']);
                Route::post('/{id}/restore', 'restore')->middleware(['user.role:company_admin']);
                Route::get('/{branch}/statistics', 'statistics');
            });
            
            Route::prefix('users')->controller(UserController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/create', 'create');
                Route::post('/', 'store')->middleware(['user.role:super_admin']);
                Route::get('/{user}/documents-expiry', 'documentsExpiry');
                Route::put('/{user}', 'update')->middleware(['user.role:super_admin']);
                Route::get('/{user}', 'show');
                Route::delete('/{user}', 'destroy')->middleware(['user.role:super_admin']);
                Route::post('/{id}/restore', 'restore')->middleware(['user.role:super_admin']);
                Route::get('/{user}/statistics', 'statistics');
            });
            
            Route::prefix('criterias')->middleware(['user.role:super_admin'])->controller(CriteriaController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/categories', 'categories');
                Route::post('/', 'store');
                Route::get('/{criteria}', 'show');
                Route::put('/{criteria}', 'update');
                Route::delete('/{criteria}', 'destroy');
                Route::post('/{id}/restore', 'restore');
                Route::post('/{criteria}/evaluate', 'evaluate');
            });
            
            Route::prefix('checklists')->middleware(['user.role:super_admin'])->controller(ChecklistController::class)->group(function () {
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
            
            Route::prefix('/questions')->controller(QuestionController::class)->group(function () {
                Route::get('create', 'create');
            });
    
            Route::prefix('visits')->controller(VisitController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/calendar', 'calendar');
                Route::get('/create', 'create')->middleware(['user.role:super_admin']);
                Route::post('', 'store')->middleware(['user.role:super_admin']);
                Route::put('/{visit}', 'update')->middleware(['user.role:super_admin']);
                Route::get('/{visit}', 'show');
                Route::get('/{visit}/analysis', 'visitAnalysis');
                Route::delete('/{visit}', 'destroy')->middleware(['user.role:super_admin']);
                Route::post('/{id}/restore', 'restore')->middleware(['user.role:super_admin']);
                Route::post('/{visit}/start', 'start')->middleware(['user.role:auditor']);
                Route::get('auditor/{auditorId}', 'auditorVisits');
                Route::get('branch/{branchId}', 'branchVisits');
                Route::post('/{visit}/complete', 'complete')->middleware(['user.role:auditor']);
                //Route::get('/{visit}/statistics', 'statistics');
                
                Route::prefix('{visit}/questions')->group(function () {
                    Route::get('', 'questions');
                });
                
                Route::prefix('{visit}/answers')->controller(AnswerController::class)->group(function () {
                    Route::get('', 'index');
                    Route::post('', 'store')->middleware(['user.role:super_admin']);
                    Route::get('/{answer}', 'show');
                    Route::put('/{answer}', 'update')->middleware(['user.role:super_admin']);
                    Route::delete('/{answer}', 'destroy')->middleware(['user.role:super_admin']);
                    Route::post('/{id}/restore', 'restore')->middleware(['user.role:super_admin']);
                    Route::post('bulk', 'bulkStore')->middleware(['user.role:super_admin']);
                });
                
                
            });
            
        });
        
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::middleware(['api-module-priority'])->group(function () {
                Route::post('login', 'login');
            });
            
            Route::middleware(['api-token-priority'])->group(function () {
                Route::post('logout', 'logout');
                Route::get('me', 'me');
            });
            
        });
        
        Route::get('/docs', function () {
            return redirect('/api/v1/documentation');
        });
    });
});


