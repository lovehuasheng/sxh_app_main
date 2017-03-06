<?php
require_once WANG_MS_PATH."model/userinfo.php";
$output_account = function($data){
    $redis = RedisLib::get_instance();
    //根据post数据，获取用户信息

    $m_user = new userinfo();
    $user_info = $m_user->getUserOneInfo($data['user_id'],'id,username,status,flag,is_transfer,security,secondary_password,password','sxh_user');
    if(!$user_info){
        return returnErr(1,'参数用户ID不存在错误');
    }
    $user = $user_info[0];
    //二级密码验证
    if($user['security']){
        $pwd = set_secondary_password(htmlspecialchars(urldecode($data['password'])));
    }else{
        $pwd = set_old_password(htmlspecialchars(urldecode($data['password'])));
    }
    if($user['secondary_password'] != $pwd) {
        return returnErr(1,'二级密码错误，请重新输入！');
    }
    //二级密码与登录密码对比，如果相等提示更改
    if($user['security']){
        $pwd = set_password(htmlspecialchars(urldecode($data['password'])),$user['security']);
    }else{
        $pwd = set_old_password(htmlspecialchars(urldecode($data['password'])));
    }
    if($user['password'] == $pwd) {
        return returnErr(1,'二级密码不能与登录密码一致，请到电脑端重置！');
    }
    if($user['username']==$data['recipient_account']){
        return returnErr(1,'不能给自己转');
    }
    if($user['status'] != 1){
        return returnErr(1,'您的账号未激活或被冻结');
    }
    //特殊账号另外处理
    $spec_id = array('90','156','82');
    if(!in_array($user['id'],$spec_id)){
        if($data['money_type'] == 1){
            if($data['money_sum']>100){
                return returnErr(1,'转出数量超过上限');
            }
        }else{
            if($data['money_sum']>300){
                return returnErr(1,'转出数量超过上限');
            }
        }
    }else{
        if($data['money_sum']>500){
            return returnErr(1,'转出数量超过500上限');
        }
    }
    //取出接受者的ID
    $redis = RedisLib::get_instance();
    $reci_id = $redis->getUserId($data['recipient_account']);
    $pid = $m_user->getUserOneInfo($reci_id,'id,status','sxh_user');
    if(empty($pid) || $pid[0]['status'] != 1){
        return returnErr(1,'接收人账号不存在或未激活或被冻结');
    }
    //获取字段名称
    $arr_field = array(1=>'activate_currency',2=>'guadan_currency');
    $field = $arr_field[$data['money_type']];
    $uinfo = $m_user->getUserOneInfo($data['user_id'],$field,'sxh_user_account');
    if($uinfo[0][$field]<$data['money_sum']){
        return returnErr(1,'超额转出');
    }
    $arr_dec = array(1=>'善种子',2=>'善心币');
    $type_name = $arr_dec[$data['money_type']];
    //判断是否有任意转币权限，如果没有则只能在5级内转币
    if(!$user['is_transfer']){
        //除了特殊ID外
        if(!in_array($user['id'],$spec_id)){
            $res_rela = $m_user->getUserRelation($pid[0]['id'],'full_url');
            $len = count(trim($res_rela[0]['full_url'],','));
            if($len>6){
                $arr = explode(',',trim($res_rela[0]['full_url'],','));
                $full_url = ','.$arr[$len-1].','.$arr[$len-2].','.$arr[$len-3].','.$arr[$len-4].','.$arr[$len-5].',';
            }else{
                $full_url = $res_rela[0]['full_url'];
            }
            if(strpos($full_url,','.$user['id'].',')===false){
                return returnErr(1,'操作失败，只能给5级内的下属会员转出');
            }
        }
    }

    $check_token = $redis->incr('retoken_'.$data['check_token'].$data['money_type'].$pid[0]['id']);
    if($check_token>1){
        return returnErr(1,'数据不能重复提交');
    }
    //处理数据
    $m_user->startTrans();
    //扣除操作account表
    $field_val = '`'.$field.'`=`'.$field.'`-'.$data['money_sum'].',`update_time`='.time();
    $user_flag = $m_user->updateUserAccount($data['user_id'],$field_val,'sxh_user_account');
    //增加收入用户的账户
    $field_val = '`'.$field.'`=`'.$field.'`+'.$data['money_sum'].',`update_time`='.time();
    $p_flag = $m_user->updateUserAccount($pid[0]['id'],$field_val,'sxh_user_account');
    //插入outgo表
    $out = array();
    $out['id'] = $redis->incr('sxh_user_outgo:id');
    $out['type'] = $data['money_type'];
    $out['outgo'] = $data['money_sum'];
    $out['user_id'] = $data['user_id'];
    $username = $redis->getUsernameByID($data['user_id']);
    $out['pid'] = $pid[0]['id'];
    $out['other_username'] = $data['recipient_account'];
    $out['username'] = $username;
    $out['info'] = '【App】'.$data['notes'];
    $out['create_time'] = time();
    $out_flag = $m_user->insertDesc($out,'sxh_user_outgo');
    //插入income表
    $scome = array();
    $scome['id'] = $redis->incr('sxh_user_income:id');
    $scome['type'] = $data['money_type'];
    $scome['income'] = $data['money_sum'];
    $scome['username'] = $data['recipient_account'];
    $scome['user_id'] = $pid[0]['id'];
    $scome['pid'] = $data['user_id'];
    $scome['other_username'] = $username;
    $scome['cat_id'] = $out['id'];
    $scome['info'] = '【App】'.$data['notes'];
    $scome['create_time'] = time();
    $income_flag = $m_user->insertDesc($scome,'sxh_user_income');
    if($user_flag && $p_flag && $out_flag && $income_flag){
        $m_user->commit();
        $flag_set = $redis->set('retoken_'.$data['check_token'].$data['money_type'].$pid[0]['id'],1,120);
        if(!$flag_set){
            $redis->set('retoken_'.$data['check_token'].$data['money_type'].$pid[0]['id'],1,120);
        }
    }else{
        $m_user->rollback();
        $flag_del = $redis->del('retoken_'.$data['check_token'].$data['money_type'].$pid[0]['id']);
        if(!$flag_del){
            $redis->del('retoken_'.$data['check_token'].$data['money_type'].$pid[0]['id']);
        }
        return returnErr(1,'系统繁忙，请重新操作！');
    }

    $user_phone = $redis->hgetUserinfoByID($data['user_id'],'phone');
    $reci_phone = $redis->hgetUserinfoByID($pid[0]['id'],'phone');
    //推送信息
    $sdata = array();
    $sdata['extra_data ']['user_id'] = $data['user_id'];
    $sdata['extra_data ']['phone'] = $user_phone;
    $sdata['extra_data ']['title'] = '转出'.$type_name;
    $sdata['extra_data ']['code'] = '';
    $sdata['extra_data ']['status'] = 1;
    $sdata['extra_data ']['ip_address'] = $data['ip'];
    $sdata['extra_data ']['valid_time'] = '';
    $sdata['extra_data ']['create_time'] = time();
    $sdata['extra_data ']['update_time'] = time();
    $username = $user['username'];
    $time = date('Y-m-d H:i:s');
    $number = $data['money_sum'];
    $sdata['content'] = "您好，您的".$username."账户于".$time."成功扣除".$number."个".$type_name."。";
    $sdata['phone'] = $user_phone;

    $redis->lPush('sxh_user_sms', json_encode($sdata,JSON_UNESCAPED_UNICODE));

    $sdata['extra_data ']['user_id'] = $pid[0]['id'];
    $sdata['extra_data ']['phone'] = $reci_phone;
    $sdata['extra_data ']['title'] = '转入'.$type_name;
    $username = $data['recipient_account'];
    $sdata['content'] = "您好，您的".$username."账户于".$time."成功充值".$number."个".$type_name."。";
    $sdata['phone'] = $reci_phone;
    $redis->lPush('sxh_user_sms', json_encode($sdata,JSON_UNESCAPED_UNICODE));

    return returnErr(0,'转出成功');
};
return $output_account($arr);

