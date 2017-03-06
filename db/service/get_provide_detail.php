<?php
require_once WANG_MS_PATH."model/publicCenter.php";
$get_provide_detail = function($data){
    //$redis = RedisLib::get_instance();
    $model = new publicCenter();
    $arr = array();
    $arr['other_id'] = intval($data['id']);
    $arr['create_time'] = $data['create_time'];
    $result_list = $model->getMatchingListByProvideID($arr,'id,pid,other_type_id,user_id,money,other_id,other_user_id,other_money,status,sign_time,create_time,name,pay_time,delayed_time_status,expiration_create_time,audit_time');
    if(empty($result_list)){
        return returnErr(1,'数据未匹配');
    }
    //整理数据返回
    $arr = array('提供资助','提供资助','接单资助');
    $status_text = array('未审核','未布施','已布施','已布施');
    $sign_text = array('未审核','未确认','未确认','已确认');
    $list = array();
    foreach($result_list as $k=>$v){
        if($v['other_user_id'] != $data['user_id']){
            return returnErr(1,'数据错误');
            break;
        }
        $list[$k]['id'] = $v['id'];
        $list[$k]['pid'] = $v['pid'];
        $list[$k]['other_id'] = $v['other_id'];
        $list[$k]['matching_text'] = $arr[$v['other_type_id']];
        $list[$k]['other_money'] = $v['other_money'];
        $list[$k]['status'] = $v['status'];
        $list[$k]['status_text'] = $status_text[$v['status']];
        $list[$k]['sign_text'] = $sign_text[$v['status']];
        $list[$k]['username'] = $v['name'];
        $list[$k]['create_time'] = $v['create_time'];
        $list[$k]['pay_time'] = $v['pay_time'];
        $list[$k]['provide_overtime_status'] = 0;
        $list[$k]['accept_overtime_status'] = 0;
        //打款方未付款倒计时计算
        if($v['status'] == 1){
            if($v['delayed_time_status']){
                if(($v['expiration_create_time']-$_SERVER['REQUEST_TIME']) > 0){
                    $list[$k]['create_time_text'] = $v['expiration_create_time']-$_SERVER['REQUEST_TIME'];
                }else{
                    $list[$k]['create_time_text'] = 0;
                    $list[$k]['provide_overtime_status'] = 1;
                }
            }else{
                if(($_SERVER['REQUEST_TIME']-$v['audit_time']) < config('matchhelp_out_time')){
                    $list[$k]['create_time_text'] = $v['audit_time']+config('matchhelp_out_time')-$_SERVER['REQUEST_TIME'];
                }else{
                    $list[$k]['create_time_text'] = 0;
                    $list[$k]['provide_overtime_status'] = 1;
                }
            }
        }else{
            $list[$k]['create_time_text'] = 0;
        }
        if($v['status'] == 2){
            if(($_SERVER['REQUEST_TIME']-$v['pay_time']) < config('matchhelp_out_time')){
                $list[$k]['pay_time_text'] = $v['pay_time']+config('matchhelp_out_time')-$_SERVER['REQUEST_TIME'];
            }else{
                $list[$k]['pay_time_text'] = 0;
                $list[$k]['accept_overtime_status'] = 1;
            }
        }else{
            $list[$k]['pay_time_text'] = 0;
        }
    }

    return returnErr(0,'请求成功',$list);
};
return $get_provide_detail($arr);

