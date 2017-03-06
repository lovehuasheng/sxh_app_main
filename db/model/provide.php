<?php
class provideModel extends MMysql
{
    /*挂单数据处理,插入数据库*/
    public function doSaveProvide($d){
        $redis = RedisLib::get_instance();
        $return = [];
        $insert_provide['id']          = $redis->incr("sxh_user_provide:id");
        $insert_provide['type_id']     = 1;
        $insert_provide['money']       = intval($d['money']);
        $insert_provide['cid']         = $d['cid'];
        $insert_provide['cname']       = $d['name'];/*社区名*/
        $insert_provide['user_id']     = intval($d['user_id']);
        $insert_provide['username']    = $d['username']; /*用户名*/
        $insert_provide['name']        = $d['real_name'];/*真实姓名*/
        $insert_provide['status']      = 0;
        $insert_provide['batch']       = strtotime(date("Y-m-d"));
        $insert_provide['ipaddress']   = $d['ip'];
        $insert_provide['sign_time']   = '0';
        $insert_provide['create_time'] = time();
        $insert_provide['update_time'] = time();
        $insert_provide['match_num']   = 0;
        $insert_provide['pay_num']     = 0;
        $insert_provide['flag']        = 0;
        $table_user_account = 'sxh_user_account_'.ceil($d['user_id']/1000000);
        $this->startTrans();
        $table = 'sxh_user_provide_'.date("Y").'_'.ceil(date("m")/3);
        $id = $this->insert($table,$insert_provide);
        if($id){
            $s = $this->doSql("update ".$table_user_account." set guadan_currency = guadan_currency - ".intval($d['c'])." where user_id = ".intval($d['user_id']));
            if($s){
                $insert_outgo['id']      = $redis->incr("sxh_user_outgo:id");
                $insert_outgo['type']    = 2;
                $insert_outgo['outgo']   = intval($d['c']);
                $insert_outgo['user_id'] = intval($d['user_id']);
                $insert_outgo['pid']     = $insert_provide['id'];
                $insert_outgo['info']    = '【App】提供资助扣善心币';
                $insert_outgo['create_time']    = time();
                $insid = $this->insert('sxh_user_outgo_'.date("Y").'_'.ceil(date("m")/3),$insert_outgo);
                if($insid){
                    /*设置防重复操作*/
                    $provide = $redis->get('sxh_user_provide:userid:'.intval($d['user_id']));
                    if(is_numeric($provide)){/*3秒钟时效，在此期间不处理第二次请求*/
                        $this->rollback();  
                        $return['err'] = '不能重复提交';
                        $return['code'] = 0;
                        return $return;
                    }
                    $this->commit(); 
                    $redis->set('sxh_user_provide:userid:'.intval($d['user_id']),1,3);
                    $num = $redis->hget('sxh_userinfo:id:'.intval($d['user_id']),"provide_create_num");/*提出挂单的次数*/
                    if($num == ''){
                        $num = 0;
                    }
                    $redis->hset('sxh_userinfo:id:'.intval($d['user_id']),"provide_create_num",$num+1);/*成功挂单，挂单的次数+1*/                
                    $return['code'] = 1;
                    return $return;
                }
            }
        }
        $this->rollback(); 
        $return['err'] = '提供资助失败';
        $return['code'] = 0;
        return $return;
        
    }
    /*取消订单*/
    public function cancelProvide($d){
        $table = 'sxh_user_provide_'.date("Y",$d['create_time']).'_'.ceil(date("m",$d['create_time'])/3);
        $sql   = 'update '.$table.' set flag = 2 where id = '.$d['id'].' and status = 0 and match_num = 0 and user_id = '.$d['user_id'];
        $row   = $this->doSql($sql);
        if($row == 1){
            $return['msg'] = '取消提供资助成功';
            $return['errCode'] = 0;
            return $return;
        }
        $return['msg'] = '不能取消提供资助';
        $return['errCode'] = 1;
        return $return;
    } 
    /*查询单条用户挂单信息*/
    public function getProvideInfo($id,$create_time,$field = '*'){
        $table = 'sxh_user_provide_'.date("Y",$create_time).'_'.ceil(date("m",$create_time)/3);
        $sql = "select ".$field." from ".$table." where id = ".$id;
        return $this->doSql($sql);
    }
    /*确认收款   $match_info匹配信息，$com_info社区信息，$provide_info打款人挂单信息，$accepthelp_info接受资助信息*/
    public function doConfirmMoney($match_info,$com_info,$provide_info,$accepthelp_info){
        $this->startTrans(); /*开启事物*/
        /*更新provide，accepthelp，matchhelp表中的状态*/
        $return = $this->updateMatchStatus($match_info,$provide_info,$accepthelp_info);
        if($return == false){
            $this->rollback(); /*事务回滚*/
            return false;
        }
        $match_info['is_company'] = $provide_info['is_company'];
        /*关于转接单的处理*/
        if($match_info['other_type_id'] == 2){  
            $back_data = $this->setRebate($match_info);/*个人直接返利%5（接单钱包，还是社区钱包）不在考虑上级  企业直接返利%5企业钱包不在考虑上下级*/
            if($back_data == false){
                $this->rollback(); /*事务回滚*/
                return false;
            }
            $this->commit(); /*转接单收款成功*/
            return true;
        }
        /*关于返利的计算，这里分为个人返利和企业返利*/
        $match_info['i'] = $provide_info['i'];
        if($match_info['is_company'] == 0){
            $return = $this->userParentLevelRebate($match_info,$com_info);/*个人*/
        }else{
            $return = $this->companyParentLevelRebate($match_info,$com_info);/*企业*/
        }
        if($return == false) {
            $this->rollback(); /*事务回滚*/
            return false;
        }
        /*更新匹配订单的收款时间*/
        $table_matchhelp_now = 'sxh_user_matchhelp_'.date("Y",$match_info['create_time']).'_'.ceil(date("m",$match_info['create_time'])/3);
        $sql = "update ".$table_matchhelp_now ." sign_time = ".time()." where id = ".$match_info['id'];
        $return = $thi->doSql($sql);
        if(!$return){
            $this->rollback(); /*事务回滚*/
            return false;
        }
        $this->commit();  /*事物提交*/
        return true;
    }
    /*更新状态*/
    private function updateMatchStatus($match_info,$provide_info,$accepthelp_info){
        $tabal_provide = "sxh_user_provide_".date("Y",$match_info['provide_create_time'])."_".ceil(date("m",$match_info['provide_create_time'])/3);
        $sql = "update ".$tabal_provide." set finish_count = finish_count + 1  where id = ".$match_info['other_id'];
        if($provide_info['i'] == 100){
            $sql = "update ".$tabal_provide." set finish_count = match_num,status =3 ,sign_time = ".time()." where id = ".$match_info['other_id'];
        }
        $update_pro = $this->doSql($sql);
        if(!$update_pro)  return false;
        $tabal_accept = "sxh_user_accepthelp_".date("Y",$match_info['accepthelp_create_time'])."_".ceil(date("m",$match_info['accepthelp_create_time'])/3);
        $sql2 = "update ".$tabal_accept." set finish_count = finish_count + 1  where id = ".$match_info['pid'];
        if($accepthelp_info['i'] == 100){
            $sql2 = "update ".$tabal_accept." set finish_count = finish_count + 1,status =3 ,sign_time = ".time()." where id = ".$match_info['pid'];      
        }
        $update_acc = $this->doSql($sql2);
        if(!$update_acc) return false;
        return true;    
    }
    /*关于转接单的处理*/
    private function setRebate($match_info){
        $redis = RedisLib::get_instance();
        $table_user = 'sxh_user_account_'.ceil($match_info['other_user_id']/1000000);
        $field = 'order_taking';  /*个人转接单*/
        if($match_info['is_company'] == 1)  $field = 'company_order_taking';/*企业转接单*/
        $income      = $match_info['other_money']*5*0.01+$match_info['other_money'];
        $earnings    = $match_info['other_money']*5*0.01;
        $sql = "update ".$table_user." set ".$field." = ".$field." + ".$income." where user_id = ".$match_info['other_user_id'];
        $update = $this->doSql($sql);
        if(!$update)          return false;
        $insertincome1['id']          = $redis->incr("sxh_user_income:id");
        $insertincome1['cid']         = $match_info['other_cid'] ;
        $insertincome1['user_id']     = $match_info['other_user_id'];
        $insertincome1['username']    = $match_info['other_username'];
        $insertincome1['pid']         = $match_info['other_id'];
        $insertincome1['create_time'] = time();
        $insertincome1['status']      = 0;    
        $insertincome1['income']      = $income;/*转接单收益5%*/
        $insertincome1['earnings']    = $earnings;
        $insertincome1['info']        = '转接单收益接单钱包';
        $insertincome1['type']        =  6 ;/*接单钱包*/
        if($match_info['is_company'] == 1)  $insertincome1['type']    =  12 ; /*企业钱包*/
        $insert = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome1);
        if(!$insert){
            return false;
        }
        return true;
    }
    /*个人确认收款返利*/
    private function userParentLevelRebate($match_info,$com_info){
        $redis = \org\RedisLib::get_instance();
        /*查询用户上五级*/
        $user_relation = $this->doSql("select full_url from sxh_user_relation where user_id = ".$match_info['other_user_id']." limit 1");
        if(count($user_relation) == 0) return false;
        $pids =  explode(',',$user_relation[0]['full_url']);
        krsort($pids);
        $p = [];  /*返利上级数组*/
        $l = 0;     /*层级*/
        foreach($pids as $k=>$v){
            $l += 1;
            if($v == '' || $l>=8){
                continue;
            }
            $p[]=$v; 
        }
        if(isset($p[0])){/*挂单人*/
            $user[0]['user_id'] = $p[0]; 
            $user[0]['rebate']  = $com_info['rebate'];
            if($match_info['other_cid'] == 3){ /*小康社区挂单五次以上，返利变成15%*/
                $provide_tables = $this->doSql("show tables like 'sxh_user_provide_201%'");
                ksort($provide_tables);
                $sql = '';
                foreach($provide_tables as $k=>$v){
                    if($k==0){
                        $sql  = " select id from ".current($v)." where user_id = ".$match_info['other_user_id']." and cid = 3 and status = 3 and type_id=1 and flag = 0 limit 0,6 ";
                    }else{
                        $sql .= " union select id from ".current($v)." where user_id = ".$match_info['other_user_id']." and cid = 3 and status = 3 and type_id = 1 and flag = 0 limit 0,6 ";
                    }
                }
                $provide = $this->doSql($sql);
                if(count($provide) == 6){
                    $user[0]['rebate']  = 15; 
                }
            }
            $user[0]['level']   = 0;
            $user[0]['field']   = $com_info['wallet_field'];
            $user[0]['rebate_money']  = $match_info['other_money']*$user[0]['rebate']*0.01 + $match_info['other_money'];
        }
        if(isset($p[1])){/*一级推荐人*/
           $user[1]['user_id'] = $p[1]; 
           $user[1]['rebate']  = $com_info['level1_rebate'];
           $user[1]['level']   = 1;
           $user[1]['rebate_money']  = $match_info['other_money']*$user[1]['rebate']*0.01;
        }
        if(isset($p[3])){/*三级级推荐人*/
           $user[3]['user_id'] = $p[3]; 
           $user[3]['rebate']  = $com_info['level3_rebate'];
           $user[3]['level']   = 3;
           $user[3]['rebate_money']  = $match_info['other_money']*$user[3]['rebate']*0.01;
        }
        foreach($user as $k=>$v){
            $table_user = '';
            $table_user = 'sxh_user_account_'.ceil($v['user_id']/1000000);
            $insertincome1 = [];
            $insertincome1['cid']         = $match_info['other_cid'] ;
            $insertincome1['user_id']     = $v['user_id'];
            $insertincome1['username']    = $redis->get("sxh_user:id:".$v['user_id'].":username");
            $insertincome1['pid']         = $match_info['other_id'];
            $insertincome1['create_time'] = time();
            $insertincome1['status']      = 0;
            if($v['level'] == 0){ /*自身返利*/
                $update_user = $this->doSql("update ".$table_user." set ".$com_info['wallet_field']." = ".$com_info['wallet_field']." + ".$v['rebate_money']." where user_id = ".$v['user_id']);
                if(!$update_user)return false;
                switch($com_info['wallet_status']){
                    case 7:$package = '特困钱包';break;
                    case 8:$package = '贫穷钱包';break;
                    case 9:$package = '小康钱包';break;
                    case 10:$package = '德善钱包';break;
                    case 11:$package = '富人钱包';break;
                    case 15:$package = '大德钱包';break;
                }
                $insertincome1['id']          = $redis->incr("sxh_user_income:id");
                $insertincome1['type']        = $com_info['wallet_status'] ;
                $insertincome1['income']      = $v['rebate_money'];
                $insertincome1['earnings']    = $v['rebate_money'] - $match_info['other_money'];
                $insertincome1['info']        = '挂单收益'.$package;
                $insert = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome1);
                if(!$insert)return false;
                /*完成第一笔匹配或者只有一笔挂单返善心币转成善金币，翻倍的善心币作为也按照正常处理*/
                if(($match_info['i'] == 101||$match_info['i'] == 1)){
                    $insertincome1['id']          = $redis->incr("sxh_user_income:id");
                    $insertincome1['type']        = 3 ;
                    $insertincome1['income']      = $com_info['need_currency']*100;
                    $insertincome1['earnings']    = $com_info['need_currency']*100;
                    $insertincome1['info']        = '完成挂单返善金币';
                    $update_user_acc = $this->doSql("update ".$table_user." set invented_currency = invented_currency + ".$insertincome1['income']." where user_id = ".$v['user_id']);
                    if(!$update_user_acc) return false;
                    $insert = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome1);
                    if(!$insert)return false;
                }
            }else{ /*1,3级返利管理钱包和善金币*/
                $money = $v['rebate_money']/2;
                $update_user = Db::execute("update ".$table_user." set  manage_wallet = manage_wallet + ".$money.",invented_currency = invented_currency + ".$money."  where user_id = ".$v['user_id']);
                if(!$update_user)  return false;
                $insertincome1['id']          = $redis->incr("sxh_user_income:id");
                $insertincome1['type']        = 5 ;
                $insertincome1['income']      = $money;
                $insertincome1['earnings']    = $money;
                $insertincome1['info']        = '返利管理钱包';
                $insert1 = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome1);
                if(!$insert1)return false;
                $insertincome1['id']          = $redis->incr("sxh_user_income:id");
                $insertincome1['type']        = 3;
                $insertincome1['info']        = '返利善金币';
                $insert2 = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome1);
                if($insert2<=0)return false;
            }
        }
        return true;
    }
    /*企业确认收款返利$match_info匹配信息，$com_info社区信息*/
    private function companyParentLevelRebate($match_info,$com_info){
        /*获取返利企业信息,企业表数据较少可以*查询*/
        $sql = "select * from sxh_company_info where company_id =  ".$match_info['other_user_id']." limit 1";
        $company_info=$this->doSql($sql);
        if(count($company_info) == 0) return false;
        $comp = current($company_info);
        $cp = [];/*返利计算数组*/
        if($match_info['other_user_id'] >0){     /*自身返利   第一层*/
            $cp[0]['user_id'] = $match_info['other_user_id'];
            $cp[0]['level']   = 0;
            $cp[0]['rebate']  = $com_info['rebate'];
            $cp[0]['rebate_money']  = $cp[0]['rebate']*$match_info['other_money']*0.01+$match_info['other_money'];
            if($comp['business_type'] == 1){   /*商务中心只返本金*/
                $cp[0]['rebate_money']  = $match_info['other_money'];
            }
        }
        if($comp['referee_id'] > 0){    /*引荐人  第二层*/
            $cp[1]['user_id'] = $comp['referee_id'];
            $cp[1]['level']   = 1;
            $cp[1]['rebate']  = $com_info['level1_rebate'];
            $cp[1]['rebate_money']  = $cp[1]['rebate']*$match_info['other_money']*0.01;
        }
        if($comp['membership_id'] > 0){ /*招商员  第二层*/
            $cp[2]['user_id'] = $comp['membership_id'];
            $cp[2]['level']   = 2;
            $cp[2]['rebate']  = $com_info['membership_rebate'];
            $cp[2]['rebate_money']  = $cp[2]['rebate']*$match_info['other_money']*0.01;
        }
        if($comp['business_center_id'] > 0){ /*商务中心  第二层*/
            $cp[3]['user_id'] = $comp['business_center_id'];
            $cp[3]['level']   = 3;
            $cp[3]['rebate']  = $com_info['business_rebate'];
            $cp[3]['rebate_money']  = $cp[3]['rebate']*$match_info['other_money']*0.01;
        }
        $redis = RedisLib::get_instance();
        /*循环处理返利*/
        foreach($cp as $k=>$v){
            $table_user = '';
            $table_user = 'sxh_user_account_'.ceil($v['user_id']/1000000);   /*用户所在表*/
            $insertincome2 = [];
            $insertincome2['cid']         = $match_info['other_cid'] ;       /*打款的社区*/
            $insertincome2['user_id']     = $v['user_id'];                   /*返利的用户*/
            $username = $redis->get("sxh_user:id:".$v['user_id'].":username");/*收款的用户名*/
            if(!$username) $username = '';
            $insertincome2['username']    = $username;
            $insertincome2['pid']         = $match_info['other_id'];
            $insertincome2['create_time'] = time();
            $insertincome2['status']      = 0;
            if($v['level'] == 0){         /*自身企业钱包本金+利息*/
               $update_user =$this->dosql("update ".$table_user." set  company_wallet = company_wallet + ".$v['rebate_money']." where user_id = ".$v['user_id']);
                if(!$update_user)return false;
                $insertincome2['id']          = $redis->incr("sxh_user_income:id");
                $insertincome2['type']        = 12;
                $insertincome2['income']      = $v['rebate_money'];
                $insertincome2['earnings']    = $v['rebate_money']-$match_info['other_money'];
                $insertincome2['info']        = '挂单收益企业钱包';
                $insert2 = $this->dosql($insertincome2);
                if(!$insert2)return false;
                /*商务中心挂单善心币返回善金币*/
                if(($match_info['i'] == 101||$match_info['i'] == 1) && $comp['business_type'] == 1){ /*挂单匹配收款的最后一笔，商务中心要返回挂单时扣得善心币，已善金币的形式返回*/
                    $insertincome2['id']          = $redis->incr("sxh_user_income:id"); ;
                    $insertincome2['type']        = 3;
                    $insertincome2['income']      = $com_info['need_currency']*100;
                    $insertincome2['info']        = '完成挂单返回善金币';
                    $update_user_acc = $this->dosql("update ".$table_user." set invented_currency = invented_currency + ".$insertincome2['income']." where user_id = ".$v['user_id']);
                    if(!$update_user_acc)return false;
                    $insert1 = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome2);
                    if(!$insert1) return false;
                }
            }else if($v['level'] == 1 || $v['level'] == 2){/*企业管理钱包*/
                $update_user = $this->doSql("update ".$table_user." set  company_manage_wallet = company_manage_wallet + ".$v['rebate_money']."  where user_id = ".$v['user_id']);
                if(!$update_user)return false;
                $insertincome2['id']          = $redis->incr("sxh_user_income:id");
                $insertincome2['type']        = 13;
                $insertincome2['income']      = $v['rebate_money'];
                $insertincome2['earnings']    = $v['rebate_money'];
                $insertincome2['info']        = '返利企业管理钱包';
                $insert2 = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome2);
                if(!$insert2)return false;
            }else if($v['level'] == 3 ){/*企业管理钱包，善金币*/
                $money = $v['rebate_money']/2;
                $update_user = $this->dosql("update ".$table_user." set  company_manage_wallet = company_manage_wallet + ".$money." ,invented_currency = invented_currency + ".$money ." where user_id = ".$v['user_id']);
                if(!$update_user)return false;
                $insertincome2['id']          = $redis->incr("sxh_user_income:id");
                $insertincome2['type']        = 13;
                $insertincome2['income']      = $money;
                $insertincome2['earnings']    = $money;
                $insertincome2['info']        = '返利企业管理钱包';
                $insert2 = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome2);
                if(!$insert2)return false;
                $insertincome2['id']          = $redis->incr("sxh_user_income:id");
                $insertincome2['type']        = 3;
                $insertincome2['info']        = '返利善金币';
                $insert1 = $this->insert('sxh_user_income_'.date("Y").'_'.ceil(date("m")/3),$insertincome2);
                if($insert1<=0)  return false;
            }
        }
        return true;
    }
}
