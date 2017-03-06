<?php
/*
name:接受资助列表 调用名 accepthelp.accept_list
desc:接受资助列表 其中列表类型 1表示未匹配 2已匹配
config:type|int||列表类型  page|int|1|页码  per_page_num|int|10|每页数量  queter|string||季度
*/
if( !in_array($params['type'],array(1,2,3))){
    return returnAction(1,'类型不在范围内');
}
if(!empty($params['per_page_num']) && !preg_match('/^[0-9]+$/', $params['per_page_num'])){
    return returnAction(1,'页数信息有误');
}
if(!preg_match('/^[0-9]$/', $params['page'])){
    return returnAction(1,'页码信息有误');
}
$params['user_id'] = config('user_id');
$return = _service('accept_list',$params);
return  $return ;