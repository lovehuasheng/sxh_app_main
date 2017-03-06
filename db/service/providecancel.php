<?php
    include  "../db/model/user.php";
    include  "../db/model/provide.php";
    /*
     * 取消订单
     */
    /*提交的基本信息验证*/
    
    if(!is_numeric($arr['user_id']) || $arr['user_id'] < 1){
        return returnErr(1,'请先登录');
    }
    if(!is_numeric($arr['id']) || $arr['id']<1 ){
        return returnErr(1,'请选择正确的记录');
    }
    if(empty($arr['sed_pwd'])){
        return returnErr(1,'二级密码不能为空');
    }

    /*服务层不做手机验证码验证，只处理公共业务*/
    $provide_cancle = function ($data){
        
        $model = new user();
        $field = 'username,password,security,status,verify,secondary_password';
        $user  = $model->getUser($data['user_id'],$field);
        if(count($user) == 0){
            return returnErr(1,'用户不存在');
        }
        $user = current($user);
        if($user['status'] != 1){
            return returnErr(1,'用户没有激活');
        }
        if($user['verify'] != 2){
            return returnErr(1,'用户没有通过审核');
        }
        if($user['security']){  /*新用户加密方式*/
            $pwd     = set_password(trim($data['sed_pwd']),$user['security']);/*登录密码*/
            $pwd_sed = set_secondary_password(trim($data['sed_pwd'])); /*二级密码*/
        }else{                  /*老用户加密方式*/
            $pwd     = set_old_password(trim($data['sed_pwd']));/*登录密码*/
            $pwd_sed = set_old_password(trim($data['sed_pwd']));/*二级密码*/
        }
        if($user['secondary_password'] != $pwd_sed){
            return returnErr(1,'二级密码不正确');
        }
        if($pwd == $pwd_sed){
            return returnErr(1,'二级密码不能与支付密码一致');
        }
        $pro = new provideModel();
        $result = $pro->cancelProvide($data);
        
        /*更新redis*/
        $num = $redis->hget('sxh_userinfo:id:'.intval($data['user_id']),"provide_create_num");/*提出挂单的次数*/
        $redis->hset('sxh_userinfo:id:'.intval($data['user_id']),"provide_create_num",$num-1);/*成功挂单，挂单的次数-1*/
        
        return $result;
    };
    
    /*执行调用*/
    return $provide_cancle($arr);