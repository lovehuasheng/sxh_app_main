<?php
    include  "../db/model/user.php";
    include  "../db/model/matchhelp.php";
    include  "../db/model/community.php";
    include  "../db/model/provide.php";
    include  "../db/model/accepthelp.php";
    /**
     * 确认收款
     */
    if(empty($arr['user_id']) || !is_numeric($arr['user_id']) || $arr['user_id']<1){
        return returnErr(1,'请先登录');
    }
    if(empty($arr['sed_pwd'])){
        return returnErr(1,'二级密码不能为空！');
    }
    if(!is_numeric($arr['match_id']) || $arr['match_id']<1){
        return returnErr(1,'请选择正确的记录！');
    }
    $rule = '/^\d{10}$/';
    if(!preg_match($rule, $arr['create_time']) || $arr['create_time']>time() || $arr['create_time'] < strtotime("2016-01-01") ){
        return returnErr(1,'请选择正确的时间！');
    }
    /*验证用户基本信息*/
    $userinfo = function($data){
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
        return returnErr(0);
    };
     
    /*获取验证收款匹配订单信息*/
    $match_data = function($data){
        $match = new matchhelp();
        $field = 'pid,id,type_id,status,other_user_id,other_id,user_id,other_money,other_username,other_cid,other_type_id,type_id,create_time,provide_create_time,accepthelp_create_time';
        $match_data = $match->getMatchHelpInfo($data['match_id'],$data['create_time'],$field);
        if(count($match_data) == 0){
            return  returnErr(1,'请选择正确的匹配收款订单！');
        }
        $match_data = current($match_data);
        if($match_data['status'] != 2){
            return  returnErr(1,'订单不处于可确认接款状态！');
        }
        if($match_data['user_id'] != $data['user_id']){
            return  returnErr(1,'这不是你的匹配收款订单！');
        }
        /*更新匹配表中的完成状态，保证请求执行的串行*/
        $update = $match->updateMatchStatus($data['match_id'],$data['create_time'],3);
        if(!$update){
            return  returnErr(1,'不能重复收款操作！');
        }
        return returnErr(0,'',$match_data);
    };
    /*获取打款人订单的社区信息*/
    $community = function($cid){
        $community = new community();
        /*表中只有几条记录可以*查询*/
        $community_data = $community->getCommunityInfo($cid);
        if(count($community_data) == 0){
            return  returnErr(1,'订单社区错误！');
        }
        return returnErr(0,'',current($community_data));
    };
    $provide = function($id,$create_time){
        $provideModel = new provideModel();
        $field = ' type_id,money,used,status,pay_num,match_num,is_company,finish_count ';
        $provide_data = $provideModel->getProvideInfo($id,$create_time,$field);
        if(count($provide_data) == 0){
            return  returnErr(1,'打款人订单错误！');
        }
        return returnErr(0,'',current($provide_data));
    };
    $accepthelp = function($id,$create_time){
        $_accepthelp = new accepthelp();
        $field = 'status,pay_num,money,used,finish_count,match_num,type_id';
        $accepthelp_data = $_accepthelp->getUserAcceptInfo($id,$create_time,$field);
        if(count($accepthelp_data) == 0){
            return  returnErr(1,'订单错误！');
        }
        return returnErr(0,'',current($accepthelp_data));
    };
    /*确认收款  $match_info匹配信息，$com_info社区信息，$provide_info打款人挂单信息，$accepthelp_info接受资助信息*/
    $confirm = function($match_info,$com_info,$provide_info,$accepthelp_info){
        $provideModel = new provideModel();
        $return = $provideModel->doConfirmMoney($match_info,$com_info,$provide_info,$accepthelp_info);
        if($return == false){  /*收款失败，需要把之前的状态更改为未收款状态*/
            $match = new matchhelp();
            $update = $match->updateMatchStatus($match_info['id'],$match_info['create_time'],2);
            return false;
        }
        return $return;
    };

    /*验证用户基本信息*/
    $return = $userinfo($arr);
    if($return['errCode'] == 1) return $return;
    unset($return);

    /*获取匹配收款信息*/
    $return = $match_data($arr);
    if($return['errCode'] == 1) return $return;
    $match_info = $return['data'];
    unset($return);

    /*获取打款人社区信息*/
    $return =  $community($match_info['other_cid']);
    if($return['errCode'] == 1) return $return;
    $com_info = $return['data'];
    unset($return);

    /*获取打款人的挂单信息*/
    $return = $provide($match_info['other_id'],$match_info['provide_create_time']);
    if($return['errCode'] == 1) return $return;
    $provide_info = $return['data'];
    unset($return);

    /*获取收款人的接受资助信息*/
    $return = $accepthelp($match_info['pid'],$match_info['accepthelp_create_time']);
    if($return['errCode'] == 1) return $return;
    $accepthelp_info = $return['data'];
    unset($return);

    $provide_info['i'] = 0;/*打款人不是最后一次收款*/
    if($provide_info['finish_count'] == 0){
        $provide_info['i'] = 1; /*第一次*/
    }
    if($provide_info['money'] == $provide_info['used'] && $provide_info['match_num'] == ($provide_info['finish_count']+1)){  /*最后一次收款*/
        $provide_info['i'] += 100;
    }
    $accepthelp_info['i'] = 0;
    if($accepthelp_info['finish_count'] == 0){
        $accepthelp_info['i'] = 1;/*收款人不是最后一次收款*/
    }
    if($accepthelp_info['money'] == $accepthelp_info['used'] && $accepthelp_info['match_num'] == ($accepthelp_info['finish_count']+1)){  /*最后一次收款*/
        $accepthelp_info['i'] += 100;
    }

    $return = $confirm($match_info,$com_info,$provide_info,$accepthelp_info);

   /*根据处理结果来更新redis数据等*/
   if($return == false){
       return  returnErr(1,'确认收款失败！');
   }
   $redis = RedisLib::get_instance(); 
   /*转接单处理*/
    if($provide_info['type_id'] == 2 && ($provide_info['i']== 101 || $provide_info['i']== 100)){  
        $num = $redis->hget('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp_finish_num");/*提出接受资助的次数*/
        if($num == '') $num = 0;
        $redis->hset('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp_finish_num",$num+1);/*接受资助的次数+1*/
    }

    /*个人收款redis的处理*/
    if($provide_info['is_company'] == 0){
        if($provide_info['i'] == 1 || $provide_info['i'] == 101){ /*第一笔匹配打款*/
            $num = $redis->hget('sxh_userinfo:id:'.intval($match_info['other_user_id']),"provide_finish_num");/*提出挂单的次数*/
            if($num ==''){
                $num = 0;
            }
            $redis->hset('sxh_userinfo:id:'.intval($match_info['other_user_id']),"provide_finish_num",($num+1));/*成功挂单，挂单的次数+1*/
        }
        if($provide_info['i'] == 100 || $provide_info['i'] == 102){/*最后一笔匹配打款*/
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_last_community_id",$match_info['other_cid']);/*最近挂单的社区ID*/
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_current_id",$match_info['other_id']);/*上一次完成的订单ID*/
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_current_money",$provide_info['money']);/*上一次完成的订单金额*/
            $provide_num = $redis->hget("sxh_userinfo:id:".$match_info['other_user_id'],"provide_num");
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_num",($provide_num+1));/*完成的订单次数，包含所有社区*/
            $provide_community = $redis->hget("sxh_userinfo:id:".$match_info['other_user_id'],"provide_community_".$match_info['other_cid']."_count")+1;
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_community_".$match_info['other_cid']."_count",$provide_community); /*用户在特定某个社区挂单的次数*/
        }
        if(($accepthelp_info['i'] == 101 || $accepthelp_info['i'] == 100)&&$accepthelp_info['type_id'] == 1  ){ /*接受资助的最后一笔匹配*/
            $num1 = $redis->hget('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp_finish_num");/*接受资助的次数*/
            if($num1 == '') $num1 = 0;
            $redis->hset('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp_finish_num",$num1+1);/*接受资助，挂单的次数+1*/
        }
        //$redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],$return_status['field']."_last_changetime",time());  /*钱包变化的最后时间，用于显示获取钱包的最后钱包变化时间*/              
        $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_match_time",time());  /*上一笔完成匹配打款的时间，用于挂单扣除善心币是否翻倍*/
        if($provide_info['type_id'] == 1){
            $redis->hset("sxh_userinfo:id:".$match_info['user_id'],"accept_match_time",time());/*上一笔完成匹配收款的时间，用于挂单扣除善心币是否翻倍*/
        }
    }
    if($provide_info['is_company'] == 1){
        if($provide_info['i'] ==1 || $provide_info['i'] ==101 ){ /*第一笔匹配打款可能也是最后一笔*/
            $num = $redis->hget('sxh_userinfo:id:'.intval($match_info['other_user_id']),"provide_finish_num");/*提出挂单的次数*/
            $redis->hset('sxh_userinfo:id:'.intval($match_info['other_user_id']),"provide_finish_num",$num+1);/*成功挂单，挂单的次数+1*/
        }
        if($provide_info['i'] == 100 || $provide_info['i'] ==101){/*最后一笔匹配打款 可能也是第一笔*/
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_current_id",$match_info['other_id']);/*上一次完成的订单ID*/
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_current_money",$match_info['pro_money']);/*上一次完成的订单金额*/
            $provide_num = $redis->hget("sxh_userinfo:id:".$match_info['other_user_id'],"provide_num");
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_num",($provide_num+1));/*完成的订单次数*/
            $provide_community = $redis->hget("sxh_userinfo:id:".$match_info['other_user_id'],"provide_community_".$match_info['other_cid']."_count")+1;
            $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_community_".$match_info['other_cid']."_count",$provide_community);/*社区的挂单次数*/
        }
        if(($accepthelp_info['i'] == 100||$accepthelp_info['i'] == 101) && $accepthelp_info['type_id'] == 1  ){
            $num = $redis->hget('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp_finish_num");/*完成接受资助的次数*/
            if($num == '') $num = 0;
            $redis->hset('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp_finish_num",$num+1);/*完成接受资助的次数 +1*/
            if($match_info['cid']==8){
                $num2 = $redis->hget('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp8_finish_num");/*货款提取接受资助完成数*/
                if($num2 == '') $num2 = 0;
                $redis->hset('sxh_userinfo:id:'.intval($match_info['user_id']),"accepthelp8_finish_num",$num2+1);/*货款提取接受资助完成数 +1*/
            }
        }
        $redis->hset("sxh_userinfo:id:".$match_info['other_user_id'],"provide_match_time",time());/*上一笔匹配打款完成时间*/
        if($accepthelp_info['type_id'] == 1){
            $redis->hset("sxh_userinfo:id:".$match_info['user_id'],"accept_match_time",time());/*上一笔匹配收款完成时间*/
        }
    }







return returnErr(0,'确认完毕');;