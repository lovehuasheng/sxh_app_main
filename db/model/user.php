<?php

/*
 * 用户基本信息查询
 */

class user extends MMysql
{
    /*根据user_id，查询user表中的一些基本信息*/
    public function getUser($userid,$field = 'username,password'){
        $table = 'sxh_user_'.ceil(intval($userid)/1000000);
        $sql = 'select '.$field.' from '.$table.' where id = '.$userid.' limit 1';
        $return = $this->doSql($sql);
        return $return;
    }
    /*根据user_id，查询user_relation表中的一些基本信息*/
    public function getUserRelation($username){
        $table = 'sxh_user_relation';
        $sql = "select user_id from ".$table." where id = '".$username."' limit 1";
        $return = $this->doSql($sql);
        return $return;
    }
    /*根据用户ID，提交的社区ID，获取用户挂单的一些基本信息*/
    public function getUserDetail($userid,$cid){
        $table_user         = 'sxh_user_'.ceil($userid/1000000);   /*用户所在的表*/
        $table_account      = 'sxh_user_account_'.ceil($userid/1000000); /*用户金额所在的表*/
        $table_user_info    = 'sxh_user_info_'.ceil($userid/1000000);   /*用户所在的表*/
        $sql = "select a.*,b.gc,c.*,d.bid,e.real_name,e.phone from "
                    ." (select id,username,special,status,verify,is_poor,secondary_password,password,security,".$cid." as caid from  ".$table_user." where id = ".$userid." limit 1)a "
                ." join "
                   ." (select  name as real_name,user_id ,phone from  ".$table_user_info." where user_id = ".$userid." limit 1)e on a.id = e.user_id "
                ." join "
                    ." (select manage_wallet,guadan_currency as gc ,user_id from ".$table_account." where user_id =".$userid." limit 1)b on a.id = b.user_id "
                ." left join "
                    ."(select id as cid,low_sum as ls,top_sum as ts,name,multiple,need_currency as nc from sxh_user_community where id = ".$cid." limit 1)c on a.caid = c.cid "
                ." left join "
                    ." (select id as bid,user_id from sxh_user_blacklist where user_id = ".$userid." limit 1)d on a.id = d.user_id ";
        $return = $this->doSql($sql);
        if(count($return) == 0){
            return [];
        }else{
            return $return['0'];
        }
    }
    /*是否有未完成的提供资助*/
    public function  getLastProvide($userid){
        $table      = 'sxh_user_provide_'.date("Y").'_'.ceil(date("m")/3);/*当前表*/
        $last_table = 'sxh_user_provide_'.((ceil(date("m")/3)-1)?(date("Y").'_'.(ceil(date("m")/3)-1)):(date("Y")-1).'_4');
        $sql = "select id from ".$table." where status in (0,1) and user_id = ".$userid." and flag = 0 limit 1 "
                . " union "
                . " select id from ".$last_table." where status in (0,1) and user_id = ".$userid." and flag = 0 limit 1 ";
        $provide = $this->doSql($sql);
        if(count($provide)>0 && $provide['0']['id']>0){
            return  1;
        }
    }
    /*挂单扣除的善心币是否翻倍*/
    public function  getGccount($userid){
        /*有在排队的接受资助则再次挂单消耗的善心币不翻倍*/
        $table_accept_now  = 'sxh_user_accepthelp_'.date("Y").'_'.ceil(date("m")/3);
        $table_accept_last = 'sxh_user_accepthelp_'.((ceil(date("m")/3)-1)?(date("Y").'_'.(ceil(date("m")/3)-1)):(date("Y")-1).'_4');
        $sql = 'select id from '.$table_accept_now .' where user_id = '.$userid.' and status in(0,1,2) and type_id = 1 and flag = 0 limit 1 '
                . ' union '
                . ' select id from '.$table_accept_last .' where user_id = '.$userid.' and status in(0,1,2) and type_id = 1 and flag = 0 limit 1  ';
        $user_accept = $this->doSql($sql);
        if(count($user_accept)>0 && $user_accept['0']['id']>0){
            return 1;     /*有未完成的挂单 不翻倍*/
        }
        /*从redis中获取最后挂单或最后接受资助的时间*/
        
        $redis = RedisLib::get_instance();
        //$redis = \org\RedisLib::get_instance(); /*撤销订单时挂单次数减一*/
        $num1 = $redis->hget('sxh_userinfo:id:'.$userid,"provide_match_time");/*最后一笔完成匹配打款的时间，这个不用redis，数据库目前不好查询*/
        $num2 = $redis->hget('sxh_userinfo:id:'.$userid,"accept_match_time");/*最后一笔完成匹配接款的时间，这个不用redis，数据库目前不好查询*/
        $max1 = max($num1,$num2);
        if($max1>0 &&($max1+72*3600<time())){
            return 0;   /*72小时以内没有操作 翻倍*/
        }
        return 1;  /*或者新手不翻倍*/  
    }      
    /*用户上次登录的信息*/
    public function getLastLoginData($user_id){
        $sql = "select * from sxh_user_login_verify where user_id = ".$user_id;
        return $this->doSql($sql);
    }
    public function insertLogin($table,$arr){
        return $this->insert($table,$arr);
    }
    /*更新用户登录*/
    public function updateLogin($arr){
        $sql = "update sxh_user_login_verify set last_login_ip = ".$arr['last_login_ip'].",last_login_time = ".$arr['last_login_time'].",login_type = ".$arr['login_type'] 
                . ",phone_code = '".$arr['phone_code']."',login_err_num = ".$arr['login_err_num'].",login_need_code = ".$arr['login_need_code']." where user_id = ".$arr['user_id'];
        return $this->doSql($sql);
    }
}

