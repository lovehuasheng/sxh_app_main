<?php
/*
 * 匹配数据相关
 */
class matchhelp extends MMysql
{
    /*获取订单信息*/
    public function getMatchHelpInfo ($id,$create_time,$field ='*') {
        $table = 'sxh_user_matchhelp_'.date("Y",$create_time).'_'.ceil(date("m",$create_time)/3);
        $sql   = "select ".$field." from ".$table." where id = ".$id." and flag = 0 limit 1";
        return $this->doSql($sql);
    }
    /*订单延时*/
    public function set_delayed_time($id,$create_time,$time){
        $table = 'sxh_user_matchhelp_'.date("Y",$create_time).'_'.ceil(date("m",$create_time)/3);
        $sql   = 'update '.$table.' set  expiration_create_time =' .$time.', delayed_time_status = 1 where id = ' .$id;
        return $this->doSql($sql);
    }
    /*更新匹配订单的完成状态*/
    public function updateMatchStatus($id,$create_time,$value = 3){
        $table = 'sxh_user_matchhelp_'.date("Y",$create_time).'_'.ceil(date("m",$create_time)/3);
        $sql   = 'update '.$table.' set  status ='.$value.'  where id = ' .$id;
        return $this->doSql($sql);
    }
    
}