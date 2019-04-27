<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Create Account
Route::post('/signup', 'UserController@signup');

// User Login
Route::post('/login', 'UserController@login');

// Get User Info
Route::get('/getUser', 'UserController@getUser');

// Update User Info
Route::put('/update', 'UserController@updateUser');

// Add User Income
Route::post('/addIncome', 'IncomeExpenseController@addIncome');

// Add User Expense
Route::post('/addExpense', 'IncomeExpenseController@addExpense');

// Get Expense Report
Route::get('/report', 'IncomeExpenseController@getReport');

// Add Categories
Route::post('/addCategories', 'CategoriesController@addCategories');