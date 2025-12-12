<?php

use App\Http\Controllers\Backend\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('backend.pages.dashboard.index');
});

Route::prefix('admin')->name('admin.')->group(function() {
    Route::prefix('roles')->name('roles.')->group(function() {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/list', [RoleController::class, 'list'])->name('list');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/store', [RoleController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [RoleController::class, 'edit'])->name('edit');
        Route::post('/update/{id}', [RoleController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [RoleController::class, 'delete'])->name('delete');
    });
});