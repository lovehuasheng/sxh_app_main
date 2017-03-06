<?php
require_once config('service.lib')."/opensearch/entrance.php";
$plevel=intval(input("post.level"));
$re=new stdClass();//返回结果
$uid=session("uid");
$level=intval(session("plevel"))+intval($plevel);
$client = new CloudsearchClient(config("opensearch.access_key"),config("opensearch.secret"),array('host'=>config("opensearch.host")),config("opensearch.key_type"));

$search_obj = new CloudsearchSearch($client);
// 指定一个应用用于搜索
$search_obj->addIndex("sxh_user");
// 指定搜索关键词

//设定返回字段
$fields=array("id","username","name","full_url","create_time","is_company","classification","province","city","status","verify","avatar","parent_id","plevel","is_black","is_company");
//设置获取数量
$search_obj->setStartHit("0");
$search_obj->setHits(200);
$search_obj->addFetchFields($fields);
// 指定返回的搜索结果的格式为json
$search_obj->setFormat("json");
// 执行搜索，获取搜索结果
$r=getData($search_obj,$uid,$level);
if(!$r){
	$_re=array("__system__"=>1,"errCode"=>100,"errMsg"=>"系统出错，无法得到数据","result"=>"");
	return json_decode(json_encode($_re));
}
$re=array("sid"=>$r);

return json_decode(json_encode($re));

function getData($search_obj,$uid,$level){
	$t=array();
	$search_obj->setQueryString("full_url:'".$uid."'");
	
	$search_obj->addFilter("plevel ='". $level  ."'"); 
	$search_obj->setScroll("1m");
	$json=$search_obj->scroll();
	$r=json_decode($json);
	if($r->status!="OK"){
		return false;
	}else{
		$scroll_id=$r->result->scroll_id;
		return $scroll_id;
	}	
}