<?php
require_once WANG_MS_PATH."model/userinfo.php";
$out_manage_account = function($data){
    $redis = RedisLib::get_instance();
    //根据post数据，获取用户信息
    $model = new userinfo();
    $reuser = $model->getUserOneInfo($data['user_id'],'id,username,status,flag,verify,security,secondary_password,password','sxh_user');
    if(!$reuser){
        return returnErr(1,'用户ID有误');
    }

    //二级密码判断
    if($reuser[0]['security']){
        $pwd = set_secondary_password(htmlspecialchars(urldecode($data['password'])));
    }else{
        $pwd = set_old_password(htmlspecialchars(urldecode($data['password'])));
    }
    if($reuser[0]['secondary_password'] != $pwd) {
        return returnErr(1,'二级密码错误，请重新输入');
    }
    //二级密码与登录密码对比
    if($reuser[0]['security']){
        $pwd = set_password(htmlspecialchars(urldecode($data['password'])),$reuser[0]['security']);
    }else{
        $pwd = set_old_password(htmlspecialchars(urldecode($data['password'])));
    }
    //判断二级密码与登录密码是否相等，如果相等则要修改，暂时到pc端修改
    if($reuser[0]['password'] == $pwd) {
        return returnErr(1,'二级密码不能与登录密码一致，请到电脑端设置');
    }
    //是否激活判断
    if($reuser[0]['status']!=1 || $reuser[0]['verify']!=2){
        return returnErr(1,'您的账号未激活或未通过或被禁止使用');
    }
    $res = $model->getUserOneInfo($data['user_id'],"manage_wallet",'sxh_user_account');
    if($res[0]['manage_wallet']<500){
        return returnErr(1,'管理钱包要大于500方可提取');
    }
    if($res[0]['manage_wallet']<$data['money_sum']){
        return returnErr(1,'提取管理奖不能大于管理钱包的金额');
    }
    //获取缓存对比
    $num = $redis->hgetUserinfoByID($data['user_id'],'provide_num');
    if($num<2){
        return returnErr(1,'提取管理钱包必须要提供资助两次或以上');
    }
    $current_id = $redis->hgetUserinfoByID($data['user_id'],'provide_current_id');
    $last_id = $redis->hgetUserinfoByID($data['user_id'],'provide_manage_id');
//        if($current_id != $last_id){
    $res_arr = $model->checkManage(intval($data['user_id']));
    if($res_arr['code'] == 0){
        $sum = $redis->hgetUserinfoByID($data['user_id'],'provide_current_money');
        $sum = $res_arr['provide_money'];//暂时不读redis
        $amount = intval(floor($sum/2/100)*100);
        if($amount<$data['money_sum']){
            return returnErr(1,'您提取的管理奖已超出可提金额');
        }
    }else{
        return returnErr(1,'本次挂单期内只能提取一次管理奖');
    }
    //设置最近订单为数据库查出的最近订单
    $current_id = $res_arr['provide_id'];
    //判断上次挂单的社区ID
    $arr = array('poor_wallet','needy_wallet','comfortably_wallet','kind_wallet','wealth_wallet','big_kind_wallet');
    $community_id = $redis->hgetUserinfoByID($data['user_id'],'provide_last_community_id');
    if(!$community_id){
        return returnErr(1,'上次挂单的社区ID不明确');
    }

    $check_token = $redis->incr('check_token'.$data['check_token']);
    if($check_token>1){
        return returnErr(1,'数据不能重复提交');
    }

    $model->startTrans();
    //扣除操作account表  Wallet_Currency,Manage_Wallet,
    $field = $arr[$community_id-1];
    $s_data = '`manage_wallet`=`manage_wallet`-'.$data['money_sum'].',`'.$field.'`=`'.$field.'`+'.$data['money_sum'];
//    $s_data['manage_wallet'] = array('exp','manage_wallet-'.$data['money_sum']);
//    $s_data[$field] = array('exp',$field.'+'.$data['money_sum']);
    $res_account = $model->updateUserAccount($data['user_id'],$s_data,'sxh_user_account');
    //插入outgo表
    $out = array();
    $out['id'] = $redis->incr('sxh_user_outgo:id');
    $out['type'] = 5;
    $out['outgo'] = $data['money_sum'];
    $out['user_id'] = $data['user_id'];
    $username = $redis->getUsernameByID($data['user_id']);
    $out['username'] = $username;
    //记录用户的最近一次提供资助ID
    $out['pid'] = $current_id;
    $out['info'] = '提取管理奖';
    $out['create_time'] = time();
    $catid = $model->insertDesc($out,'sxh_user_outgo');
    //插入income表
    $come = array();
    $come['id'] = $redis->incr('sxh_user_income:id');
    $come['type'] = 5;
    $come['income'] = $data['money_sum'];
    $come['user_id'] = $data['user_id'];
    $come['username'] = $username;
    $come['pid'] = $data['user_id'];
    $come['other_username'] = $username;
    $come['cat_id'] = $out['id'];
    $come['info'] = '【App】提取管理奖';
    $come['create_time'] = time();
    $res_income = $model->insertDesc($come,'sxh_user_income');
    $flag_del = $redis->del('check_token'.$data['check_token']);
    if(!$flag_del){
        $redis->set('check_token'.$data['check_token'],1,1);
    }
    if($res_account && $catid && $res_income){
        $model->commit();
    }else{
        $model->rollback();
        return returnErr(1,'系统繁忙，请重新操作！');
    }

    //更新缓存里面的可提取金额
    $redis->hsetUserinfoByID($data['user_id'],'provide_current_id',$current_id);
    $redis->hsetUserinfoByID($data['user_id'],'provide_manage_id',$current_id);
    $redis->hsetUserinfoByID($data['user_id'],get_redis_field($community_id),time());
    //获取手机号
    $user_phone = $redis->hgetUserinfoByID($data['user_id'],'phone');
    //推送信息
    $sdata = array();
    $sdata['extra_data ']['user_id'] = $data['user_id'];
    $sdata['extra_data ']['phone'] = $user_phone;
    $sdata['extra_data ']['title'] = '提取管理奖';
    $sdata['extra_data ']['code'] = '';
    $sdata['extra_data ']['status'] = 1;
    $sdata['extra_data ']['ip_address'] = $data['ip'];
    $sdata['extra_data ']['valid_time'] = '';
    $sdata['extra_data ']['create_time'] = time();
    $sdata['extra_data ']['update_time'] = time();
    $username = $reuser[0]['username'];
    $time = date('Y-m-d H:i:s');
    $number = $data['money_sum'];
    $sdata['content'] = "您好，您的".$username."账户于".$time."提取了".$number."元到出局钱包，如有疑问，请联系服务中心。";
    $sdata['phone'] = $uinfo['phone'];
    //return errReturn('提取成功！', 0);exit;
    $redis->lPush('sxh_user_sms', json_encode($sdata,JSON_UNESCAPED_UNICODE));
    return returnErr(0,'提取成功');
};
return $out_manage_account($arr);

