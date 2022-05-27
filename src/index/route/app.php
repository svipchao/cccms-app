<?php
declare (strict_types=1);

use think\facade\Route;

// 附件路由
Route::rule('/file/<code>', 'index/file/file')->name('file');
