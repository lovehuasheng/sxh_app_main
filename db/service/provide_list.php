<?php
require_once WANG_MS_PATH."model/publicCenter.php";
$provide_list = function($data){
    $redis = RedisLib::get_instance();
    $model = new publicCenter();
    $arr = array(0,0,1,3);
    $map = array();
    $type = isset($data['type']) ? $data['type'] : 1;
    $map['status'] = $arr[$type];
    $map['user_id'] = $data['user_id'];
    $map['queter'] = $data['queter'] ? $data['queter'] : '';
    $page = isset($data['page']) ? $data['page']:1;
    //如果页面没传每页条数，就去配置的条数
    $total = isset($data['per_page_num'])?$data['per_page_num']:config('app_list_rows');
    $result_list = $model->getListByStatus($map, intval($page), intval($total),'id,type_id,cid,cname,money,used,user_id,status,create_time');
    if(empty($result_list)) {
        $result_list['list']['data'] = [];
        $result_list['total'] = 0;
        $result_list['per_page'] = 10;
        $result_list['current_page'] = 0;
    }

    return returnErr(0,'请求成功',$result_list);
};
return $provide_list($arr);

