<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-29
 * Time: 16:48
 */

ini_set('default_socket_timeout', -1);  //不超时
$redis = new \Redis();
$redis->connect("127.0.0.1", "6379");
// 解决Redis客户端订阅时候超时情况
$redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
$redis->psubscribe(array('__keyevent@0__:expired'), 'keyCallback');
exit();
// 回调函数,这里写处理逻辑
function keyCallback($redis, $pattern, $chan, $msg)
{
    echo $pattern;
    echo $chan;
    echo $msg;
//    \think\Log::error($pattern."@".$chan."@".$msg);
    //修改订单状态
//    $order=model('order')->where(['order_no'=>$msg])->find();
//    $order->status=-1;
//    $order->save();
//    //库存还原
//    $products=json_decode($order->snap_items,true);
//    foreach($products as $v){
//        model('product')->where(['id'=>$v['id']])->setInc('stock',$v['count']);
//    }
}
