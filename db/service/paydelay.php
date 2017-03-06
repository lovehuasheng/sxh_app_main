<?php
    include  "../db/model/user.php";
    include  "../db/model/matchhelp.php";
    /*
     * 延时打款
     */
    /*提交的基本信息验证*/
    
    if(!is_numeric($arr['user_id']) || $arr['user_id'] < 1){
        return returnErr(1,'请先登录');
    }
    if(!is_numeric($arr['match_id']) || $arr['match_id']<1 ){
        return returnErr(1,'请选择正确的记录');
    }
    if(empty($arr['sed_pwd'])){
        return returnErr(1,'二级密码不能为空');
    }
    $rule = '/^\d{10}$/';
    if(!preg_match($rule, $arr['create_time']) || $arr['create_time']>time() || $arr['create_time'] < strtotime("2016-01-01") ){
        return returnErr(1,'请选择正确的时间');
    }
    if(!is_numeric($arr['delay_time']) || $arr['delay_time']<1 || $arr['delay_time']>24){
        return  returnErr(1,'请选择正确的延时时间');
    }
    $paydelay = function($data,$matchhelp_out_time){
        $model = new user();
        $user_data = $model->getUser($data['user_id'], 'secondary_password,security');
        if(count($user_data) == 0){
            return  returnErr(1,'用户信息有误！');
        }
        $user_data = current($user_data);
        if($user_data['security']){
            $pwd = set_secondary_password(trim($data['sed_pwd']));
        }else{
            $pwd = set_old_password(trim($data['sed_pwd']));
        }
        if($pwd != $user_data['secondary_password']) {
            return returnErr(1,'二级密码不正确！');
        }
        $match = new matchhelp();
        $match_data = $match->getMatchHelpInfo($data['match_id'],$data['create_time'],'status,other_user_id,id,sign_time,pay_time,audit_time,delayed_time_status,expiration_create_time');
        if(count($match_data) == 0){
            return  returnErr(1,'请选择正确的匹配打款订单！');
        }
        $match_data = current($match_data);
        if($match_data['other_user_id'] != $data['user_id']){
            return  returnErr(1,'不是你的匹配打款订单！');
        }
        if($match_data['status'] != 1){
            return  returnErr(1,'匹配订单不是待打款状态，不可延时');
        }
        if($match_data['delayed_time_status'] == 1){
            return  returnErr(1,'已经延时过了');
        }
        if($match_data['audit_time'] + $matchhelp_out_time > time()){/*24之内提出延时*/
            $time = $match_data['audit_time'] + $matchhelp_out_time + $data['delay_time'] * 3600;
        }else{
            $time = time() + $data['delay_time'] * 3600;
        }
        $res = $match->set_delayed_time($data['match_id'],$data['create_time'],$time);
        if($res){
            return  returnErr(0,'延时成功');
        }
        return  returnErr(0,'延时失败');
    };
    return $paydelay($arr,$matchhelp_out_time);