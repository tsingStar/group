<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/19
 * Time: 下午3:33
 */

echo spinWords("Hey fellow warriors");
function spinWords($string){
    $arr_str = explode(" ", $string);
    $dst_str = [];
    foreach($arr_str as $val){
        if(mb_strlen($val)>=5){
            $val = strrev($val);
        }
        $dst_str[] = $val;
    }
    return implode(" ", $dst_str);
    //TODO Have fun :)
}