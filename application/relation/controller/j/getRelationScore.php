<?php
require_once config('service.lib')."/opensearch/entrance.php";
$uid=session("uid");
$client = new CloudsearchClient(config("opensearch.access_key"),config("opensearch.secret"),array('host'=>config("opensearch.host")),config("opensearch.key_type"));
$data1=new stdClass();
$data1->label="五代以内";
$data2=new stdClass();
$data2->label="无限代内";
//求五代以内总数
$level=session("plevel");
$end=$level+5;
$r=getTotal($client,$uid,["plevel >'". $level  ."'","plevel <='". $end  ."'"]);
if(!$r){
	$data1->total="数据错误";
}else{
	$data1->total=$r;
}

//五代以内激活人数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","plevel <='". $end  ."'","status='1'"]);
if(!$r){
	$data1->status="数据错误";
}else{
	$data1->status=$r;
}
//五代以内A轮个数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","plevel <='". $end  ."'","classification='2'"]);
if(!$r){
	$data1->a="数据错误";
}else{
	$data1->a=$r;
}
//五代以内B轮个数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","plevel <='". $end  ."'","classification='3'"]);
if(!$r){
	$data1->b="数据错误";
}else{
	$data1->b=$r;
}
//五代以内功德主轮个数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","plevel <='". $end  ."'","classification='1'"]);
if(!$r){
	$data1->c="数据错误";
}else{
	$data1->c=$r;
}
//求无限代总数
$level=session("plevel");
$end=$level+5;
$r=getTotal($client,$uid,["plevel >'". $level  ."'"]);
if(!$r){
	$data2->total="数据错误";
}else{
	$data2->total=$r;
}
//无限代激活总数
$level=session("plevel");
$end=$level+5;
$r=getTotal($client,$uid,["plevel >'". $level  ."'","status='1'"]);
if(!$r){
	$data2->status="数据错误";
}else{
	$data2->status=$r;
}
//无限代A轮个数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","classification='2'"]);
if(!$r){
	$data2->a="数据错误";
}else{
	$data2->a=$r;
}
//无限代B轮个数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","classification='3'"]);
if(!$r){
	$data2->b="数据错误";
}else{
	$data2->b=$r;
}
//无限代功德主轮个数
$r=getTotal($client,$uid,["plevel >'". $level  ."'","classification='1'"]);
if(!$r){
	$data2->c="数据错误";
}else{
	$data2->c=$r;
}
return [$data1,$data2];
function getTotal($client,$uid,$filters){	
	
	$search_obj = new CloudsearchSearch($client);
	$search_obj->addIndex("sxh_user");
	$search_obj->setFormat("json");
	$search_obj->setQueryString("full_url:'".$uid."'");
	foreach($filters as $v){
		$search_obj->addFilter($v);
	}
	//$search_obj->addFilter("plevel >'". $level  ."'"); 
	//$search_obj->addFilter("plevel <='". $end  ."'");
	$json=$search_obj->search();
	$_r=json_decode($json);
	if($_r->status!="OK"){
		return false;
	}	
	return $_r->result->total;
}