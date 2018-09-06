<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/8
 * Time: 15:29
 */

namespace app\admin\validate;


use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        'uname'=>'require',
        'password'=>'require',
        'role_id'=>'require'
    ];
    protected $message = [
        'uname'=>[
            'require'=>'用户名不能为空'
        ],
        'password'=>[
            'require'=>'密码不能为空'
        ],
        'role_id'=>[
            'require'=>'角色不能为空'
        ]
    ];

}