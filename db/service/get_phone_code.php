<?php
$get_phone_code = function($data){
   
    $redis = RedisLib::get_instance();
    $user_id = '';
    if($data['type'] == 1){
        //查询手机号是否已被注册（手机，微信号，支付宝号）
        $phone_result           = $redis->sismemberFieldValue('sxh_user_info:phone' , $data['phone']);
        $alipay_account_result  = $redis->sismemberFieldValue('sxh_user_info:alipay_account' , $data['phone']);
        $weixin_account_result  = $redis->sismemberFieldValue('sxh_user_info:weixin_account' , $data['phone']);
        if($phone_result || $alipay_account_result || $weixin_account_result){
            return returnErr(1,'手机号已经被注册');
        }
    }else if($data['type'] == 2 || $data['type'] == 5){
        $data['username'] = strtolower($data['username']);
        $user_id = $redis->getUserId($data['username']);
        if(!$user_id){
            return returnErr(1,'用户名不存在');
        }
        $data['phone'] = $redis->hgetUserinfoByID($user_id,'phone');
    }else if($data['type'] == 3 || $data['type'] == 4){
        $user_id = config('user_id');
        if(!$user_id){
            return returnErr(1,'用户名不存在');
        }
        $data['phone'] = $redis->hgetUserinfoByID($user_id,'phone');
    }
    if($data['type']==1){
        if(cache('code_expire_time'.$data['phone'])){
            return returnErr(1,'您的验证码刚刚发送，请稍后再试!');
        }
    }else{
        if(cache('code_expire_time'.$user_id.$data['phone'])){
            return returnErr(1,'您的验证码刚刚发送，请稍后再试!');
        }
    }
        
    $title = array(
        1=>'注册验证码',
        2=>'找回密码验证码',
        3=>'查看收款人验证码',
        4=>'取消挂单验证码',
        5=>'异常登录验证码',
    );
    $code = get_rand_num(5);
    $sdata = array();
    $sdata['extra_data ']['user_id'] = $user_id>0 ? $user_id : 0;
    $sdata['extra_data ']['phone'] = $data['phone'];
    $sdata['extra_data ']['title'] = $title[$data['type']];
    $sdata['extra_data ']['code'] = $code;
    $sdata['extra_data ']['status'] = 1;
    $sdata['extra_data ']['ip_address'] = $data['ip'];
    $sdata['extra_data ']['valid_time'] = 300;
    $sdata['extra_data ']['create_time'] = time();
    $sdata['extra_data ']['update_time'] = time();
    if($data['type']==1){
        $sdata['content'] = "您的验证码是".$code."，正在进行会员注册验证。";
    }else if($data['type']==2){
        $sdata['content'] = "您的验证码是".$code."，您正在尝试修改登录密码，请妥善保管账户信息";
    }else if($data['type']==3){
        $sdata['content'] = "您好，你正在查看收款人信息,为了账户信息安全,切勿泄露。您的验证码是".$code;
    }else if($data['type']==4){
        $sdata['content'] = "您好，你的账号正在撤销订单操作，请确认。如有疑问，请登录账号查看或联系客服。您的验证码是".$code;
    }else if($data['type']==5){
        $sdata['content'] = "您的验证码是".$code."，正在进行异常登录验证。";
    }
    $sdata['phone'] = $data['phone'];
    $redis->lPush('sxh_user_sms', json_encode($sdata,JSON_UNESCAPED_UNICODE));//LPUSH\
    if($user_id>0){
        cache('code'.$user_id.$data['phone'],$code,600);
        cache('code_expire_time'.$user_id.$data['phone'],120,120);
    }else{
        cache('code'.$data['phone'],$code,600);
        cache('code_expire_time'.$data['phone'],120,120);
    }
    return returnErr(0,'验证码发送成功');
};
return $get_phone_code($arr);

