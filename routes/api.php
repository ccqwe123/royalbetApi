<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logoutuser']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/new-password/{user_id}/{reset_token}', [AuthController::class, 'newPassword']);
Route::get('/new-password/{user_id}/{reset_token}', [AuthController::class, 'checkUserforgotPassword']);
//register
Route::post('/register', [AuthController::class, 'register']);
Route::get('/verify/{id}', [AuthController::class, 'verifyUser']);
Route::post('/verify', [AuthController::class, 'verify']);

Route::group(['middleware' => 'auth:api', 'scopes:check-status,user-controlz'], function() {
    Route::apiResource('users', UserController::class);
    
    Route::get('account-details', [UserController::class, 'accountDetails']);
    Route::get('transactions', [UserController::class, 'transactions']);
    Route::post('account-details', [UserController::class, 'accountDetailsPost']);
    Route::post('deposit', [UserController::class, 'deposit']);
    Route::post('withdraw', [UserController::class, 'withdraw']);
    Route::get('getUser/{id}', [UserController::class, 'getUser']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::get('/change-mobile', [UserController::class, 'changeMobile']);
    Route::post('/change-mobile', [UserController::class, 'changeMobilePost']);
    Route::post('/betslip/sports/{sport_id}', [UserController::class, 'userBet']);
    
});

