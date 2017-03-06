<?php
    include  "../db/model/user.php";
    include  "../db/model/provide.php";
/**
 * 提供资助
 */
/*基础数据验证*/
  
    if(!is_numeric($arr['user_id']) || $arr['user_id'] < 0){
        return returnErr(1,'请先登录');
    }
    if(!is_numeric($arr['cid']) || !in_array($arr['cid'],[1,2,3,4,5])){
        return returnErr(1,'请选择正确的社区！');
    }
    if(!is_numeric($arr['money']) || $arr['money']<1000){
        return returnErr(1,'金额输入错误，只能为大于等于1000数字！');
    }
    if($arr['money']%100>0){
        return returnErr(1,'提供资助金额必须必须是规定金额的倍数！');
    }
/*挂单服务层*/
$redis = RedisLib::get_instance();  
$action = $redis->get('user_action_'.$arr['user_id']);
if($action){
    return returnErr(1,'请不要提交过勤');
}

$redis->set('user_action_'.$arr['user_id'],'provide');
$redis->expire('user_action_'.$arr['user_id'],10);
    
$provideDo = function($data){
    $model = new user();
    $info = $model->getUserDetail($data['user_id'],intval($data['cid']));
    $check_info = check_info($data,$info);
    if($check_info['errCode'] == 1){
        return $check_info;
    }
    $provide = $model->getLastProvide($data['user_id']);/*未完成的提供资助*/
    if($provide == 1){
        return returnErr(1,'您还有尚未完成的挂单!');
    }
    $accept = $model->getGccount($data['user_id']);/*善心币是否翻倍*/
    $guadan = $info['nc'];
    if($accept == 0){
        $guadan = 2*$info['nc'];
    }
    if($guadan > $info['gc']){
        return returnErr(1,'善心币余额不足!');
    }
    $p['user_id']        = $data['user_id'];
    $p['money']          = $data['money'];  /*挂单金额*/
    $p['cid']            = $data['cid'];/*挂单社区*/
    $p['name']           = $info['name'];/*挂单社区名称*/
    $p['username']       = $info['username'];
    $p['real_name']      = $info['real_name'];/*用户的真实姓名*/
    $p['ip']             = $data['ip'];/**/
    $p['c']              = $guadan;
   
    $pro = new provideModel();
    $result = $pro->doSaveProvide($p);
    unset($p);
    if($result['code'] == 0){
        return returnErr(1,$result['err']);
    }else{
        /*短信接口处理*/
        $redis = RedisLib::get_instance();
        if(empty($info['phone'])){
            return returnErr(0,'提供资助成功，无法发送短信，请完善个人资料');
        }
        $p['extra_data ']['user_id'] = $data['user_id'];
        $p['extra_data ']['phone'] = $info['phone'];
        $p['extra_data ']['title'] = '挂单扣除善心币';
        $p['extra_data ']['ipaddress']    = $data['ip'];
        $p['extra_data ']['valid_time'] = 0;
        $p['extra_data ']['create_time'] = time();
        $p['extra_data ']['update_time'] = time();
        $p['content'] = "挂单扣除".$guadan."个善心币";
        $redis->lPush('sxh_user_sms', json_encode($p));
        return returnErr(0,'提供资助成功');
    }
};

/*检验用户的基本信息*/
function check_info($data,$info){
    if(empty($info)){
        return returnErr(1,'用户不存在');
    }
    if($info['status'] != 1){
        return returnErr(1,'账户未激活');
    }
    if($info['verify'] != 2){
        return returnErr(1,'账户未审核');
    }
    if($info['bid'] != ''){
        return returnErr(1,'账户处于黑名单');
    }
    if(intval($data['cid']) == 4 && $info['manage_wallet']<100000){
        return returnErr(1,'富人区提供资助金额管理奖必须大于100000');
    }
    if(intval($data['cid']) == 5 && $info['manage_wallet']<250000){
        return returnErr(1,'德善区提供资助金额管理奖必须大于250000');
    }
    if(intval($data['cid']) == 5 && $data['money'] > 2*$info['manage_wallet']){
        return returnErr(1,'德善区提供资助金额必须为管理奖的2倍');
    }
    if(intval($data['money'])%$info['multiple']>0){
        return returnErr(1,'提供资助金额必须必须是规定金额的倍数');
    }
    if($info['security']){
        $pwd       = set_secondary_password(trim($data['sed_pwd']));/*二级密码*/
        $pwd_login = set_password(trim($data['sed_pwd']),$info['security']);/*登录密码*/
    }else{
        $pwd       = set_old_password(trim($data['sed_pwd']));/*二级密码*/
        $pwd_login = set_old_password(trim($data['sed_pwd']));/*登录密码*/
    }
    if($pwd != $info['secondary_password']){
        return returnErr(1,'二级密码错误');
    }
    if($pwd == $pwd_login){
        return returnErr(1,'二级密码与登陆密码不能一致!');
    }
    if($info['special'] == 1){
        return returnErr(1,'管理员不得参与挂单!');
    }
    if($info['is_poor']==0 && $info['cid']==1){
        return returnErr(1,'您不是特困会员，不能在特困区挂单!');
    }
    if((intval($data['money'])< $info['ls'] || intval($data['money']) > $info['ts'])){
        return returnErr(1,'挂单的金额不符合社区要求范围!');
    } 
    return returnErr(0,'提供资助成功');
}

/*调用执行*/
return $provideDo($arr);