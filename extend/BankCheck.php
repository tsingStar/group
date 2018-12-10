<?php
/**
 * 银行卡类
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/13
 * Time: 上午10:07
 */

class BankCheck
{

    public static function check_bankCard($card_number){
        $arr_no = str_split($card_number);
        $last_n = $arr_no[count($arr_no)-1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n){
            if($i%2==0){
                $ix = $n*2;
                if($ix>=10){
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                }else{
                    $total += $ix;
                }
            }else{
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $x = 10 - ($total % 10);
        if($x == $last_n){
            return 'true';
        }else{
            return 'false';
        }
    }

}