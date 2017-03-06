<?php

/*
 * 用户基本信息查询
 */
if(!isset($GLOBALS["define_class"]))$GLOBALS["define_class"]=[];
if(in_array("userinfo",$GLOBALS["define_class"])) return ;
$GLOBALS["define_class"][]="userinfo";
class userinfo extends MMysql
{
    //用于user/userinfo/account
    public function getUserInfo($id,$where,$field='*',$table_='sxh_user'){
        $table = $table_.'_'.ceil($id/1000000);
        return $this->where($where)->field($field)->select($table);
    }
    /*
     * 根据ID获取用户信息,用于user/userinfo/account
     */
    public function getUserOneInfo($id,$field,$table_='sxh_user'){
        $id = intval($id);
        if($table_ == 'sxh_user'){
            $w_field = 'id';
        }else{
            $w_field = 'user_id';
        }
        $table = $table_.'_'.ceil($id/1000000);
        $sql = "select $field from $table where $w_field=$id limit 1";
        return $this->doSql($sql);
    }
    /*
     * 插入数据表,用于user/userinfo/account
     */
    public function insertTable($id,$data,$table_='sxh_user'){
        $table = $table_.'_'.ceil($id/1000000);
        return $this->insert($table,$data);
    }
    //插入outgo、income表
    public function insertDesc($data,$table_='sxh_user_outgo'){
        $res = getTable(time());
        $table = $table_.'_'.$res[0];
        return $this->insert($table,$data);
    }
    //获取关系表的信息
    public function getUserRelation($id,$field){
        $id = intval($id);
        $sql = "select $field from sxh_user_relation where user_id=$id limit 1";
        return $this->doSql($sql);
    }
    public function getRelationInfo($where,$field,$limit=1){
        return $this->where($where)->field($field)->limit($limit)->select('sxh_user_relation');
    }
    //获取企业表的信息
    public function getCompanyInfo($id,$field){
        $id = intval($id);
        $sql = "select $field from sxh_company_info where company_id=$id limit 1";
        return $this->doSql($sql);
    }
     /*
     * 插入数据表
     */
    public function insertRelation($data){
        $table = 'sxh_user_relation';
        return $this->insert($table,$data);
    }
    /*
     * 更新数据,用于user/userinfo/account
     */
    public function updateUserInfo($id,$data,$table_='sxh_user'){
        if($table_ == 'sxh_user'){
            $condition['id'] = $id;
        }else{
            $condition['user_id'] = $id;
        }
        $table = $table_.'_'.ceil($id/1000000);
        return $this->where($condition)->update($table,$data);
    }
    /*
     * 更新数据,用于account
     */
    public function updateUserAccount($id,$data,$table_='sxh_user_account'){
        $table = $table_.'_'.ceil($id/1000000);
        $sql = "update $table set $data where user_id=$id";
        return $this->doSql($sql);
    }
    /** 查询本轮管理奖是否已经提取
     */
    public function checkManage($user_id) {
        $time = time();
        $table_r = getTable($time);
        $table = "sxh_user_provide_".$table_r[0];
        //一，查provide表
        $provide_where = "user_id=".$user_id." AND status=3 AND type_id=1";
        $pro_sql = "SELECT id,money,used,cid FROM ".$table." WHERE ".$provide_where." ORDER BY id DESC LIMIT 1";
        $pro_result = $this->doSql($pro_sql);
        //如果空，就查上季
        if(empty($pro_result)) {
            $table_2 = "sxh_user_provide_".$table_r[1];
            $pro_sql = "SELECT id,money,used,cid FROM ".$table_2." WHERE ".$provide_where." ORDER BY id DESC LIMIT 1";
            $pro_result = $this->doSql($pro_sql);
        }
        //如果空，就查上季
        if(empty($pro_result)) {
            $table_2 = "sxh_user_provide_".getNextTable($table_r[1]);
            $pro_sql = "SELECT id,money,used,cid FROM ".$table_2." WHERE ".$provide_where." ORDER BY id DESC LIMIT 1";
            $pro_result = $this->doSql($pro_sql);
        }
        
        //一，如果提供表有数据，就更新 Redis ，如果没有就不管
        if(!empty($pro_result)) {
            $provide_id = $pro_result[0]['id'];
            $pro_money= $pro_result[0]['money'];
            
            //二，查income表
            //$table = getTable($time);
            $out_table = "sxh_user_outgo_".$table_r[0];
            $outgo_where = "user_id=".$user_id." AND pid=".$provide_id." AND info='提取管理奖'";
            $now_sql = "SELECT id FROM ".$out_table." WHERE ".$outgo_where." LIMIT 1";
            $outgo_result = $this->doSql($now_sql);
            
            //如果空，就查上季
            if(empty($outgo_result)) {
                $out_table_2 = "sxh_user_outgo_".$table_r[1];
                $last_sql = "SELECT id FROM ".$out_table_2." WHERE ".$outgo_where." LIMIT 1";
                $outgo_result = $this->doSql($last_sql);
            }
            //二，如果outgo有数据
            if(!empty($outgo_result)) {
                //1，本轮已提取过管理奖
                return [
                    'code'=>1,
                    'provide_id' => 0    ,
                    'provide_money' => 0,
                    ];
            } else {
                //2，本轮没有提取过管理奖
                return [
                    'code'=>0,
                    'provide_id' => $provide_id    ,
                    'provide_money' => $pro_money,
                    ];
            }
            
        } else {
            return [
                'code'=>2,
                'provide_id' => 0    ,
                'provide_money' => 0,
                ];
        }
    }
     //如果用户信息写入redis有误，则读取relation表
      public function setUserRedis($username) {
        $username = strtolower($username);
        $redis = orgRedisLib::get_instance();
        $relation_result = $this->getRelationInfo(['username'=>$username] , 'user_id');
        if(!empty($relation_result)) {

            $member_id = $relation_result[0]['user_id'];
            $user_info_result = $this->getUserInfo( $member_id,array('user_id'=>$member_id) , "user_id,phone",'sxh_user_info');
            if(empty($user_info_result)) {
                return false;
            }
            $phone = $user_info_result[0]['phone'];
            $rr = $redis->set('sxh_user:username:'.$username.':id' , $member_id);
            $dd = $redis->set('sxh_user:id:'.$member_id.':username' , $username);
            $kk = $redis->sadd('sxh_user:username' , $username);
            $kk = $redis->sadd('sxh_user_info:phone' , $phone);
            $redis->hsetUserinfoByID($member_id , "phone" , $phone);
            return $member_id;
        } else {
            return false;
        }
    }
}