<?php
class mysqlDB{
    private $host="192.168.10.165";/*主机*/
    private $db="sxhsxhlocal";     /*数据库名称*/
    private $name="root";          /*数据库的username*/
    private $pass="f3Wm8Dt1";      /*数据库的password*/
    private $ut="utf-8";           /*编码形式*/
    private $querysql;
    private $result = [];
    private $resultarray=[];
    private $rows;
    private static $link_db;
     //构造函数
    function  __construct(){        
        self::$link_db=false;
    }
    private  function connect(){
        $link=mysqli_connect($this->host,$this->name,$this->pass,$this->db) or die ($this->error());
        mysqli_query($link,"SET NAMES $this->ut");
        self::$link_db = $link;
    }
    public function begin(){
        if(!self::$link_db) $this->connect();
        mysqli_query(self::$link_db,"begin");
    }
    public function commit(){
        if(!self::$link_db) $this->connect();
        mysqli_query(self::$link_db,"commit");
    }
    public function rollback(){
        if(!self::$link_db) $this->connect();
        mysqli_query(self::$link_db,"rollback");
    }
    public function exec($sql){
        if(!self::$link_db) $this->connect();
        return mysqli_query(self::$link_db,$sql);
    }
    private function query(){
        if(!self::$link_db) $this->connect();
        return $this->result=mysqli_query(self::$link_db,$this->querysql);
    }
    private function get_num(){
        return $this->num=mysqli_num_rows($this->result);
    }
    public function get_result($sql){
        $this->querysql=$sql;
        $this->query();
        if($this->get_num()>0){
            //mysql_fetch_assoc()和 mysql_fetch_array(,MYSQL_ASSOC)从结果集中取得一行作为关联数组 没有则返回false
            while($this->rows=mysqli_fetch_array($this->result,MYSQLI_ASSOC)){
                //赋值 数组赋值 resultarray[]= 将影响的行数赋值给数组
                $this->resultarray[]=$this->rows;
            }
            mysqli_free_result($this->result);
        }
        return $this->resultarray;
    }
    public function insert($table,$arr){
      
    }

}