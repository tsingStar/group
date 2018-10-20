<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
define('__STATIC__', __DIR__.'/static');
define('__UPLOAD__', __DIR__.'/upload');
define('__PUBLIC__', __DIR__);
define('__URI__', $_SERVER['HTTP_HOST']);
$http = $_SERVER['SERVER_PORT'] == '443'?'https://':'http://';
define('__URL__', $http.__URI__);


define('SITENAME', "易贝通");
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
