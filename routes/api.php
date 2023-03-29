<?php

use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['api']], function ($router) { 

Route::post('/savereview', [App\Http\Controllers\Api\ReviewController::class, 'save_review']);
Route::get('/getallreview', [App\Http\Controllers\Api\ReviewController::class, 'get_all_reviews']);
Route::delete('/delete-reviews/{id}', [App\Http\Controllers\Api\ReviewController::class, 'delete_reviews']);
Route::get('/get-review/{id}', [App\Http\Controllers\Api\ReviewController::class, 'get_review_by_id']);
Route::get('/get-review-to/{id}', [App\Http\Controllers\Api\ReviewController::class, 'get_all_review_to']);
Route::get('/get-review-by/{id}', [App\Http\Controllers\Api\ReviewController::class, 'get_all_review_by']);
Route::get('/most-recent-review', [App\Http\Controllers\Api\ReviewController::class, 'most_recent_reviews']);
Route::get('/search-all-reviews', [App\Http\Controllers\Api\ReviewController::class, 'search_all_reviews']);
Route::get('/most-liked-review', [App\Http\Controllers\Api\ReviewController::class, 'most_liked_reviews']);

});


Route::group(['middleware' => ['api']], function ($router) { 

Route::post('/register', [App\Http\Controllers\Api\UserController::class, 'user_register']);
Route::post('/login', [App\Http\Controllers\Api\UserController::class, 'user_login']);
Route::post('/update-profile-data/{id}', [App\Http\Controllers\Api\UserController::class, 'update_profile_data']);
Route::get('/user-own-reviews', [App\Http\Controllers\Api\UserController::class, 'user_own_reviews']);

});