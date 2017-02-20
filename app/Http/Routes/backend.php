<?php


/* 后台首页 */
Route::get('index/', [
    'as' => 'backend.index.index',
    'uses' => 'IndexController@index',
]);
/* 后台首页 */
Route::get('welcome/', [
    'as' => 'backend.index.welcome',
    'uses' => 'IndexController@welcome',
]);
/* 菜单管理模块 */
Route::get('menu/search', [
    'as' => 'backend.menu.search',
    'uses' => 'MenuController@search',
    'middleware' => ['search'],
]);
Route::resource('menu', 'MenuController');

/* 用户管理模块 */
Route::resource("user", 'UserController');
Route::get('userCenter/show', [
	'as' => 'backend.userCenter.show',
	'uses' => 'UserController@myInfo'
]);
Route::get('userCenter/editPwd', [
	'as' => 'backend.userCenter.editPwd',
	'uses' => 'UserController@editPwd'
]);
Route::put('userCenter/editPwd', [
	'as' => 'backend.userCenter.editPwdHandler',
	'uses' => 'UserController@editPwdHandler'
]);
Route::get('userCenter/bindOpenID', [
	'as' => 'backend.userCenter.bindOpenID',
	'uses' => 'UserController@bindOpenID'
]);
Route::put('userCenter/removeBind', [
	'as' => 'backend.userCenter.removeBind',
	'uses' => 'UserController@removeBind'
]);



/* 角色管理模块 */
Route::get('role/permission/{id}', [
    'as' => 'backend.role.permission',
    'uses' => 'RoleController@permission',
]);
Route::post('role/associatePermission', [
    'as' => 'backend.role.associate.permission',
    'uses' => 'RoleController@associatePermission',
]);
Route::resource("role", 'RoleController');

/* 权限管理模块 */
Route::get('permission/associate/{id}', [
    'as' => 'backend.permission.associate',
    'uses' => 'PermissionController@associate',
]);
Route::post('permission/associateMenus', [
    'as' => 'backend.permission.associate.menus',
    'uses' => 'PermissionController@associateMenus',
]);
Route::post('permission/associateActions', [
    'as' => 'backend.permission.associate.actions',
    'uses' => 'PermissionController@associateActions',
]);
Route::resource("permission", 'PermissionController');

/* 操作管理模块 */
Route::resource('action', 'ActionController');

/* 文件管理模块 */
Route::get('file', [
    'as' => 'backend.file.index',
    'uses' => 'FileController@index',
]);
Route::post('file/upload', [
    'as' => 'backend.file.upload',
    'uses' => 'FileController@upload',
]);


// 用户操作日志
Route::get("actionLogger/index", [
    'as' => 'backend.actionLogger.index',
    'uses' => "ActionLoggerController@index"
]);
Route::get("actionLogger/show", [
    'as' => 'backend.actionLogger.show',
    'uses'=> 'ActionLoggerController@show'
]);

//消息管理
Route::get('message/index', [
    'as' => 'backend.message.index',
    'uses' => 'MessageController@index'
]);
Route::get('message/read', [
    'as' => 'backend.message.read',
    'uses' => 'MessageController@read'
]);
Route::get('message/ask', [
    'as' => 'backend.message.ask',
    'uses' => 'MessageController@ask'
]);
Route::get('message/readAll', [
    'as' => 'backend.message.readAll',
    'uses' => 'MessageController@readAll'
]);

