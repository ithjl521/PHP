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
    return view('login');
});

//登录页面
Route::get('/login','View\MemberController@toLogin');
//注册页面
Route::get('/register','View\MemberController@toRegister');
//category页面
Route::get('/category','View\BookController@toCategory');
//products页面
Route::get('/product/category_id/{category_id}','View\BookController@toProduct');
//product页面
Route::get('/product/{product_id}','View\BookController@toPdtContent');



//service路由组
Route::group(['prefix'=>'service'],function (){
    //获取验证码接口
    Route::any('validate_code/create','Service\ValidateCodeController@create');
    //短信接口
    Route::any('validate_phone/send','Service\ValidateCodeController@sendSMS');
    //注册接口
    Route::any('register','Service\MemberController@register');
    //邮箱激活接口
    Route::any('validate_email','Service\ValidateCodeController@validateEmail');
    //登录接口
    Route::any('login','Service\MemberController@login');
    //获取分类ID
    Route::any('category/parent_id/{parent_id}','Service\BookController@getCategoryIdByParentId');
});

