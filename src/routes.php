<?php

use Illuminate\Support\Facades\File;

Route::group(array('before' => 'vextAuth'), function() {

    /*Route::any('/{all}', function($page) {
        if ( File::extension($page) === 'js' ) {
            header("Content-type: text/javascript");
        } else if ( File::extension($page) === 'css' ) {
            header("Content-type: text/css");
        }
        readfile("../app/views/extjs/$page");
        flush();
    })->where('all', '.*');*/

    //Route::get('login', 'Qlcorp\VextSecurity\UserController@showLogin');
    Route::controller('user', 'Qlcorp\VextSecurity\UserController');
    Route::controller('brand', 'Qlcorp\VextSecurity\BrandController');
    Route::controller('role', 'Qlcorp\VextSecurity\RoleController');
    Route::controller('departmentRole', 'Qlcorp\VextSecurity\DepartmentRoleController');
});

Route::group(array('before' => 'vextGuest'), function() {
    Route::controller('login', 'Qlcorp\VextSecurity\AuthController');
});

