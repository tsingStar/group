<?php
/**
 * 微信小程序公共模块
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-27
 * Time: 16:32
 */

namespace app\wapp\controller;


use app\common\model\ApplyHeaderRecord;
use think\Controller;
use think\Log;

class Pub extends Controller
{

    public function test()
    {
        $group_id = 57;
        $group_list = model("GroupProduct")->where("header_group_id", $group_id)->group("group_id")->field("sum(sell_num*group_price*commission/100) sum_money, leader_id")->select();
        print_r($group_list);

    }

    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 微信小程序授权
     */
    public function getOpenId()
    {
        $app_id = config('wapp.app_id');
        $app_secret = config('wapp.app_secret');
        $js_code = input('js_code');
        if (!$app_id || !$app_secret || !$js_code) {
            exit_json(-1, '参数错误');
        }
        $res = file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid=$app_id&secret=$app_secret&js_code=$js_code&grant_type=authorization_code");
        $res = json_decode($res, true);
        if (!isset($res['openid'])) {
            exit_json(-1, $res['errmsg']);
        }
        $session_key = $res['session_key'];
        $open_id = $res['openid'];

        $os = db('os')->where('open_id', $open_id)->find();
        if ($os) {
            db('os')->where('open_id', $open_id)->update(['session_key' => $session_key]);
        } else {
            db('os')->insert(['open_id' => $open_id, 'session_key' => $session_key]);
        }
        exit_json(1, '请求成功', [
            'open_id' => $open_id,
        ]);
    }

    /**
     * 小程序登陆
     */
    public function loginByWapp()
    {
        $open_id = input('open_id');
        $user_name = input('user_name');
        $avatar = input('avatar');
        $gender = input('gender');
        $city = input('city');
        $province = input('province');
        $country = input('country');
        $data = [
            'open_id' => $open_id,
            'user_name' => $user_name,
            'avatar' => $avatar,
            'gender' => $gender,
            'city' => $city,
            'province' => $province,
            'country' => $country
        ];
        if (!$open_id) {
            exit_json(-1, '登陆失败');
        }
        $user = model('User')->where('open_id', $open_id)->find();
        if ($user) {
            $data['update_time'] = time();
            $res = $user->save($data);
            $user_id = $user['id'];
        } else {
            $res = model('User')->save($data);
            $user_id = model('User')->getLastInsID();
        }
        if ($res) {
            exit_json(1, '登陆成功', model('User')->find($user_id));
        } else {
            exit_json(-1, '登陆失败asdfghj');
        }
    }

    /**
     * 申请城主
     */
    public function applyHeader()
    {
        $data = [
            'user_id' => input('user_id'),
            'name' => input('name'),
            'telephone' => input('telephone'),
            'address' => input('address'),
            'profession' => input('profession'),
            'is_leader' => input('is_leader'),
            'have_group' => input('have_group'),
            'other' => input('other'),
        ];
        $header = ApplyHeaderRecord::get(['user_id' => $data['user_id'], 'status' => 0]);
        if ($header) {
            exit_json(-1, '你有新的申请正在等待处理');
        } else {
            $res = ApplyHeaderRecord::create($data);
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '申请失败');
            }
        }
    }

    /**
     * 城主登陆
     */
    public function headerLogin()
    {
        $name = input('name');
        $password = input('password');
        $header = model('Header')->where(['name' => $name, 'password' => md5($password)])->field('id header_id, name, nick_name, head_image, amount_able+amount_lock amount, amount_able')->find();
        if ($header) {
            exit_json(1, '登陆成功', $header);
        } else {
            exit_json(-1, '账号或密码错误');
        }
    }


    /**
     * 上传图片
     */
    public function uploadImg()
    {
        $file = request()->file('file');
        if ($file) {
            $ext_array = ['jpg', 'jpeg', 'png', 'mp4', '3gp', 'avi'];
            $ext = $file->checkExt($ext_array);
            if (!in_array($ext, $ext_array)) {
                exit_json(-1, '请上传有效格式的文件');
            }
            $info = $file->move(__UPLOAD__);
            if ($info) {
                $saveName = $info->getSaveName();
                $path = __URL__ . "/upload/" . $saveName;
                exit_json(1, '操作成功', ["img_url" => $path]);
            } else {
                // 上传失败获取错误信息
                exit_json(-1, $file->getError());
            }
        } else {
            exit_json(-1, '文件不存在');
        }
    }

    /**
     * 删除文件
     */
    public function delImg()
    {
        $path = input('img_url');
        $file_info = parse_url($path);
        $path = $file_info['path'];
        $real_path = __PUBLIC__ . $path;
        if (file_exists($real_path)) {
            @unlink($real_path);
            exit_json(1, '操作成功', ['msg' => '已删除']);
        } else {
            exit_json(-1, '文件不存在');
        }

    }


}