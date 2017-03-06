<?php
require_once WANG_MS_PATH."model/userinfo.php";
//处理业务层
$mod_user_password = function($data){
    //实例化redis
    $redis = RedisLib::get_instance();
    $data['username'] = strtolower($data['username']);
    $user_id = $redis->getUserId($data['username']);
    if(!$user_id){
        return returnErr(1,'用户名不存在！');
    }

    $data['phone'] = $redis->hgetUserinfoByID($user_id,'phone');
    //验证码
    $code = cache('code'.$user_id.$data['phone']);
    if(empty($code) || $code != $data['verify']){
        //return returnErr(1,'验证码不正确或已过期！');
    }
    if($data['password'] == $data['username']){
        return returnErr(1,'密码不能与用户名一致！');
    }

    $m_user = new userinfo();
    $info = $m_user->getUserOneInfo($user_id,'password,security,secondary_password','sxh_user');
    if($info[0]['security']){
        $pwd = set_password(trim($data['password']),$info[0]['security']);
    }else{
        $pwd = set_old_password(trim($data['password']));
    }
    if($info[0]['password'] == $pwd){
        return returnErr(1,'新密码不能与原来密码一样！');
    }
    if($info[0]['security']){
        $spwd = set_secondary_password(trim($data['password']));
    }else{
        $spwd = set_old_password(trim($data['password']));
    }
    if($info[0]['secondary_password'] == $spwd){
        return returnErr(1,'新密码不能与二级密码一样！');
    }
    
    $sdata = array();
    //$sdata['secondary_password'] = set_password(md5($data['password']));
    if($info[0]['security']){
        $security = get_rand_num(6);
        $sdata['security'] = $security;
        $pwd = set_password(trim($data['password']),$security);
    }
    $sdata['password'] = $pwd;
    $res = $m_user->updateUserInfo($user_id,$sdata,'sxh_user');
    if($res){
        cache('code'.$user_id.$data['phone'],null);
        return returnErr(0,'修改成功！');
    }else{
        return returnErr(1,'修改失败！');
    }
};
return $mod_user_password($arr);

