<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatRoleController;
use App\Http\Controllers\CatEmployeeController;
use App\Http\Controllers\CatUserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    // Rutas para el CRUD de CatRole
    Route::prefix('roles')->group(function () {
        Route::get('/', [CatRoleController::class, 'index']); // GET - Listar roles
        Route::post('/', [CatRoleController::class, 'store']); // POST - Crear role
        Route::get('/{id}', [CatRoleController::class, 'show']); // GET - Ver un role
        Route::put('/{id}', [CatRoleController::class, 'update']); // PUT - Actualizar role
        Route::delete('/{id}', [CatRoleController::class, 'destroy']); // DELETE - Desactivar role
        Route::patch('/{id}/restore', [CatRoleController::class, 'restore']); // PATCH - Restaurar role
    });

    // Rutas para el CRUD de CatEmployee
    Route::prefix('employees')->group(function () {
        Route::get('/', [CatEmployeeController::class, 'index']); // GET - Listar empleados
        Route::post('/', [CatEmployeeController::class, 'store']); // POST - Crear empleado
        Route::get('/{id}', [CatEmployeeController::class, 'show']); // GET - Ver un empleado
        Route::put('/{id}', [CatEmployeeController::class, 'update']); // PUT - Actualizar empleado
        Route::delete('/{id}', [CatEmployeeController::class, 'destroy']); // DELETE - Desactivar empleado
        Route::patch('/{id}/restore', [CatEmployeeController::class, 'restore']); // PATCH - Restaurar empleado
    });

    // Rutas para el CRUD de CatUser
    Route::prefix('users')->group(function () {
        Route::get('/', [CatUserController::class, 'index']); // GET - Listar usuarios
        Route::post('/', [CatUserController::class, 'store']); // POST - Crear usuario
        Route::get('/{id}', [CatUserController::class, 'show']); // GET - Ver un usuario
        Route::put('/{id}', [CatUserController::class, 'update']); // PUT - Actualizar usuario
        Route::delete('/{id}', [CatUserController::class, 'destroy']); // DELETE - Desactivar usuario
        Route::patch('/{id}/restore', [CatUserController::class, 'restore']); // PATCH - Restaurar usuario
    });
});
