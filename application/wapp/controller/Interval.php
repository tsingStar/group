<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-27
 * Time: 08:22
 */

namespace app\wapp\controller;


use app\common\model\Group;
use think\Cache;
use think\Controller;
use think\Exception;
use think\Log;

class Interval extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 主定时入口
     */
    public function index()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        echo "启动成功";
        while (true){
            file_get_contents("https://www.ybt9.com/wapp/Interval/closeGroup");
            ob_flush();
            flush();
            sleep(1);
        }
        exit();
    }



    /**
     * 定时结束军团
     */
    public function closeGroup()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        $list = model("HeaderGroup")->where("UNIX_TIMESTAMP(close_time)<" . time())->where("status", 1)->select();
        try {
            foreach ($list as $key=>$value) {
                $group_id = $value["id"];
                model("GroupPush")->save(["status"=>1], ["group_id"=>$group_id]);
                $res = $value->save(["status" => 2]);
                Cache::rm($value["id"].":HeaderGroup");
                if ($res) {
                    Group::update(["status" => 2, "close_time" => date("Y-m-d H:i")], ["header_group_id" => $group_id, "status" => ["neq", 2]]);
                    $id_arr = model("Group")->where(["header_group_id" => $group_id, "status" => ["neq", 2]])->column("id");
                    foreach ($id_arr as $id){
                        Cache::rm($id.":groupBaseInfo");
                    }
                } else {
                    throw new Exception("团购状态处理失败");
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        exit("ok");
    }
}