<?php
/*
 * 接受资助相关
 */
class accepthelp extends MMysql
{
    /*获取用户接受资助的信息*/
    public function getUserAcceptInfo($id,$create_time,$field = '*'){
        $table = 'sxh_user_accepthelp_'.date("Y",$create_time).'_'.ceil(date("m",$create_time)/3);
        $sql = "select ".$field." from ".$table." where id = ".$id;
        return $this->doSql($sql);
    }
    /*获取用户的二级密码，特困会员，激活状态，审核状态，黑名单状态,出局钱包*/
    public function getUserDetail($userid){
        /*获取用户所在的表*/
        $table_user       = 'sxh_user_'.ceil($userid/1000000);   /*用户所在的表*/
        $table_user_info  = 'sxh_user_info_'.ceil($userid/1000000);/*用户信息表*/
        $table_account    = 'sxh_user_account_'.ceil($userid/1000000); /*用户金额所在的表*/       
        $sql = "select a.*,b.*,c.id ,d.name from "
                    ." (select id as user_id,username, status,verify,is_poor,secondary_password,security from  ".$table_user." where id = ".$userid." limit 1)a "
                ." join "
                    ." (select   user_id,name  from  ".$table_user_info." where user_id = ".$userid." limit 1)d on a.user_id=d.user_id "
                ." join "
                    ." (select sum(poor_wallet+needy_wallet+needy_wallet+comfortably_wallet+kind_wallet+wealth_wallet+big_kind_wallet) as sum ,greatest(poor_wallet,needy_wallet,needy_wallet,comfortably_wallet,kind_wallet,wealth_wallet,big_kind_wallet) as meger,"
                    . "manage_wallet as mw,poor_wallet as pw,needy_wallet as nw,comfortably_wallet as cw,kind_wallet as kw,wealth_wallet as ww,big_kind_wallet as bkw,user_id as uid from ".$table_account." where user_id =".$userid." limit 1)b on a.user_id = b.uid "
                ." left join "
                    ." (select id ,user_id from sxh_user_blacklist where user_id = ".$userid." limit 1)c on a.user_id = c.user_id ";
        $user = $this->doSql($sql);
        if(count($user) == 0){
            return [];
        }else{
            return current($user);
        }
    }
    /*上笔挂单，是否提取管理奖*/
    public function getUserGap($userid){
        /*最后一次挂单记录，暂时查询两个季度*/
        $table1 = 'sxh_user_provide_'.date("Y").'_'.ceil(date("m")/3);
        $table2 = 'sxh_user_provide_'.((ceil(date("m")/3)-1)?(date("Y").'_'.(ceil(date("m")/3)-1)):(date("Y")-1).'_4');
        $sql1   = "select id from ".$table1." where user_id = ".$userid." and type_id = 1 and status = 3 and flag = 0  order by sign_time desc limit 1 ";
        $p1 = $this->doSql($sql1);  
        if(count($p1) == 0){
            $sql2  = "select  id from ".$table2." where user_id = ".$userid." and type_id = 1 and status = 3 and flag = 0  order by sign_time desc limit 1 ";
            $p2 = $this->doSql($sql1);  
            if(count($p2) == 0){
                return 0;     /*两个季度没有挂单*/
            }else{
                $id = $p2[0]['id'];
            }
        }else{
            $id = $p1[0]['id'];
        }
        $table3 = 'sxh_user_outgo_'.date("Y").'_'.ceil(date("m")/3);
        $table4 = 'sxh_user_outgo_'.((ceil(date("m")/3)-1)?(date("Y").'_'.(ceil(date("m")/3)-1)):(date("Y")-1).'_4');
        $sql3   = "select pid from ".$table3." where user_id = ".$userid." and type = 13  order by create_time desc limit 1 ";
        $p3 = $this->doSql($sql3);  
        if(count($p3) == 0){
            $sql4   = "select pid from ".$table4." where user_id = ".$userid." and type = 13  order by create_time desc limit 1 ";
            $p4 = $this->doSql($sql4);  
            if(count($p4) == 0){
                return 0;     /*两个季度没有提取管理奖*/
            }else{
                $pid = $p4[0]['pid'];
            }
        }else{
            $pid = $p3[0]['pid'];
        }
        if($pid!=$id){
            return 1;  /*最后一次提取管理奖跟最后一次完成的挂单不一致，说明不是最后一次提取*/
        }
        return 0;   /*已经提取过管理奖*/
    }
    public function mergewalletDo($data){
        $redis = RedisLib::get_instance();
        $this->startTrans();
        $table_user = 'sxh_user_account_'.ceil($data['user_id']/1000000);
        $sum = $data['sum'];
        $sql = "update ".$table_user." set ".$data['arr'][1]['field']." = 0 ,".$data['arr'][2]['field']." = 0 ,".$data['arr'][3]['field']." = 0 ,".$data['arr'][4]['field']." = 0 ,".$data['arr'][5]['field']." = 0 ,".
                $data['arr'][0]['field']." = ".$sum." where user_id = ".intval($data['user_id']);
        $res = $this->doSql($sql);
        if(!$res){
            $this->rollback();
            $return['errCode'] = 1;
            $return['msg']     = '合并失败';
            $return['data']    = '';
            return $return;
        }
        $outgo_table  = 'sxh_user_outgo_'.date("Y").'_'.ceil(date("m")/3);
        $income_table = 'sxh_user_income_'.date("Y").'_'.ceil(date("m")/3);
        foreach($data['arr'] as $k=>$v){
            if($k == 0){
               continue;  /*最大列钱包*/
            }
            if($v['k'] == 0){
                continue; /*钱包为零没有支出记录*/
            }
            $outgoinsert = [];
            $outgoinsert['type']     = $v['type'];
            $outgoinsert['id']       = $redis->incr("sxh_user_outgo:id");
            $outgoinsert['user_id']  = intval($data['user_id']);
            $outgoinsert['username'] = $data['username'];
            $outgoinsert['outgo']    = $v['k'];
            $outgoinsert['pid']      = 0;
            $outgoinsert['info']     = '合并钱包';
            $outgoinsert['create_time']     = time();
            $outgoinsert['status']     = 0;
            $insert = $this->insert($outgo_table, $outgoinsert);
            if(!$insert){
                $this->rollback();
                $return['errCode'] = 1;
                $return['msg']     = '合并失败';
                $return['data']    = '';
                return $return;
            }
            $incomeinset = [];
            $incomeinset['id']       = $redis->incr("sxh_user_income:id");
            $incomeinset['type'] = $data['arr'][0]['type'];
            $incomeinset['cid'] = $data['arr'][0]['c'];
            $incomeinset['user_id'] = intval($data['user_id']);
            $incomeinset['username'] = $data['username'];
            $incomeinset['income'] = $v['k'];
            $incomeinset['earnings'] = 0;
            $incomeinset['pid'] = $outgoinsert['id'] ;
            $incomeinset['cat_id'] = 0;
            $incomeinset['info'] = '【App】合并钱包';
            $incomeinset['create_time'] = time();
            $incomeinset['status'] = 0;
            
            $inser = $this->insert($income_table,$incomeinset);
            if(!$inser){
                $this->rollback();
                $return['errCode'] = 1;
                $return['msg']     = '合并失败';
                $return['data']    = '';
                return $return;
            }
        }
        $this->commit();
        $redis->hset("sxh_userinfo:id:".$data['user_id'],$data['arr']['0']['field']."_last_changetime",time());  /*钱包变化的最后时间，用于显示获取钱包的最后钱包变化时间*/              
        $return['errCode'] = 0;
        $return['msg']     = '合并完毕';
        $return['data']    = '';
        return $return;
    }
    /*用户是否完成过挂单*/
    public function getUserProvide($userid){
        $redis = RedisLib::get_instance();
        $provide_num = $redis->hget("sxh_userinfo:id:".$userid,"provide_num");
        if($provide_num>0){
            return 1;
        }else{
            return 0;
        }
    }
    /*接受资助时是否有未完成的提供资助*/
    public function getUserProvideFinish($userid){
        $table_provide_now      = 'sxh_user_provide_'.date("Y").'_'.ceil(date("m")/3);/*当前表*/
        $table_provide_last     = 'sxh_user_provide_'.((ceil(date("m")/3)-1)?(date("Y").'_'.(ceil(date("m")/3)-1)):(date("Y")-1).'_4'); /*90天前的表，一般的排单不打款不会超过90天*/
        $provide_where = "user_id=".$userid." AND status in (1,2) AND type_id=1";
        $sql = "SELECT id FROM ".$table_provide_now." WHERE ".$provide_where." LIMIT 1 "
                . " union "
                . " SELECT id FROM ".$table_provide_last." WHERE ".$provide_where." LIMIT 1";
        $pro_result = $this->doSql($pro_sql);
        if(empty($pro_result)) {
            return 1;
        }
        return 0;
    }
    /*是否有未完成的接受资助*/
    public function getUserAccept($userid){
        $table_accept_now  = 'sxh_user_accepthelp_'.date("Y").'_'.ceil(date("m")/3);
        $table_accept_last = 'sxh_user_accepthelp_'.((ceil(date("m")/3)-1)?(date("Y").'_'.(ceil(date("m")/3)-1)):(date("Y")-1).'_4');
        $sql =  " select user_id from ".$table_accept_now." where user_id = ".$userid." and status in (0,1,2) and flag = 0 and type_id = 1 limit 1"
                . " union "
                . " select user_id from ".$table_accept_last." where user_id = ".$userid." and status in (0,1,2) and flag = 0 and type_id = 1 limit 1";
        $user = $this->doSql($sql);
        if(count($user)>0){
            return  0;
        }
        return  1;
    }
    public function doSaveAccept($data){
        $redis = RedisLib::get_instance();
        $return = [];
        $this->startTrans(); 
        $table_accept_now  = 'sxh_user_accepthelp_'.date("Y").'_'.ceil(date("m")/3);
        $table_outgo  ="sxh_user_outgo_".date("Y")."_".ceil(date("m")/3);
        $table_user = 'sxh_user_account_'.ceil($data['user_id']/1000000);
        $insert_accepthelp['id']         = $redis->incr('sxh_user_accepthelp:id');
        return $insert_accepthelp['id'];
        $insert_accepthelp['type_id']    = 1;
        $insert_accepthelp['money']      = intval($data['money']);
        $insert_accepthelp['used']       = 0;
        $insert_accepthelp['cid']        = $data['arr']['c'];
        $insert_accepthelp['user_id']    = intval($data['user_id']);
        $insert_accepthelp['username']   = $data['username'];
        $insert_accepthelp['name']       = $data['name'];
        $insert_accepthelp['cname']      = $data['arr']['cname'];
        $insert_accepthelp['status']     = 0;
        $insert_accepthelp['batch']      = strtotime(date("Y-m-d"));
        $insert_accepthelp['ipaddress']  = $data['ip'];
        $insert_accepthelp['create_time']= time();
        $insert_accepthelp['update_time']= time();
        $id = $this->insert($table_accept_now,$insert_accepthelp);
        if($id){
            $s = $this->doSql("update ".$table_user." set ".$data['arr']['field']." = ".$data['arr']['field']." - ".intval($data['money'])." where user_id = ".intval($data['user_id']));
            if($s > 0){
                $insert_outgo['id']          = $redis->incr("sxh_user_outgo:id");
                $insert_outgo['type']        = $data['arr']['type'];
                $insert_outgo['user_id']     = intval($data['user_id']);
                $insert_outgo['username']    = $data['username'];
                $insert_outgo['outgo']       = intval($data['money']);
                $insert_outgo['pid']         = $insert_accepthelp['id'];
                $insert_outgo['info']        = '接受资助';
                $insert_outgo['create_time'] = time();
                $insert_outgo['status']      = 0;
                $insid = $this->insert($table_outgo,$insert_outgo);
                if($insid){
                    $provide = $redis->get('sxh_user_accepthelp:userid:'.intval($data['user_id']));
                    if(is_numeric($provide)){/*3秒钟时效，在此期间不处理第二次请求*/
                        $this->rollback(); 
                        $return['errCode'] = 1;
                        $return['msg']     = '不能重复提交';
                        $return['data']    = '';
                        return $return;
                    }
                    $this->commit();
                    $redis->set('sxh_user_accepthelp:userid:'.intval($data['user_id']),1,3);
                    $num = $redis->hget('sxh_userinfo:id:'.intval($data['user_id']),"accepthelp_create_num");
                    if($num == ''){
                        $num =0;
                    }
                    $redis->hset('sxh_userinfo:id:'.intval($data['user_id']),"accepthelp_create_num",($num+1)); /*接受资助的创建次数*/
		    $redis->hset("sxh_userinfo:id:".intval($data['user_id']),$data['arr']['field']."_last_changetime",time());  /*钱包变化的最后时间，用于显示获取钱包的最后钱包变化时间*/  
                    $return['errCode'] = 0;
                    $return['msg']     = '接受资助成功';
                    $return['data']    = '';
                    return $return;
                }
            }
        }
        $this->rollback(); 
        $return['errCode'] = 1;
        $return['msg']     = '接受资助失败';
        $return['data']    = '';
        return $return;
    }
}