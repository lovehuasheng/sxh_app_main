<?php
/*
 * 匹配数据相关
 */
class community extends MMysql
{
    /*获取订单信息*/
    public function getCommunityInfo ($id,$field ='*') {
        $sql   = "select ".$field." from sxh_user_community where id = ".$id." limit 1";
        return $this->doSql($sql);
    }
}
