<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReviewController;

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

Route::post('/forget-password',[App\Http\Controllers\Api\UserController::class,'forgetpassword']);
Route::get('/reset-password',[UserController::class,'resetPasswordLoad']);
Route::post('/reset-password',[UserController::class,'resetPassword']);



Route::group(['middleware' => ['api']], function ($router) { 

Route::post('/savereview', [App\Http\Controllers\Api\ReviewController::class, 'save_review']);
// Route::get('/getallreview', [App\Http\Controllers\Api\ReviewController::class, 'get_all_reviews']);
Route::get('/get-by-firm', [App\Http\Controllers\Api\ReviewController::class, 'get_only_firm']);


Route::post('/delete-reviews', [App\Http\Controllers\Api\ReviewController::class, 'delete_reviews']);
Route::get('/get-review/{id}', [App\Http\Controllers\Api\ReviewController::class, 'get_review_by_id']);
Route::get('/get-review-to/{id}', [App\Http\Controllers\Api\ReviewController::class, 'get_all_review_to']);
Route::get('/get-review-by/{id}', [App\Http\Controllers\Api\ReviewController::class, 'get_all_review_by']);
Route::get('/most-recent-review', [App\Http\Controllers\Api\ReviewController::class, 'most_recent_reviews']);
Route::get('/search-all-reviews', [App\Http\Controllers\Api\ReviewController::class, 'search_all_reviews']);
Route::get('/most-liked-review', [App\Http\Controllers\Api\ReviewController::class, 'most_liked_reviews']);
Route::get('/getlikedislikes/{user_id}', [App\Http\Controllers\Api\ReviewController::class, 'getlikedislikes']);
});

    // Route::group(['middleware' => ['api','jwt.auth']], function ($router) { 
    Route::get('/get-all-users', [App\Http\Controllers\Api\UserController::class, 'get_all_users']);
    Route::get('/getallreview', [App\Http\Controllers\Api\ReviewController::class, 'get_all_reviews']);
    Route::get('/get-user-profile-data-id/{id}',[UserController::class,'get_user_profile_data_id']);
    // });

Route::group(['middleware' => ['api']], function ($router) { 
Route::post('/register', [App\Http\Controllers\Api\UserController::class, 'user_register']);
Route::post('/login', [App\Http\Controllers\Api\UserController::class, 'user_login']);
Route::post('/update-profile-data', [App\Http\Controllers\Api\UserController::class, 'update_profile_data']);
Route::get('/user-own-reviews', [App\Http\Controllers\Api\UserController::class, 'user_own_reviews']);
// Route::get('/get-user-profile-data-id/{id}',[UserController::class,'get_user_profile_data_id']);
Route::get('/get-user-profile-data/{slug}', [UserController::class, 'get_user_profile_data']);

Route::get('/search',[UserController::class,'search']);

//Route::get('/search/{slug}', [SearchController::class, 'search']);


Route::get('/user_review/{id}',[UserController::class,'user_review']);
Route::get('/review_by_me/{id}',[UserController::class,'review_by_me']);
Route::get('/my_reviews/{id}',[ReviewController::class,'my_reviews']);
Route::post('/like-review',[App\Http\Controllers\Api\ReviewController::class,'like']);
Route::post('/new_user_review',[ReviewController::class,'new_user_review']);

Route::post('/edit-profile', [App\Http\Controllers\Api\UserController::class, 'edit_profile_data']);
Route::get('/get-edit-profile-data/{id}',[UserController::class,'get_edit_profile_data']);
Route::get('/get-bunjee-score',[UserController::class,'get_bunjee_score']);

Route::post('/users', [UserController::class, 'deleteUser']);

// Route::delete('/users/{id}', [UserController::class, 'deleteUser']);

Route::post('/users/merge', [UserController::class, 'user_merge']);

Route::get('/users/same-name', [UserController::class, 'getSameNameUsers']);




});