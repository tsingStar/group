<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-29
 * Time: 17:28
 */
class Redis2
{
    private $redis;

    public function __construct($host = '127.0.0.1', $port = 6379)
    {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
//        $this->redis->auth("ybt666666");
    }

    /**
     * 字符串设置
     * @param $key
     * @param $val
     * @return bool
     */
    public function set($key, $val)
    {
        return $this->redis->set($key, $val);
    }

    /**
     * 获取字符串key对应的值
     * @param $key
     * @return bool|string
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * 指定键值增加rank数量
     * @param $key
     * @param int $rank
     * @return bool|float|int
     */
    public function incr($key, $rank=1)
    {
        if($rank === 1){
            return $this->redis->incr($key);
        }elseif(is_int($rank) && $rank>1){
            return $this->redis->incrBy($key, $rank);
        }elseif(is_float($rank)){
            return $this->redis->incrByFloat($key, $rank);
        }else{
            return false;
        }
    }

    /**
     * 批量设置key-value字符串
     * @param $arr
     * @return bool
     */
    public function mset($arr)
    {
        return $this->redis->mset($arr);
    }

    /**
     * 返回多个指定key对应的value
     * @param $arr
     * @return array
     */
    public function mget($arr)
    {
        return $this->redis->mget($arr);
    }

    /**
     * 返回指定key对应的字符串的长度
     * @param $key
     * @return int
     */
    public function strlen($key)
    {
        return $this->redis->strlen($key);
    }

    /**
     * 将value 插入到列表 key 的表头
     * @param $key
     * @param $value1
     * @return bool|int
     */
    public function lpush($key, $value1)
    {
        return $this->redis->lPush($key, $value1);
    }

    /**
     * 移除并返回列表 key 的头元素。
     * @param $key
     * @return string
     */
    public function lpop($key)
    {
        return $this->redis->lPop($key);
    }

    /**
     * 返回列表长度
     * @param $key
     * @return int
     */
    public function llen($key)
    {
        return $this->redis->lLen($key);
    }

    /**
     * 将哈希表 key 中的域 field 的值设为 value 。
     * @param $key
     * @param $field
     * @param $value
     * @return bool|int
     */
    public function hset($key, $field, $value)
    {
        return $this->redis->hset($key, $field, $value);
    }

    /**
     * 返回哈希表 key 中给定域 field 的值
     * @param $key
     * @param $field
     * @return string
     */
    public function hget($key, $field)
    {
        return $this->redis->hGet($key, $field);
    }

    /**
     * 向集合中添加value
     * @param $key
     * @param $value
     * @return int
     */
    public function sadd($key, $value)
    {
        return $this->redis->sAdd($key, $value);
    }

    /**
     * 移除集合 key 中的一个或多个 member 元素，不存在的 member 元素会被忽略。
     * @param $key
     * @param $value
     * @return int
     */
    public function srem($key, $value)
    {
        return $this->redis->sRem($key, $value);
    }

    /**
     * 删除指定key
     * @param $key
     * @return int
     */
    public function delKey($key)
    {
        return $this->redis->del($key);
    }

    /**
     * 开启事务代码块
     * @return Redis
     */
    public function muti()
    {
        return $this->redis->multi();
    }
}
