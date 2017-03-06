<?php
require_once WANG_MS_PATH."model/userinfo.php";
    /*
     * 用户注册
     */
    $register =  function ($data){
        
        $code = cache('code'.$data['phone']);
        if(empty($code) || $code != $data['verify']){
            //return returnErr(1,'验证码不正确或已过期！');
        }
        $redis = RedisLib::get_instance();
            
        //查帐户是否已被注册
        if($redis->sismemberFieldValue('sxh_user:username' , $data['username'])) {
            return returnErr(1,'帐户已经被注册！');
        }
        //查询手机号是否已被注册（手机，微信号，支付宝号）
        $phone_result           = $redis->sismemberFieldValue('sxh_user_info:phone' , $data['phone']);
        $alipay_account_result  = $redis->sismemberFieldValue('sxh_user_info:alipay_account' , $data['phone']);
        $weixin_account_result  = $redis->sismemberFieldValue('sxh_user_info:weixin_account' , $data['phone']);
        if($phone_result || $alipay_account_result || $weixin_account_result){
            return returnErr(1,'手机号已经被注册！');
        }
        //查询推荐人是否存在
        if(!$redis->sismemberFieldValue('sxh_user:username' , $data['referee_name'])) {
            return returnErr(1,'推荐人信息有误！');
        }
        $m_info = new userinfo();
        $referee_id = $redis->getUserId($data['referee_name']);
        $referee_result = $m_info->getUserOneInfo($referee_id, 'name','sxh_user_info');
        $rename = $referee_result[0]['name'];
        //新增数据（事务）
        $salt = get_rand_num(6);
        //获取 redis 自增id
        $redis_id = $redis->incr('sxh_user:id');
         //调用业务逻辑,生成token
        $user_token = get_user_token($redis_id,$data['name'],'');
        $time   = time();
        //获取IP并转换
        $ip     = $data['ip'];
        $m_info->startTrans();
        //初始化user表
        $user_data = [
                    'id'                    => $redis_id,
                    'username'          => $data['username'],
                    'password'          => set_password($data['password'],$salt),
                    'secondary_password' => set_secondary_password($data['password']),
                    'last_login_time'   => $time,
                    'last_login_ip'     => $ip,
                    'create_time'       => $time,
                    'update_time'       => $time,
                    'security'       => $salt,
                    'user_token'    => $user_token
                ];
        $user_id = $m_info->insertTable($redis_id,$user_data,'sxh_user');
        //初始化userinfo表
        $tel_number = '189'.str_pad($redis_id, 9,'8');
        $userinfo_data = [
                'user_id'            => $redis_id,
                'username'           => $data['username'],
                'name'              => $data['name'],
                'phone'             => $data['phone'],
                'referee'           => $data['referee_name'],
                'referee_id'         => $referee_id,//推荐人ID
                'referee_name'      =>$rename,
                'tel_number'         => $tel_number,
                'create_time'        => $time,
                'update_time'        => $time,
            ];
        $userinfo = $m_info->insertTable($redis_id,$userinfo_data,'sxh_user_info');
         //初始化帐户表
        $account_data = [
            'user_id'                => $redis_id,
            'create_time'            => $time,
            'update_time'            => $time,
        ];
        $user_account = $m_info->insertTable($redis_id,$account_data,'sxh_user_account');
        //初始化relation关系（查找所有父级）
        $res = $m_info->getUserRelation($referee_id,'full_url,a,b,c');
        $re_info = $res[0];
        //合并URL
        if($re_info['full_url']){
            $full_url = $re_info['full_url'].$redis_id.',';
            $p_len = count(explode(',',trim($re_info['full_url'])));
        }else{
            $full_url = ','.$referee_id.','.$redis_id.',';
            $p_len = 2;
        }

        //添加关系表user_relation
        $relation_data = [
            'user_id'        => $redis_id,
//                    'url'           => $p_url,
            'full_url'     => $full_url,
            'edi'          => $referee_id,
            'plevel'      => $p_len,
            'username'   => $data['username'],
            'name'       => $data['name'],
            'create_time'    => $time,
            'update_time'    => $time,
            'a'            => $re_info['a'],
            'b'            => $re_info['b'],
            'c'            => $re_info['c'],
        ];
        $relation = $m_info->insertRelation($relation_data);
        //设置验证码为已经使用
        if($user_id && $userinfo && $user_account && $relation) {
            $m_info->commit();
        }else{
            $m_info->rollback();
            return returnErr(1,'注册失败！');
        }
        //验证码失效
        cache('code'.$data['phone'],null);
        //缓存用户信息
        $redis->multi();
        $redis->setUsernameByID($redis_id,$data['username']);
        $redis->setUserPhoneId( $data['phone'] ,  $redis_id);
        $redis->setUserId( $data['username'] ,  $redis_id);
        $redis->saddField( 'sxh_user:username' ,  $data['username']);
        $redis->saddField(  'sxh_user_info:phone' , $data['phone'] );
        $redis->hsetUserinfoByID($redis_id,'phone',$data['phone']);
        $redis->hsetUserinfoByID($redis_id,'provide_num',0);
        $redis->exec();
        //注册成功，发送短信
        $smsinfo['extra_data'] = [
            'user_id'        => $redis_id,
            'phone'         => $data['phone'],
            'title'         => '注册短信',//短信动作id（注册动作）
            'status'        => 1,//短信发送的状态
            'ip_address'     => $ip,
            'create_time'    => $time,
            'update_time'    => $time,
        ];
        $password = $data['password'];
        $account = $data['username'];
        $smsinfo['content'] = '欢迎成为我们的注册会员，您的登录账号：'.$account.'，密码：'.$password.'。推荐人：'.$rename.'，请妥善保管个人信息。';
        $smsinfo['phone'] = $data['phone'];
        $redis->lPush('sxh_user_sms', json_encode($smsinfo));
        //注册成功，返回数据
        return returnErr(0,'注册成功,激活后即可登录！');
    };
return $register($arr);

