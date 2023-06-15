<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('index');
});

Route::group(['prefix' => 'api/brands', 'as' => 'api.brands.'], function(){
    Route::get("/", [\App\Http\Controllers\BrandsController::class, "getAllBrands"]);
});

Route::group(['prefix' => 'api/users', 'as' => 'api.users.'], function(){
    Route::get("/", [\App\Http\Controllers\UsersController::class, "login"]);
    Route::post("/", [\App\Http\Controllers\UsersController::class, "createUser"]);
    Route::get("/{id}", [\App\Http\Controllers\UsersController::class, "getUser"]);
    Route::post("/{id}", [\App\Http\Controllers\UsersController::class, "changeUserData"]);
    Route::get("/{id}/watched", [\App\Http\Controllers\UsersController::class, "getWatchedProducts"]);
    Route::post("/{id}/watched", [\App\Http\Controllers\UsersController::class, "setWatchedProduct"]);
    Route::delete("/{id}/watched", [\App\Http\Controllers\UsersController::class, "removeWatchedProduct"]);
});

Route::group(['prefix' => 'api/sections', 'as' => 'api.sections.'], function(){
    Route::get("/", [\App\Http\Controllers\SectionsController::class, "getAllSections"]);
    Route::post("/", [\App\Http\Controllers\SectionsController::class, "createSection"]);
    Route::delete("/", [\App\Http\Controllers\SectionsController::class, "deleteSection"]);
    Route::get("/{id}", [\App\Http\Controllers\SectionsController::class, "getSection"]);
    Route::get("/{section_id}/categories", [\App\Http\Controllers\SectionsController::class, "getSectionCategories"]);
});

Route::group(['prefix' => 'api/categories', 'as' => 'api.categories.'], function(){
    Route::post("/", [\App\Http\Controllers\CategoriesController::class, "createCategory"]);
    Route::delete("/", [\App\Http\Controllers\CategoriesController::class, "deleteCategory"]);
    Route::get("/{category_id}", [\App\Http\Controllers\CategoriesController::class, "getCategory"]);
    Route::get("/{category_id}/products", [\App\Http\Controllers\CategoriesController::class, "getCategoryProducts"]);
    Route::get("/{category_id}/filters", [\App\Http\Controllers\CategoriesController::class, "getCategoryFilters"]);
});

Route::group(['prefix' => 'api/products', 'as' => 'api.products.'], function(){
    Route::get("/", [\App\Http\Controllers\ProductsController::class, "getProducts"]);
    Route::get("/{code}", [\App\Http\Controllers\ProductsController::class, "getProduct"]);
    Route::delete("/{code}", [\App\Http\Controllers\ProductsController::class, "deleteProduct"]);
    Route::get("/{code}/rating", [\App\Http\Controllers\ProductsController::class, "getProductTotalRating"]);
    Route::post("/{code}/feedback", [\App\Http\Controllers\ProductsController::class, "sendFeedback"]);
    Route::delete("/{code}/feedback", [\App\Http\Controllers\ProductsController::class, "removeFeedback"]);
});

Route::group(['prefix' => 'api/orders', 'as' => 'api.orders.'], function(){
    Route::post("/{user_id}", [\App\Http\Controllers\OrdersController::class, "createOrder"]);
    Route::get("/{user_id}", [\App\Http\Controllers\OrdersController::class, "getUserOrders"]);
});

//Auth::routes();

//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
