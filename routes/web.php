<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/admin','admin\indexController@index');


Route::get('/student/index', 'StudentController@index');
Route::post('/student/do_add','StudentController@do_add');
Route::get('/student/update','StudentController@update');
Route::post('/student/do_update','StudentController@do_update');
Route::get('/student/delete','StudentController@delete');
Route::get('/admin/login','admin\indexController@login');
Route::get('/admin/do_login','admin\indexController@do_login');
// Route::get('/student/login','StudentController@login');
// Route::post('/student/do_login','StudentController@do_login');

Route::get('/admin/register','admin\indexController@register');
Route::get('/admin/do_register','admin\indexController@do_register');

Route::get('/admin/add_goods','admin\GoodsController@add_goods');
Route::post('/admin/do_add_goods','admin\GoodsController@do_add_goods');


//调用中间件
Route::group(['middleware' => ['login']], function () {
    //添加学生信息
Route::get('/student/add','StudentController@add');

});