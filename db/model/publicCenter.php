<?php

/*
 * 用户基本信息查询
 */
class publicCenter extends MMysql
{
     /**
     * 提供资助列表
     * @param type $map
     * @param type $page
     * @param type $r
     * @param type $field
     * @param type $order
     * @return type
     * @Author hhs
     */
    public function getListByStatus($map, $page = 1, $r = 20, $field = '*', $order = 'id desc') {
        $table = getTable(time());
        if (isset($map['status']) && $map['status'] == 1) {
            //已匹配
            $sql = "select $field from sxh_user_provide_".$table[0]." where user_id=".$map['user_id']." and flag=0 and status in (1,2) order by $order";
            $list1 = $this->doSql($sql);
            if(!$list1 || empty($list1)){
                $list1 = array();
            }
            $sql = "select $field from sxh_user_provide_".$table[1]." where user_id=".$map['user_id']." and flag=0 and status in (1,2) order by $order";
            $list2 = $this->doSql($sql);
            if(!$list2 || empty($list2)){
                $list2 = array();
            }
            $list_res = array_merge($list1,$list2);
            $list = $this->checkMoneyEq($list_res);
        } else if(isset($map['status']) && $map['status'] == 3) {
            $flag_again = 0;
            $arr_queter = array('2016_1','2016_2','2016_3','2016_4','2017_1','2017_2','2017_3','2017_4','2018_1','2018_2','2018_3','2018_4');
            if($page==1 || !in_array($map['queter'],$arr_queter)){
                $sql = "select $field from sxh_user_provide_".$table[0]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                $list1 = $this->doSql($sql);
                if(!$list1 || empty($list1)){
                    $list1 = array();
                }
                $sql = "select $field from sxh_user_provide_".$table[1]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                $list2 = $this->doSql($sql);
                if(!$list2 || empty($list2)){
                    $list2 = array();
                }
                $list = array_merge($list1,$list2);
                $now_table = $table[1];
            }else{
//                $now_table = cache('sxh_current_table'.$map['user_id']);
                $now_table = $map['queter'];
                $sql = "select $field from sxh_user_provide_".$now_table." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                $list = $this->doSql($sql);
                if(!$list){
                    $now_table = getQueterTabel($now_table);
                    if($now_table){
                        $sql = "select $field from sxh_user_provide_".$now_table." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                        $list = $this->doSql($sql);
                        $flag_again = 1;
                    }
                }
            }
        }else{
            $sql = "select $field from sxh_user_provide_".$table[0]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
            $list1 = $this->doSql($sql);
            if(!$list1 || empty($list1)){
                $list1 = array();
            }
            $sql = "select $field from sxh_user_provide_".$table[1]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
            $list2 = $this->doSql($sql);
            if(!$list2 || empty($list2)){
                $list2 = array();
            }
            $list = array_merge($list1,$list2);
        }
        if (!empty($list)) {
            $b_list = array();
            $b_list['list']['data'] = $list;
            $b_list['current_page'] = $page;
            $queter = '';
            if($map['status'] == 3){
                $res = getQueterTabel($now_table);
                if($res){
//                  cache('sxh_current_table'.$map['user_id'],$res);
                    $queter = $res;
                    $b_list['current_page'] += 1;
                    if($flag_again){
                        $b_list['current_page'] += 1;
                    }
                }else {
                    $b_list['current_page']  = 0;
                }
                $b_list['total'] = 200;
                $b_list['per_page'] = $r;
            }else{
                $b_list['total'] = count($list);
                $b_list['current_page']  = 0;
                $b_list['per_page'] = $b_list['total']>$r ? $b_list['total'] : $r;
            }
            $b_list['queter'] = $queter;
            return $b_list;
        }
        return null;
    }
    /*
     * 查询匹配表，查看已经审核的匹配金额是否等于提供资助金额，如果不等，提示部分匹配
     */
    public function checkMoneyEq($list){
        if(count($list)>0){
            $table = getTable(time(),1);
            foreach($list as $k => $v){
                $sum1 = 0;
                $sum2 = 0;
                if($v['money']==$v['used']){
                    if($table[1]){
                        $sql = 'select sum(other_money) as total_money from sxh_user_matchhelp_'.$table[1].' where flag=0 and status!=0 other_id='.$v['id'];
                        $list2 = $this->doSql($sql);
                        $sum2 = $list2[0]['total_money'];
                    }
                    if($sum2 < $v['money']){
                        $sql = 'select sum(other_money) as total_money from sxh_user_matchhelp_'.$table[0].' where flag=0 and status!=0 other_id='.$v['id'];
                        $list1 = $this->doSql($sql);
                        $sum1 = $list1[0]['total_money'];
                    }
                    $list[$k]['used'] = $sum1+$sum2;
                }
            }
        }
        return $list;
    }
    /**
     * 获取分页数据
     * @param type $map
     * @param type $field
     * @return type
     * 2016-10-17改
     */
    public function getAcceptListByStatus($map, $page = 1, $r = 20, $field = '*', $order = 'id desc') {
        $table = getTable(time());
        if (isset($map['status']) && $map['status'] == 1) {
            $sql = "select $field from sxh_user_accepthelp_".$table[0]." where user_id=".$map['user_id']." and flag=0 and status in (1,2) order by $order";
            $list1 = $this->doSql($sql);
            if(!$list1 || empty($list1)){
                $list1 = array();
            }
            $sql = "select $field from sxh_user_accepthelp_".$table[1]." where user_id=".$map['user_id']." and flag=0 and status in (1,2) order by $order";
            $list2 = $this->doSql($sql);
            if(!$list2 || empty($list2)){
                $list2 = array();
            }
            $list_res = array_merge($list1,$list2);
            $list = $this->checkMoneyEq($list_res);
        } else if(isset($map['status']) && $map['status'] == 3) {
            $flag_again = 0;
            $arr_queter = array('2016_1','2016_2','2016_3','2016_4','2017_1','2017_2','2017_3','2017_4','2018_1','2018_2','2018_3','2018_4');
            if($page==1 || !in_array($map['queter'],$arr_queter)){
                $sql = "select $field from sxh_user_accepthelp_".$table[0]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                $list1 = $this->doSql($sql);
                if(!$list1 || empty($list1)){
                    $list1 = array();
                }
                $sql = "select $field from sxh_user_accepthelp_".$table[1]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                $list2 = $this->doSql($sql);
                if(!$list2 || empty($list2)){
                    $list2 = array();
                }
                $list = array_merge($list1,$list2);
                $now_table = $table[1];
            }else{
                $now_table = $map['queter'];
                $sql = "select $field from sxh_user_accepthelp_".$now_table." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                $list = $this->doSql($sql);
                if(!$list){
                    $now_table = getQueterTabel($now_table);
                    if($now_table){
                        $sql = "select $field from sxh_user_accepthelp_".$now_table." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
                        $list = $this->doSql($sql);
                        $flag_again = 1;
                    }
                }
            }
               
        }else{
            $sql = "select $field from sxh_user_accepthelp_".$table[0]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
            $list1 = $this->doSql($sql);
            if(!$list1 || empty($list1)){
                $list1 = array();
            }
            $sql = "select $field from sxh_user_accepthelp_".$table[1]." where user_id=".$map['user_id']." and flag=0 and status=".$map['status']." order by $order";
            $list2 = $this->doSql($sql);
            if(!$list2 || empty($list2)){
                $list2 = array();
            }
            $list = array_merge($list1,$list2);
        }
        if (!empty($list)) {
            $b_list = array();
            $b_list['list']['data'] = $list;
            $b_list['current_page'] = $page;
            $queter = '';
            if($map['status'] == 3){
                $res = getQueterTabel($now_table);
                if($res){
                    $queter = $res;
                    $b_list['current_page'] += 1;
                    if($flag_again){
                        $b_list['current_page'] += 1;
                    }
                }else {
                    $b_list['current_page'] = 0;
                }
                $b_list['total'] = 10;
                $b_list['per_page'] = $r;
            }else{
                $b_list['total'] = count($list);
                $b_list['current_page']  = 0;
                $b_list['per_page'] = $b_list['total']>$r ? $b_list['total'] : $r;
            }
            $b_list['queter'] = $queter;
            return $b_list;
        }
        return null;
    }
    
     /**
     * 获得匹配详情列表
     * @param type $other_id
     * @param type $field
     * @return boolean
     * @Author hhs
     * @time  2016-10-04
     */
    public function getMatchingListByProvideID($map, $field = '*') {
        //查找表后缀，即属于第几季度的表
        $table = getTable($map['create_time'],1);
        if($table[1]){
            $sql = "select $field from sxh_user_matchhelp_".$table[1]." where other_id=".$map['other_id']." and flag=0 and status != 0 order by id desc";
            $list1 = $this->doSql($sql);
            if(!$list1 || empty($list1)){
                $list1 = array();
            }
            $sql = "select $field from sxh_user_matchhelp_".$table[0]." where other_id=".$map['other_id']." and flag=0 and status != 0 order by id desc";
            $list2 = $this->doSql($sql);
            if(!$list2 || empty($list2)){
                $list2 = array();
            }
            $list = array_merge($list1,$list2);
        }else{
            $sql = "select $field from sxh_user_matchhelp_".$table[0]." where other_id=".$map['other_id']." and flag=0 and status != 0  order by id desc";
            $list = $this->doSql($sql);
        }
            
        return $list;
    }
    
        public function getMatchingListByAcceptID($map, $field = '*') {
        //查找表后缀，即属于第几季度的表,然后再向上取一个表，如果没有则为0
        $table = getTable($map['create_time'],1);
        if($table[1]){
            $sql = "select $field from sxh_user_matchhelp_".$table[1]." where pid=".$map['pid']." and flag=0 and status != 0 order by id desc";
            $list1 = $this->doSql($sql);
            if(!$list1 || empty($list1)){
                $list1 = array();
            }
            $sql = "select $field from sxh_user_matchhelp_".$table[0]." where pid=".$map['pid']." and flag=0 and status != 0 order by id desc";
            $list2 = $this->doSql($sql);
            if(!$list2 || empty($list2)){
                $list2 = array();
            }
            $list = array_merge($list1,$list2);
        }else{
            $sql = "select $field from sxh_user_matchhelp_".$table[0]." where pid=".$map['pid']." and flag=0 and status != 0 order by id desc";
            $list = $this->doSql($sql);
        }

        return $list;
    }
    //根据ID获取outgo/income/provide/accept/matchhelp
    public function getTableDesc($id,$field="*",$table){
        $sql = "select $field from $table where id=$id";
        return $this->doSql($sql);
    }
}

