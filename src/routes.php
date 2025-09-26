<?php

use Illuminate\Support\Facades\Route;
use Yatilabs\ApiAccess\Controllers\ApiKeyController;
use Yatilabs\ApiAccess\Controllers\DomainController;

$routeConfig = config('api-access.routes', [
    'prefix' => 'api-access',
    'middleware' => ['web', 'auth'],
    'name_prefix' => 'api-access.',
]);

Route::group([
    'prefix' => $routeConfig['prefix'],
    'middleware' => $routeConfig['middleware'],
    'as' => $routeConfig['name_prefix'],
], function () {
    
    // API Keys Management
    Route::get('/', [ApiKeyController::class, 'index'])->name('index');
    Route::get('/api-keys/create', [ApiKeyController::class, 'create'])->name('api-keys.create');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::get('/api-keys/{id}/edit', [ApiKeyController::class, 'edit'])->name('api-keys.edit');
    Route::post('/api-keys/{id}/update', [ApiKeyController::class, 'update'])->name('api-keys.update');
    Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    Route::post('/api-keys/{id}/regenerate-secret', [ApiKeyController::class, 'regenerateSecret'])->name('api-keys.regenerate-secret');
    Route::post('/api-keys/{id}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('api-keys.toggle-status');
    
    // Domain Restrictions
    Route::get('/domains/create', [DomainController::class, 'create'])->name('domains.create');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
    Route::get('/domains/{id}/edit', [DomainController::class, 'edit'])->name('domains.edit');
    Route::post('/domains/{id}/update', [DomainController::class, 'update'])->name('domains.update');
    Route::delete('/domains/{id}', [DomainController::class, 'destroy'])->name('domains.destroy');
    
    // API Logs
    Route::get('/logs', [ApiKeyController::class, 'logs'])->name('logs.index');
    Route::get('/logs/{id}', [ApiKeyController::class, 'logDetail'])->name('logs.detail');
    Route::get('/logs/filters/options', [ApiKeyController::class, 'logFilters'])->name('logs.filters');
    
});