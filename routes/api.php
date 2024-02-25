<?php

use App\Http\Controllers\Api\v1\Auth\AuthenticationController;
use App\Http\Controllers\Api\v1\ConversationController;
use App\Http\Controllers\Api\v1\MessageController;
use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('login', [AuthenticationController::class, 'login']);
Route::post('register', [AuthenticationController::class, 'register']);
Route::group(['prefix' => 'v1', 'middleware' => 'auth:sanctum'], function () {
    // @intelephense disable P1009
    Route::resource('users', UserController::class);
    Route::resource('conversations', ConversationController::class);
    Route::post('/messages/{conversation}', [MessageController::class, 'store']);
    Route::get('/messages/{conversation}', [MessageController::class, 'show']);
    Route::put('/messages/{message}', [MessageController::class, 'update']);
    Route::delete('/{conversation}/messages/{message}', [MessageController::class, 'delete']);
});

