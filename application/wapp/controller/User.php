<?php
/**
 * 普通团员
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-03
 * Time: 15:26
 */

namespace app\wapp\controller;


use app\common\model\ApplyLeaderRecord;
use think\Controller;

class User extends Controller
{
    private $user;
    protected function _initialize()
    {
        parent::_initialize();
        $user_id = input('user_id');
        if(!$user_id){
            exit_json(-1,'用户不存在，请重新登陆');
        }else{
            $this->user = \app\common\model\User::get($user_id);
            if(!$this->user){
                exit_json(-1,'用户不存在，请重新登陆');
            }
        }

    }

    /**
     * 申请成为团长
     */
    public function applyLeader()
    {
        //城主id
        $data = [
            'header_id' => input('header_id'),
            'user_id' => input('user_id'),
            'name' => input('name'),
            'telephone' => input('telephone'),
            'address' => input('address'),
            'residential' => input('residential'),
            'neighbours' => input('neighbours'),
            'have_group' => input('have_group'),
            'have_sale' => input('have_sale'),
            'work_time' => input('work_time'),
            'other' => input('other'),
        ];
        $header = model('Header')->where('id', $data['header_id'])->find();
        if(!$header){
            exit_json(-1, '参数错误');
        }
        if($this->user['role_status'] == 2){
            exit_json(-1,'你已经是团长无需重复申请');
        }
        $apply_leader = ApplyLeaderRecord::get(['user_id'=>$data['user_id'], 'status'=>0]);
        if($apply_leader){
            exit_json(-1,'已提交申请，等待城主申请');
        }

        $res = ApplyLeaderRecord::create($data);
        if($res){
            exit_json(1, '申请成功');
        }else{
            exit_json(-1,'申请失败');
        }
    }

    /**
     * 获取城主地址
     */
    public function getHeaderAddress()
    {
        $header_id = input('header_id');
        $header = \app\common\model\Header::get($header_id);
        $data = [
            'header_id'=>$header['id'],
            'nick_name'=>$header['nick_name'],
            'address'=>$header['address']
        ];
        exit_json(1, '请求成功', $data);
    }


}