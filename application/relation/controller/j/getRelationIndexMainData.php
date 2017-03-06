<?php
require_once config('service.lib')."/opensearch/entrance.php";
$scroll_id=input("sid");
$re=new stdClass();//返回结果
$client = new CloudsearchClient(config("opensearch.access_key"),config("opensearch.secret"),array('host'=>config("opensearch.host")),config("opensearch.key_type"));

$search_obj = new CloudsearchSearch($client);
$search_obj->setScroll("1m");
$search_obj->setScrollId($scroll_id);
$json = $search_obj->scroll();
$r=json_decode($json);
if($r->status!="OK"){
	$_re=array("items"=>array(),"sid"=>"0");
	return json_decode(json_encode($_re));
}else{
	if(count($r->result->items)==0){
		$_re=array("items"=>array(),"sid"=>"0");
		return json_decode(json_encode($_re));
	}
	$_re=array("items"=>$r->result->items,"sid"=>$r->result->scroll_id,"total"=>$r->result->total);
	return json_decode(json_encode($_re));
}
