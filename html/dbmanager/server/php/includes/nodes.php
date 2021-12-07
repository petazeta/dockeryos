<?php
class Db_gateway {
  function __construct($db_system) {
    $this->db_system=$db_system;
    $this->dblink=null;
  }
  function connect($db_host, $db_username, $db_userpwd, $db_databasename, $new=false){
    if (!$this->dblink || $new) {
      try {
        $this->dblink = new PDO($this->db_system . ":host=$db_host;dbname=$db_databasename", $db_username, $db_userpwd);
        // set the PDO error mode to exception
        $this->dblink->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
      }
    }
    return $this->dblink;
  }
  function get_dblink($new=false){
    if (!$new && $this->dblink) return $this->dblink;
    $this->dblink=$this->connect(DB_HOST, DB_USERNAME, DB_USERPWD, DB_DATABASENAME);
    return $this->dblink;
  }
  function is_error(){
    if ($this->dblink->connect_errno) {
      return $this->dblink->connect_error;
    }
    return false;
  }
  function query($sql) {
    $result=$this->get_dblink()->query($sql, PDO::FETCH_ASSOC);
    if (!$result) throw new Exception($this->get_dblink()->error);
    return $result;
  }
  function multi_query($sql) {
    $this->get_dblink()->beginTransaction();
    $this->get_dblink()->exec($sql);
    $this->get_dblink()->commit();
    return true;
  }
  function escape_string($value) {
    $quoted = $this->get_dblink()->quote($value);
    return substr($quoted, 1, -1);
  }
  function selectQuery($sql){
    return $this->query($sql)->fetchAll();
  }
  function insertQuery($sql) {
    $this->query($sql);
    return $this->get_dblink()->lastInsertId();
  }
  function updateQuery($sql) {
    $result=$this->query($sql);
    return $result->rowCount();
  }
  function get_foreign_keys($table_name) {
    //return foreigntablename foreigncolumnname
    if ($this->db_system=="mysql") {
      $sql = <<<END
SELECT r.REFERENCED_TABLE_NAME as parenttablename, r.COLUMN_NAME as name FROM
INFORMATION_SCHEMA.KEY_COLUMN_USAGE r WHERE
TABLE_SCHEMA= SCHEMA()
AND r.REFERENCED_TABLE_NAME IS NOT NULL
AND r.TABLE_NAME=
END;
    }
    else if ($this->db_system=="pgsql"){
      $sql = <<<END
SELECT
  ccu.table_name AS parenttablename,
kcu.column_name AS name
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu 
  ON tc.constraint_name = kcu.constraint_name 
JOIN information_schema.constraint_column_usage AS ccu 
  ON ccu.constraint_name = tc.constraint_name 
WHERE constraint_type = 'FOREIGN KEY'
AND tc.table_name =
END;
    }
    $sql .="'{$table_name}'";
    return $this->selectQuery($sql);
  }
  function get_tables_has_foreigns($table_name){
    //return childtablename, parenttablename, name
    if ($this->db_system=="mysql") {
      $sql = <<<END
SELECT r.TABLE_NAME as childtablename, r.REFERENCED_TABLE_NAME as parenttablename, r.TABLE_NAME as name FROM
INFORMATION_SCHEMA.KEY_COLUMN_USAGE r
WHERE
TABLE_SCHEMA= SCHEMA()
AND r.REFERENCED_TABLE_NAME=
END;
    }
    else if ($this->db_system=="pgsql") {
      $sql = <<<END
SELECT
  ccu.table_name AS parenttablename, tc.table_name as childtablename, tc.table_name as name 
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu 
  ON tc.constraint_name = kcu.constraint_name 
JOIN information_schema.constraint_column_usage AS ccu 
  ON ccu.constraint_name = tc.constraint_name 
WHERE constraint_type = 'FOREIGN KEY'
AND ccu.table_name =
END;
    }
    $sql .="'{$table_name}'";
    return $this->selectQuery($sql);
  }
  function get_foreign_tables($table_name){
    //return childtablename, parenttablename, name
    if ($this->db_system=="mysql") {
      $sql = <<<END
SELECT r.TABLE_NAME as childtablename, r.REFERENCED_TABLE_NAME as parenttablename, r.TABLE_NAME as name FROM
INFORMATION_SCHEMA.KEY_COLUMN_USAGE r
WHERE
TABLE_SCHEMA= SCHEMA()
AND REFERENCED_TABLE_NAME IS NOT NULL
AND r.TABLE_NAME=
END;
    }
    else if ($this->db_system=="pgsql") {
      $sql = <<<END
SELECT
  ccu.table_name AS parenttablename, tc.table_name as childtablename
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu 
  ON tc.constraint_name = kcu.constraint_name 
JOIN information_schema.constraint_column_usage AS ccu 
  ON ccu.constraint_name = tc.constraint_name 
WHERE constraint_type = 'FOREIGN KEY'
AND tc.table_name =
END;
    }
    $sql .="'{$table_name}'";
    return $this->selectQuery($sql);
  }
  function get_keys($table_name){
    if ($this->db_system=="mysql") {
      $sql='show columns from ' . $table_name;
    }
    else if ($this->db_system=="pgsql") {
      $sql = <<<END
SELECT
column_name as "Field", data_type as "Type", is_nullable as "Null", column_default as "Default", '' as "Extra"
FROM information_schema.columns
WHERE table_schema='public' 
AND table_name=
END;
      $sql .="'{$table_name}'";
    }
    $result = $this->selectQuery($sql);
    if ($this->db_system=="pgsql") {
      foreach ($result as $row) {
        if (preg_match('/nextval/', $row['Default'])) {
          $row['Default']='NULL';
          $row['Extra']='auto_increment';
        }
      }
    }
    return $result;
  }
  function get_tables(){
    if ($this->db_system=="mysql") {
      $sql="SHOW TABLES";
    }
    else if ($this->db_system=="pgsql") {
      $sql="SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'";
    }
    return $this->selectQuery($sql);
  }
  function insert_void($table_name){
    $sql = "INSERT INTO {$table_name}";
    if ($this->db_system=="mysql") {
      $sql .= ' VALUES ()';
    }
    else if ($this->db_system=="pgsql") {
      $sql .= ' DEFAULT VALUES';
    }
    return $this->insertQuery($sql);
  }
  function update_sibling_sort_order($tableName, $positioncolumnname, $foreigncolumnname, $this_id, $this_partner_id, $new_sort_order) {
    if ($this->db_system=="mysql") {
      $sql = 'UPDATE ' . $tableName . ' dt1 INNER JOIN ' . $tableName . ' dt2'
      . ' ON dt2.id=' . Node::get_db_gateway()->escape_string($this_id)
      . ' SET dt1.' . $positioncolumnname . '=dt2.' . $positioncolumnname
      . ' WHERE'
      . ' dt1.id !=' . Node::get_db_gateway()->escape_string($this_id)
      . ' AND dt1.' . $foreigncolumnname . '=' . Node::get_db_gateway()->escape_string($this_partner_id)
      . ' AND dt1.' . $positioncolumnname . '=' . Node::get_db_gateway()->escape_string($new_sort_order);
    }
    else if ($this->db_system=="pgsql") {
      $sql = 'UPDATE ' . $tableName . ' dt1'
      . ' SET ' . $positioncolumnname . '=dt2.' . $positioncolumnname
      . ' FROM ' . $tableName . ' dt2'
      . ' WHERE'
      . ' dt1.id !=' . Node::get_db_gateway()->escape_string($this_id)
      . ' AND dt1.' . $foreigncolumnname . '=' . Node::get_db_gateway()->escape_string($this_partner_id)
      . ' AND dt1.' . $positioncolumnname . '=' . Node::get_db_gateway()->escape_string($new_sort_order)
      . ' AND dt2.id=' . Node::get_db_gateway()->escape_string($this_id);
    }
    return $this->updateQuery($sql);
  }
  function delete_update_siblings_sort_order($tableName, $positioncolumnname, $foreigncolumnname, $this_id, $this_partner_id) {
    if ($this->db_system=="mysql") {
      $sql = 'UPDATE ' . $tableName . ' dt1 INNER JOIN ' . $tableName . ' dt2'
      . ' ON dt2.id=' . Node::get_db_gateway()->escape_string($this_id)
      . ' SET dt1.' . $positioncolumnname . '= dt1.' . $positioncolumnname . ' - 1'
      . ' WHERE'
      . ' dt1.' . $positioncolumnname . '=' . Node::get_db_gateway()->escape_string($this_partner_id)
      . ' AND dt1.' . $positioncolumnname . '> dt2.' . $positioncolumnname;
    }
    else if ($this->db_system=="pgsql") {
      $sql = 'UPDATE ' . $tableName . ' dt1'
      . ' SET ' . $positioncolumnname . '= dt1.' . $positioncolumnname . ' - 1'
      . ' FROM ' . $tableName . ' dt2'
      . ' WHERE'
      . ' dt2.id =' . Node::get_db_gateway()->escape_string($this_id)
      . ' AND dt1.' . $positioncolumnname . '=' . Node::get_db_gateway()->escape_string($this_partner_id)
      . ' AND dt1.' . $positioncolumnname . '> dt2.' . $positioncolumnname;
    }
    return $this->updateQuery($sql);
  }
}
      
class Node {
  public $props;
  function __construct() {
    $this->props=new stdClass();
  }
  static function detectGender($element){
    if (isset($element->parentNode)) return "NodeMale";
    else return "NodeFemale";
  }
  //This function is to quickly copy plain props from one object to another. Text numbers become numbers.
  //$source can be assoc array
  //copyKeys is optional array for coping just some
  static function copyCleanValues($sourceObj, $targetObj=null, $copyKeys=null){
    if ($targetObj===null) $targetObj=new stdClass;
    foreach ($sourceObj as $key => $value) {
      if (gettype($copyKeys)=='array' && !in_array($key, $copyKeys)) continue;
      if (gettype($value) == "string" && is_numeric($value)) $targetObj->$key=(float)$value;
      else $targetObj->$key=$value;
    }
    return $targetObj;
  }
  function copyProperties($origin, $someKeys=null) {
    Node::copyCleanValues($origin->props, $this->props, $someKeys);
  }
  function copyPropertiesFromArray($origin, $someKeys=null) {
    Node::copyCleanValues($origin, $this->props, $someKeys);
  }
  //Load entire node
  function load($source, $levelup=null, $leveldown=null, $thisProperties=null, $thisPropertiesUp=null, $thisPropertiesDown=null){
    if (gettype($source)=='string') $source=json_decode($source);
    if (gettype($thisProperties)=='string') $thisProperties=[$thisProperties];
    if (isset($source->props)) $this->copyProperties($source, $thisProperties);
    return $this;
  }
  static function checkrequirements(){
    $minimum='5.6';
    $currentVer=phpversion();
    if (version_compare($currentVer, $minimum)<0) {
      return "Your PHP version is: $currentVer. Min version is: $minimum";
    }
    return true;
  }
  //<--Database queries
  static function get_db_gateway($new=false){
    global $database_gateway;
    if (!$database_gateway || $new) {
      $database_gateway=new Db_gateway(DB_SYSTEM);
    }
    return $database_gateway;
  }
  static function checkdblink(){
    try {
      $db=new Db_gateway(DB_SYSTEM);
      $db->get_dblink();
      return true;
    }
    catch(Exception $e) { //It seems the connection error throws error exception
      return $e->getMessage();
    }
  }
  static function db_gettables() {
    $result=Node::get_db_gateway()->get_tables();
    $tables=[];
    foreach ($result as $row) {
      array_push($tables, $row[array_keys($row)[0]]);
    }
    return $tables;
  }
  static function db_initdb() {
    $myTables=Node::db_gettables();
    if (count($myTables)>0) {
      throw new Exception("Database tables already present. Import file manually");
    }
    if (!($sql = file_get_contents(DB_SQLPATH . DB_SYSTEM . 'db.sql'))) {
      throw new Exception("Imposible to read file sql");
    }
    if (DB_SYSTEM=='pgsql') {
      $sql .= file_get_contents(DB_SQLPATH . DB_SYSTEM . 'db.sql' . '.sync');
    }
    return Node::get_db_gateway()->multi_query($sql);
  }
}
class NodeFemale extends Node{
  public $partnerNode;
  public $children=[];
  public $childtablekeys=[];
  public $childtablekeysinfo=[];
  public $syschildtablekeys=[];
  public $syschildtablekeysinfo=[];

  function __construct($childtablename=null, $parenttablename=null){
    parent::__construct();
    if ($childtablename) $this->props->childtablename=$childtablename;
    if ($parenttablename) $this->props->parenttablename=$parenttablename;
  }

  function load($source, $levelup=null, $leveldown=null, $thisProperties=null, $thisPropertiesUp=null, $thisPropertiesDown=null) {
    parent::load($source);
    if (isset($source->childtablekeys)) {
      foreach ($source->childtablekeys as $key => $value) {
        $this->childtablekeys[$key]=$value;
      }
    }
    if (isset($source->childtablekeysinfo)) {
      foreach ($source->childtablekeysinfo as $key => $value) {
        $this->childtablekeysinfo[$key]=$value;
      }
    }
    if (isset($source->syschildtablekeys)) {
      foreach ($source->syschildtablekeys as $key => $value) {
        $this->syschildtablekeys[$key]=$value;
      }
    }
    if (isset($source->syschildtablekeysinfo)) {
      foreach ($source->syschildtablekeysinfo as $key => $value) {
        $this->syschildtablekeysinfo[$key]=$value;
      }
    }
    if ($levelup !== 0 && !($levelup < 0)) { //level null and undefined => untill the end
      $this->loadasc($source, $levelup, $thisPropertiesUp);
    }
    if ($leveldown !== 0 && !($leveldown < 0)) {
      $this->loaddesc($source, $leveldown, $thisPropertiesDown);
    }
  }
  function loaddesc($source, $level=null, $thisProperties=null) {
    $this->children=[];
    if (gettype($source)=='string') $source=json_decode($source);
    if ($level===0) return;
    if ($level) $level--;
    if (!isset($source->children) || !$source->children) return false;
    for ($i = 0; $i < count($source->children); $i++) {
      $this->children[$i]=new NodeMale();
      $this->children[$i]->parentNode=$this;
      $this->children[$i]->load($source->children[$i], 0, 0, $thisProperties);
      $this->children[$i]->loaddesc($source->children[$i], $level, $thisProperties);
    }
  }
  //It loads data from a json, if update is true only fields and relationship present at original will be updated
  function loadasc($source, $level=null, $thisProperties=null) {
    $this->partnerNode=null;
    if (gettype($source)=='string') $source=json_decode($source);
    if (!isset($source->partnerNode) || !$source->partnerNode) return false;
    if ($level===0) return;
    if ($level) $level--;
    $this->partnerNode=new NodeMale();
    $this->partnerNode->load($source->partnerNode, 0, 0, $thisProperties);
    $this->partnerNode->loadasc($source->partnerNode, $level, $thisProperties);
  }
  function cloneNode($levelup=null, $leveldown=null, $thisProperties=null, $thisPropertiesUp=null, $thisPropertiesDown=null) {
    $myClon=new NodeFemale();
    $myClon->load($this, $levelup, $leveldown, $thisProperties, $thisPropertiesUp, $thisPropertiesDown);
    return $myClon;
  }
  function cutUp(){
    $this->partnerNode=null;
  }
  function cutDown(){
    $this->children=[];
  }
  function getChild($obj=null) {
    if (!$obj) return $this->children[0];
    $keyname=array_keys($obj)[0];
    foreach ($this->children as $child) {
      if (isset($child->props->$keyname) && $child->props->$keyname==$obj->$keyname) return $child;
    }
    return false;
  }
  function addChild($child) {
    array_push($this->children,$child);
    $child->parentNode=$this;
    return $child;
  }
  //foreignkey relative to the parenttablename
  static function getSysKey($data, $type='foreignkey'){
    if (!is_array($data->syschildtablekeysinfo) || count($data->syschildtablekeysinfo)==0) {
      $sysinfo=NodeFemale::db_getchildtablekeys($data)->syschildtablekeysinfo;
    }
    else $sysinfo=$data->syschildtablekeysinfo;
    //Get foreign keys from the actual relatioship
    foreach ($sysinfo as $syskey) {
      if ($type=='primary' && $syskey->type==$type) {
        return $syskey->name;
      }
      if (isset($syskey->parenttablename) && $syskey->parenttablename==$data->props->parenttablename && $syskey->type==$type) {
        return $syskey->name;
      }
    }
  }
  function getMySysKey($type='foreignkey'){
    return NodeFemale::getSysKey($this, $type);
  }
  
  //Get foreignkeys from the rel and from extraParents related to the same child
  static function getRealForeignKeys($data, $extraParents=null){
    $myforeign=NodeFemale::getSysKey($data);
    if (!$extraParents) return [$myforeign];
    if (!is_array($extraParents)) $extraParents=[$extraParents];
    //We filter the extraParents foreign key cause it could be a generic request (load my tree with a language f.e.)
    $foreigns=[];
    foreach ($extraParents as $value) {
      $fkey= NodeFemale::getSysKey($value);
      if (in_array($fkey, $data->syschildtablekeys)) array_push($foreigns, $fkey);
    }
    array_unshift($foreigns, $myforeign);
    return $foreigns;
  }
  function getMyRealForeignKeys($extraParents=null){
    return NodeFemale::getRealForeignKeys($this, $extraParents);
  }
  //Get root male node (skipping the female)
  function getrootnode($tableNameTop=null) {
    if (!$this->partnerNode) return false;
    else return $this->partnerNode->getrootnode($tableNameTop);
  }
  function avoidrecursion(){
    if ($this->partnerNode) {
      $this->partnerNode->avoidrecursionup();
    }
    foreach ($this->children as $child) {
      $child->avoidrecursiondown();
    }
    return $this;
  }
  function avoidrecursiondown(){
    $this->partnerNode=null;
    foreach ($this->children as $child) {
      $child->avoidrecursiondown();
    }
  }
  function avoidrecursionup(){
    $this->children=[];
    if ($this->partnerNode) {
      $this->partnerNode->avoidrecursionup();
    }
  }
  //<-- Database queries
  //Accessorial function
  //returns an array of nodes from the request result object
  static function readQuery($elements){
    $children=[];
    foreach ($elements as $element) {
      $child=new NodeMale();
      $child->copyPropertiesFromArray($element);
      $children[]=$child;
    }
    return $children;
  }
  //foreignkey props removal EXPERIMENTAL
  static function removeSysProps($children, $parent, $type='foreignkey'){
    if (!is_array($parent->syschildtablekeysinfo) || count($parent->syschildtablekeysinfo)==0) {
      $table_keys=NodeFemale::db_getchildtablekeys($parent);
      $sys_keys=$table_keys->syschildtablekeys;
      $sys_info_keys=$table_keys->syschildtablekeysinfo;
    }
    else {
      $sys_keys=$parent->syschildtablekeys;
      $sys_info_keys=$parent->syschildtablekeysinfo;
    }
    foreach ($children as $child) {
      foreach ($child->props as $prop_name => $value) {
        if (($index=array_search($prop_name, $sys_keys))!==false) {
          if ($sys_info_keys[$index]->type==$type) {
            unset($child->props->$prop_name);
          }
        }
      }
    }
    return $children;
  }
  //<-- Load queries
  static function db_getchildtablekeys($data) {
    $element=new stdClass();
    $element->childtablekeys=[];
    $element->childtablekeysinfo=[];
    $element->syschildtablekeys=[];
    $element->syschildtablekeysinfo=[];
    //lets load systablekeys (referenced columns)
    $result = Node::get_db_gateway()->get_foreign_keys(constant($data->props->childtablename));
    foreach ($result as $row) {
      $syskey=new stdClass();
      $syskey->type='foreignkey';
      Node::copyCleanValues($row, $syskey);
      // to constant table names
      $syskey->parenttablename='TABLE_' . strtoupper($syskey->parenttablename);
      $element->syschildtablekeysinfo[]=$syskey;
      $element->syschildtablekeys[]=$syskey->name;
    }
    //Now the childtablekeys and positions skey
    $result = Node::get_db_gateway()->get_keys(constant($data->props->childtablename));
    foreach ($result as $row) {
      if (preg_match('/.+_position$/', $row['Field'])) { //positionsyskey
	$syskey=new stdClass();
	$syskey->name=$row['Field'];
	$syskey->type='sort_order';
	$refkey=preg_replace('/(.+)_position$/', '$1', $row['Field']);
	foreach($element->syschildtablekeysinfo as $keyinfo) {
	  if ($keyinfo->name==$refkey) {
	    $syskey->parenttablename=$keyinfo->parenttablename;
	    break;
	  }
	}
	$element->syschildtablekeysinfo[]=$syskey;
	$element->syschildtablekeys[]=$row['Field'];
	continue;
      }
      if ($row['Field']=="id") {
        $syskey=new stdClass();
        $syskey->type='primary';
        $syskey->name=$row['Field'];
	$element->syschildtablekeysinfo[]=$syskey;
	$element->syschildtablekeys[]=$row['Field'];
      }
      if (in_array($row['Field'], $element->syschildtablekeys) && $row['Field']!="id") continue; //we are under  normal keys query
      $element->childtablekeys[]=$row['Field'];
      $infokeys=new stdClass();
      Node::copyCleanValues($row, $infokeys);
      $element->childtablekeysinfo[]=$infokeys;
    }
    return $element;
  }
  function db_getmychildtablekeys() {
    return NodeFemale::db_getchildtablekeys($this);
  }
  function db_loadmychildtablekeys() {
    $this->load($this->db_getmychildtablekeys());
  }
  static function db_getroot($data) {
    $foreigncolumnname=NodeFemale::getSysKey($data); //it uses the parent->partner->props->id
    $primary_key= NodeFemale::getSysKey($data, "primary");
    $order_by='';
    if ($primary_key) $order_by=' ORDER BY ' . $primary_key;
    $sql = 'SELECT * FROM '
      . constant($data->props->childtablename)
      . ' WHERE ' . $foreigncolumnname  . ' IS NULL'
      . $order_by
      . ' LIMIT 1';
    $result = Node::get_db_gateway()->selectQuery($sql);
    if (count($result)==1) {
      $children=NodeFemale::readQuery($result);
      return NodeFemale::removeSysProps($children, $data)[0];
    }
    else return false;
  }
  function db_getmyroot() {
    return NodeFemale::db_getroot($this);
  }
  
  static function db_getchildren($data, $extraParents=null, $filterProp=[], $limit=[]) {
    $foreigncolumnnames=NodeFemale::getRealForeignKeys($data, $extraParents);
    $positioncolumnname=NodeFemale::getSysKey($data, 'sort_order');
    $searcharray = []; // search string array
    foreach ($filterProp as $key => $value) {
      $value=Node::get_db_gateway()->escape_string($value);
      $key=Node::get_db_gateway()->escape_string($key);
      if ($value===null) {
        array_push($searcharray, "{$key} IS NULL");
        continue;
      }
      array_push($searcharray, "{$key} = '{$value}'");
    }
    $sql = 'SELECT *' . ' FROM ' . constant($data->props->childtablename);
    if ($foreigncolumnnames && count($foreigncolumnnames)) {
      $sql .= ' WHERE ';
      $fsentence=[];
      array_push($fsentence, Node::get_db_gateway()->escape_string($foreigncolumnnames[0]) . '=\'' . Node::get_db_gateway()->escape_string($data->partnerNode->props->id) . '\'');
      for ($i=1; $i<count($foreigncolumnnames); $i++) {
        array_push($fsentence, Node::get_db_gateway()->escape_string($foreigncolumnnames[$i]) . '=\'' . Node::get_db_gateway()->escape_string($extraParents[$i-1]->partnerNode->props->id) . '\'');
      }
      $sql .=implode(' AND ' , array_merge($fsentence, $searcharray));
      if (isset($positioncolumnname) && $positioncolumnname) {
        $sql .= ' ORDER BY ' . $positioncolumnname;
      }
      if (count($limit) == 2) {
        $count_sql=str_replace('*' , 'COUNT(*)', str_replace(' ORDER BY ' . $positioncolumnname , '', $sql));
        if ($limit[0]==0) $sql .= ' LIMIT ' . Node::get_db_gateway()->escape_string($limit[1]);
        else {
          $lim_modul=$limit[1] - $limit[0];
          $sql .= ' LIMIT ' . $lim_modul . ' OFFSET ' . Node::get_db_gateway()->escape_string($limit[0]);
        }
      }
    }
    $result = Node::get_db_gateway()->selectQuery($sql);
    $children = NodeFemale::readQuery($result);
    $myReturn=new stdClass();
    if (isset($count_sql)) {
      $count_result = Node::get_db_gateway()->selectQuery($count_sql);
      $myReturn->total=$count_result[0];
    }
    else $myReturn->total=count($result);
    $myReturn->data=NodeFemale::removeSysProps($children, $data);
    return $myReturn;
  }
  function db_getmychildren($extraParents=null, $filterProp=[], $limit=[]) {
    return NodeFemale::db_getchildren($this, $extraParents, $filterProp, $limit);
  }
  function db_loadmychildren($extraParents=null, $filterProp=[], $limit=[]) {
    $result=$this->db_getmychildren($extraParents, $filterProp, $limit);
    $children=$result->data;
    foreach ($children as $child) {
      $this->addChild($child);
    }
    $this->props->total=$result->total;
  }
  function db_loadmytree($extraParents=null, $level=null, $filterProp=[], $limit=[]) {
    if ($level===0) return true;
    if ($level) $level--;
    $this->db_loadmychildren($extraParents, $filterProp, $limit);
    foreach ($this->children as $child)  {
      $child->db_loadmytree($extraParents, $level);
    }
    $myReturn=new stdClass();
    $myReturn->data=$this->children;
    $myReturn->total=$this->props->total;
    return $myReturn;
  }
  function db_getmytree($extraParents=null, $level=null, $filterProp=[], $limit=[]) {
    $result=$this->db_loadmytree($extraParents, $level, $filterProp, $limit);
    foreach ($result->data as $child)  {
      $child->parentNode=null;
    }
    return $result;
  }
  
  function db_getmypartner($child_id) {
    $foreigncolumnname=$this->getMySysKey();
    $child_id=Node::get_db_gateway()->escape_string($child_id);
    $sql = 'SELECT t.* FROM '
    . constant($this->props->parenttablename) . ' t'
    . ' inner join ' . constant($this->props->childtablename) . ' c'
    . ' on t.id=c.' . $foreigncolumnname
    . ' WHERE c.id=\'' . $child_id . '\'';
    
    $result = Node::get_db_gateway()->selectQuery($sql);
    if (count($result)==1) {
      $myPartner= new NodeMale();
      $myPartner->copyPropertiesFromArray($result[0]);
      return $myPartner;
    }
  }
  function db_loadmypartner($child_id) {
    $myPartner=$this->db_getmypartner($child_id);
    if ($myPartner) $myPartner->addRelationship($this);
  }
  function db_loadmytreeup($level=null) {
    if ($level===0) return true;
    if ($level) $level--;
    $this->partnerNode=null;
    $this->db_loadmypartner($this->getChild()->props->id);
    if ($this->partnerNode) $this->partnerNode->db_loadmytreeup($level);
    return $this->partnerNode;
  }
  function db_getmytreeup($level=null) {
    $myPartner=$this->db_loadmytreeup($level);
    if ($myPartner) {
      $myPartner->relationships=[];
    }
    return $myPartner;
  }
  static function db_getallchildren($data, $filterProp=[], $limit=[]) {
    $searcharray = []; // search string array
    foreach ($filterProp as $key => $value) {
      $key=Node::get_db_gateway()->escape_string($key);
      if ($value===null) {
        array_push($searcharray, "{$key} IS NULL");
        continue;
      }
      $value=Node::get_db_gateway()->escape_string($value);
      array_push($searcharray, "{$key} = '{$value}'");
    }
    $sql = 'SELECT * FROM ' . constant($data->props->childtablename);
    if (count($searcharray)>0) $sql .=' where ' . implode(' and ', $searcharray);
    if (count($limit) == 2) {
      $count_sql=str_replace('*' , 'COUNT(*)', $sql);
      if ($limit[0]==0) $sql .= ' LIMIT ' . Node::get_db_gateway()->escape_string($limit[1]);
      else {
        $lim_modul=$limit[1] - $limit[0];
        $sql .= ' LIMIT ' . $lim_modul . ' OFFSET ' . Node::get_db_gateway()->escape_string($limit[0]);
      }
    }
    $result = Node::get_db_gateway()->selectQuery($sql);
    $children= NodeFemale::readQuery($result);
    $myReturn=new stdClass();
    if (isset($count_sql)) {
      $count_result = Node::get_db_gateway()->selectQuery($count_sql);
      $myReturn->total=$count_result[0];
    }
    else $myReturn->total=count($result);
    $myReturn->data=NodeFemale::removeSysProps($children, $data);
    return $myReturn;
  }
  function db_getallmychildren($filterProp=[]) {
    return NodeFemale::db_getallchildren($this, $filterProp);
  }
  //<-- insert queries
  //This and the following two functions are the most important
  function db_insertmychild($thisChild, $extraParents=null) {
    if (!$thisChild) return false;
    if ($extraParents && !is_array($extraParents)) $extraParents=[$extraParents];
    $myprops=get_object_vars($thisChild->props); //copy properties to not modify original element
    //add link relationships column properties
    if (isset($this->partnerNode->props->id)) {
      $foreigncolumnnames=$this->getMyRealForeignKeys($extraParents);
      $positioncolumnname=$this->getMySysKey('sort_order');
      $myforeigncolumnname=$foreigncolumnnames[0] ;
      
      $myprops[$myforeigncolumnname]=$this->partnerNode->props->id;
      if ($positioncolumnname && !$myprops[$positioncolumnname]) {
        $myprops[$positioncolumnname]=1; //first element by default
      }
      if ($extraParents) {
        for ($i=1; $i<count($foreigncolumnnames); $i++) {
          $prop_name=$foreigncolumnnames[$i];
          $myprops[$prop_name]=$extraParents[$i-1]->partnerNode->props->id;
        }
      }
      if ($positioncolumnname && isset($myprops[$positioncolumnname])) {
        $update_siblings_sql = 'UPDATE ' . constant($this->props->childtablename)
        . ' SET ' . $positioncolumnname . '=' . $positioncolumnname . ' + 1'
        . ' WHERE '
        . $myforeigncolumnname . '=' . $this->partnerNode->props->id
        . ' AND ' . $positioncolumnname . ' >= ' . $myprops[$positioncolumnname];
      }
    }
    //Now we add a value for the props that are null and cannot be null except for the primary key
    if (isset($this->childtablekeysinfo) && isset($this->syschildtablekeys)) {
      foreach ($this->childtablekeys as $key => $value) {
        if  ($this->childtablekeysinfo[$key]->Null=='NO' && (!isset($this->childtablekeysinfo[$key]->Default) || $this->childtablekeysinfo[$key]->Default===null) && $this->childtablekeysinfo[$key]->Extra!='auto_increment'){
          if (!in_array($value, array_keys($myprops)) || $myprops[$value]===null) {
            if (strpos($this->childtablekeysinfo[$key]->Type, 'int')!==false || strpos($this->childtablekeysinfo[$key]->Type, 'decimal')!==false) {
              $myprops[$value]=0;
            }
            else {
              $myprops[$value]='';
            }
          }
        }
      }
    }
    if (isset($myprops['id'])) unset($myprops['id']); //let mysql give the id
    if (count(array_keys($myprops))==0) {
      $thisChild->props->id = Node::get_db_gateway()->insert_void(constant($this->props->childtablename));
    }
    else {
      $cleankeys=[];
      $cleanvalues=[];
      foreach ($myprops as $key => $value) {
        $value=Node::get_db_gateway()->escape_string($value);
        $key=Node::get_db_gateway()->escape_string($key);
        array_push($cleankeys, $key);
        array_push($cleanvalues, "'{$value}'");
      }
      $sql = 'INSERT INTO '
        . constant($this->props->childtablename)
        . ' (' . implode(', ', $cleankeys) . ')'
        . ' VALUES '
        . ' (' . implode(', ', array_values($cleanvalues)) . ')';
        $thisChild->props->id = Node::get_db_gateway()->insertQuery($sql);
    }
    if (isset($update_siblings_sql)) {
      $update_siblings_sql .= ' AND id !=' . $thisChild->props->id;
      Node::get_db_gateway()->updateQuery($update_siblings_sql);
    }
    return $thisChild->props->id;
  }
  //--> it seems this is not needed anymore
  function db_insertmylink($thisChild, $extraParents=null) {
    if ( !is_numeric($thisChild->props->id)) return false;
    if (($afected=$this->db_setmylink($thisChild, $extraParents))<=0) return $afected;

    //We try to update sort_order at the rest of elements
    $foreigncolumnname=$this->getMySysKey();
    $positioncolumnname=$this->getMySysKey('sort_order');
    
    if (!isset($thisChild->props->$positioncolumnname)) return $afected;

    $sql = 'UPDATE ' . constant($this->props->childtablename)
    . ' SET ' . $positioncolumnname . '=' . $positioncolumnname . ' + 1'
    . ' WHERE '
    . $foreigncolumnname . '=' . $this->partnerNode->props->id
    . ' AND id !=' . $thisChild->props->id
    . ' AND ' . $positioncolumnname . ' >= ' . $thisChild->props->$positioncolumnname;
    
    return Node::get_db_gateway()->updateQuery($sql);
  }
  function db_setmylink($thisChild, $extraParents=null) {
    if ( !is_numeric($thisChild->props->id)) return false;
    if ($extraParents && !is_array($extraParents)) $extraParents=[$extraParents];

    $foreigncolumnnames=$this->getMyRealForeignKeys($extraParents);
    $positioncolumnname=$this->getMySysKey('sort_order');
    
    //we will insert the relationship
    $sql = 'UPDATE ' . constant($this->props->childtablename) . ' SET ';
    $fsentence=[];
     array_push($fsentence, $foreigncolumnnames[0] ."=" . $this->partnerNode->props->id);
    if ($extraParents) {
      for ($i=1; $i<count($foreigncolumnnames); $i++) {
        array_push($fsentence, $foreigncolumnnames[$i] . '=' . $extraParents[$i-1]->partnerNode->props->id);
      }
    }
    if ($positioncolumnname) {
      if (!isset($thisChild->props->$positioncolumnname)) $thisChild->props->$positioncolumnname=1; //first element by default
      array_push($fsentence, $positioncolumnname . '=' . $thisChild->props->$positioncolumnname);
    }
    $sql .=implode(', ', $fsentence);
    $sql .=' WHERE ' . 'id =' . $thisChild->props->id;
    return Node::get_db_gateway()->updateQuery($sql);
  }
  //<-- it seems this is not needed anymore
  function db_insertmytree($level=null, $extraParents=null) {
    if ($level===0) return true;
    if ($level) $level--;
    foreach ($this->children as $child) {
      $child->db_insertmytree($level, $extraParents);
    }
    return $this;
  }
  function db_insertmytree_tablecontent($table, $level=null, $extraParents=null) {
    if ($level===0) return true;
    if ($level) $level--;
    foreach ($this->children as $child) {
      $child->db_insertmytree_tablecontent($table, $level, $extraParents);
    }
  }
  function db_insertmychildren($extraParents=null){
    $childrenIds=[];
    foreach ($this->children as $child)  {
      array_push($childrenIds, $this->db_insertmychild($child, $extraParents));
    }
    return $childrenIds;
  }
  //<-- delete queries
  //To ensure deletion we load first the tree
  function db_deletemytree($load=true){
    if ($load) $this->db_loadmytree();
    $afected=[];
    foreach ($this->children as $child)  {
      array_push($afected, $child->db_deletemytree(false));
    }
    return $afected;
  }
  function db_deletemylink($child) {
    if (($afected=$this->db_releasemylink($child))==0) return $afected;
    $foreigncolumnname=$this->getMySysKey();
    $positioncolumnname=$this->getMySysKey('sort_order');
    if (!$positioncolumnname) return $afected;
    //Now we got to update the sort_order of the borothers
    /*
    $childPositionSql= 'SELECT ' . $positioncolumnname . ' FROM ' .  constant($this->props->childtablename) . ' WHERE id=' .  $child->props->id;
    $sql = 'UPDATE ' . constant($this->props->childtablename)
    . ' SET ' . $positioncolumnname . '=' . $positioncolumnname . ' - 1'
    . ' WHERE '
    . $foreigncolumnname . '=' . $this->partnerNode->props->id
    . ' AND ' . $positioncolumnname . ' > (' . $childPositionSql . ')';
    return Node::get_db_gateway()->updateQuery($sql);
    */
    Node::get_db_gateway()->delete_update_siblings_sort_order(constant($this->props->childtablename), $positioncolumnname, $foreigncolumnname, $child->props->id, $this->partnerNode->props->id);
    return $afected;
  }
  
  //Pure link deletion
  function db_releasemylink($child){
    if (!is_numeric($child->props->id)) return false;
    $sql='UPDATE ' . constant($this->props->childtablename) . ' SET ';
    $sql .= $this->getMySysKey() . '=' . ' NULL';
    $sql .=' WHERE id=' . $child->props->id;
    return Node::get_db_gateway()->updateQuery($sql);
  }
}

class NodeMale extends Node{
  public $parentNode;
  public $relationships=array();

  //It loads data from a json, if update is true only fields and relationship present at original will be updated
  function load($source, $levelup=null, $leveldown=null, $thisProperties=null, $thisPropertiesUp=null, $thisPropertiesDown=null) {
    parent::load($source, null, null, $thisProperties);
    //if (isset($source->sort_order)) $this->sort_order=$source->sort_order;
    if ($levelup !== 0 && !($levelup < 0)) { //level null and undefined like infinite
      $this->loadasc($source, $levelup, $thisPropertiesUp);
    }
    if ($leveldown !== 0 && !($leveldown < 0)) {
      $this->loaddesc($source, $leveldown, $thisPropertiesDown);
    }
  }
  function loaddesc($source, $level=null, $thisProperties=null) {
    $this->relationships=[];
    if (gettype($source)=='string') $source=json_decode($source);
    //if (isset($source->sort_order)) $this->sort_order=$source->sort_order;
    if ($level===0) return;
    if ($level) $level--;
    if (!isset($source->relationships) || !$source->relationships) return false;
    for ($i=0; $i<count($source->relationships); $i++) { //keep previous rels
      $this->relationships[$i]=new NodeFemale();
      $this->relationships[$i]->partnerNode=$this;
      $this->relationships[$i]->load($source->relationships[$i], 0, 0, $thisProperties);
      $this->relationships[$i]->loaddesc($source->relationships[$i], $level, $thisProperties);
    }
  }
  function loadasc($source, $level=null, $thisProperties=null) {
    $this->parentNode=null;
    if (gettype($source)=='string') $source=json_decode($source);
    if (!isset($source->parentNode) || !$source->parentNode) return false;
    if ($level===0) return;
    if ($level) $level--;
    if (gettype($source->parentNode)=="array") { //A child could have diferent parents from diferent relationships. childtable -> parenttable1 - childtable -> parenttable2
      $this->parentNode=[];
      for ($i=0; $i < count($source->parentNode); $i++) {
	$this->parentNode[$i]=new NodeFemale();
        $this->parentNode[$i]->children[]=$this;
	$this->parentNode[$i]->load($source->parentNode[$i], 0, 0, $thisProperties);
	$this->parentNode[$i]->loadasc($source->parentNode[$i], $level, $thisProperties);
      }
    }
    else {
      $this->parentNode=new NodeFemale();
      $this->parentNode->children[]=$this;
      $this->parentNode->load($source->parentNode, 0, 0, $thisProperties);
      $this->parentNode->loadasc($source->parentNode, $level, $thisProperties);
    }
  }
  function cloneNode($levelup=null, $leveldown=null, $thisProperties=null, $thisPropertiesUp=null, $thisPropertiesDown=null) {
    $myClon=new NodeMale();
    $myClon->load($this, $levelup, $leveldown, $thisProperties, $thisPropertiesUp, $thisPropertiesDown);
    return $myClon;
  }
  function addRelationship($rel) {
    array_push($this->relationships, $rel);
    $rel->partnerNode=$this;
    return $rel;
  }
  function cutUp(){
    $this->parentNode=null;
  }
  function cutDown(){
    $this->relationships=[];
  }
  function getRelationship($data=null) {
    if ($data===null) return $this->relationships[0];
    if (gettype($data)=="string") {
      $obj=new stdClass();
      $obj->name=$data;
      $data=$obj;
    }
    foreach ($this->relationships as $rel) {
      if ($rel->props->name===$data->name) return $rel;
    }
    return false;
  }
  //gets root (root is allways a male node)
  //if tableName, get first element belonging to tablename
  function getrootnode($tableNameTop=null) {
    if ($this->parentNode && $this->parentNode->partnerNode &&
      (!$tableNameTop || $this->parentNode->props->childtablename!=$tableNameTop)) {
      return $this->parentNode->partnerNode->getrootnode($tableNameTop);
    }
    else if (!$tableNameTop || $this->parentNode && $this->parentNode->props->childtablename==$tableNameTop) return $this;
  }
  function avoidrecursion() {
    if ($this->parentNode) {
      if (gettype($this->parentNode)=='array') {
	foreach ($this->parentNode as $value) {
	  $value->avoidrecursionup();
	}
      }
      else $this->parentNode->avoidrecursionup();
    }
    foreach ($this->relationships as $rel)  {
      $rel->avoidrecursiondown();
    }
    return $this;
  }
  function avoidrecursionup(){
    $this->relationships=[];
    if ($this->parentNode) {
      if (gettype($this->parentNode)=='array') {
	foreach ($this->parentNode as $value) {
	  $value->avoidrecursionup();
	}
      }
      else $this->parentNode->avoidrecursionup();
    }
  }
  function avoidrecursiondown(){
    $this->parentNode=null;
    foreach ($this->relationships as $rel)  {
      $rel->avoidrecursiondown();
    }
  }
  //<-- Database queries
  //<-- Load queries
  //It requires parentNode->props->childtablename
  static function db_getrelationships($data) {
    $result = Node::get_db_gateway()->get_tables_has_foreigns(constant($data->parentNode->props->childtablename));
    $relationships=[];
    foreach ($result as $row) {
      $myrel = new NodeFemale();
      $myrel->copyPropertiesFromArray($row);
      $myrel->props->childtablename='TABLE_' . strtoupper($myrel->props->childtablename);
      $myrel->props->parenttablename='TABLE_' . strtoupper($myrel->props->parenttablename);
      $myrel->db_loadmychildtablekeys();
      $relationships[]=$myrel;
    }
    //Reorder rels. we must set the first relationship the selfrelationship one
    foreach($relationships as $key => $value) {
      if ($key==0 && $value->props->childtablename==$value->props->parenttablename)
        break;
      if ($value->props->childtablename==$value->props->parenttablename) {
	$changedRel=$relationships[0];
	$relationships[0]=$value;
	$relationships[$key]=$changedRel;
	break;
      }
    }
    return $relationships;
  }
  
  function db_getmyrelationships() {
    return NodeMale::db_getrelationships($this);
  }
  
  function db_loadmyrelationships() {
    $relationships=$this->db_getmyrelationships();
    foreach ($relationships as $rel) {
      $this->addRelationship($rel);
    }
  }
  
  //It requires parentNode->props->childtablename or relationships[0]->props->parenttablename
  function db_loadmyparent() {
    if ($this->parentNode) {
      $mytablename=$this->parentNode->props->childtablename;
    }
    else if (count($this->relationships) > 0) $mytablename=$this->relationships[0]->props->parenttablename;
    else return false;
    $result=Node::get_db_gateway()->get_foreign_tables(constant($mytablename));
    $this->parentNode=null;
    foreach ($result as $i => $row) {
      $this->parentNode[$i] = new NodeFemale();
      $this->parentNode[$i]->copyPropertiesFromArray($row);
      $this->parentNode[$i]->props->childtablename='TABLE_' . strtoupper($this->parentNode[$i]->props->childtablename);
      $this->parentNode[$i]->props->parenttablename='TABLE_' . strtoupper($this->parentNode[$i]->props->parenttablename);
      $this->parentNode[$i]->db_loadmychildtablekeys();
      $this->parentNode[$i]->children[0]=$this;
    }
    if (gettype($this->parentNode)=="array" && count($this->parentNode)==1) $this->parentNode=$this->parentNode[0];
    return $this->parentNode;
  }
  
  function db_loadmytree($extraParents=null, $level=null, $filterProp=[], $limit=[], $myself=false) {
    if ($level===0) return true;
    if ($level) $level--;
    if ($myself) $this->db_loadmyself();
    $this->db_loadmyrelationships();
    foreach ($this->relationships as $rel)  {
      $rel->db_loadmytree($extraParents, $level, $filterProp, $limit);
    }
    return $this;
  }
  
  function db_getmytree($extraParents=null, $level=null, $filterProp=[], $limit=[], $myself=false) {
    $tree=$this->db_loadmytree($extraParents, $level, $filterProp, $limit, $myself);
    if ($myself) return $tree;
    foreach ($tree->relationships as $rel)  {
      $rel->partnerNode=null;
    }
    return $tree->relationships;
  }
  
  function db_loadmytreeup($level=null) {
    if ($level===0) return true;
    if ($level) $level--;
    $this->db_loadmyparent();
    if (gettype($this->parentNode)=='array') {
      foreach($this->parentNode as $pNode) {
        $pNode->db_loadmytreeup($level);
      }
    }
    else if ($this->parentNode) $this->parentNode->db_loadmytreeup($level);
    return $this->parentNode;
  }
  
  function db_getmytreeup($level=null) {
    $parentNode=$this->db_loadmytreeup($level);
    if (gettype($parentNode)=='array') {
      foreach($parentNode as $pNode) {
        $pNode->children=[];
      }
    }
    else if ($parentNode) $parentNode->children=[];
    return $parentNode;
  }
  
  function db_loadmyself(){
    $sql="SELECT * FROM " . constant($this->parentNode->props->childtablename)
    . " WHERE id=" .  Node::get_db_gateway()->escape_string($this->props->id);
    $result = Node::get_db_gateway()->selectQuery($sql);
    if (count($result)==1) {
      $this->copyPropertiesFromArray($result[0]);
    }
    return count($result);
  }
  
  //<-- Insert queries
  function db_insertmyself($extraParents=null){
    return $this->parentNode->db_insertmychild($this, $extraParents);
  }
  
  function db_insertmylink($extraParents=null){
    return $this->parentNode->db_insertmylink($this, $extraParents);
  }
  //It inserts myself by default
  function db_insertmytree($level=null, $extraParents=null, $myself=null) {
    if ($level===0) return true;
    if ($level) $level--;
    if ($myself!==false) {
      if (!$this->db_insertmyself($extraParents)) return;
    }
    foreach ($this->relationships as $rel) {
      $rel->db_insertmytree($level, $extraParents);
    }
    return $this;
  }
  function db_insertmytree_tablecontent($table, $level=null, $extraParents=null) {
    if ($level===0) return true;
    if ($level) $level--;
    $isTableContent=false;
    foreach ($this->parentNode->syschildtablekeysinfo as $syskey) {
      if ($syskey->type=='foreignkey' && $syskey->parenttablename==$table) {
        $isTableContent=true;
        break;
      }
    }
    if ($isTableContent && !$this->db_insertmyself($extraParents)) return;
    foreach ($this->relationships as $rel) {
      $rel->db_insertmytree_tablecontent($level, $extraParents);
    }
    return $this->relationships;
  }
  //<-- Delete queries
  function db_deletemytree($load=true){
    if ($load) $this->db_loadmytree();
    $afected=$this->db_deletemyself();
    foreach ($this->relationships as $rel) {
      $rel->db_deletemytree(false);
    }
    return $afected;
  }
  //Deletes a node. Note: After deleting a node we must remove also the nodes below.
  function db_deletemyself() {
    if (!is_numeric($this->props->id)) return false;
    if ($this->parentNode->getMySysKey('sort_order')) {
      $this->db_deletemylink(); //The pourpose of explicity deleting the link is to update the sort order of the brothers
    }
    $sql='DELETE FROM '
    . constant($this->parentNode->props->childtablename)
    . ' WHERE id=' . $this->props->id; //' LIMIT 1'; not valid in pgsql
    return Node::get_db_gateway()->updateQuery($sql);
  }

  function db_deletemylink() {
    return $this->parentNode->db_deletemylink($this);
  }
  
  //Pure link deletion
  function db_releasemylink(){
    return $this->parentNode->db_releasemylink($this);
  }
  //<-- Update queries
  function db_updatemyprops($proparray){
    if ( !is_numeric($this->props->id)) return false;
    //We update the fields
    //We take in acount for sql injections
    $cleansentence=[];
    foreach ($proparray as $key => $value) {
      $value=Node::get_db_gateway()->escape_string($value);
      $key=Node::get_db_gateway()->escape_string($key);
      array_push($cleansentence, "{$key}='{$value}'");
    }
    $sql = 'UPDATE '
    . constant($this->parentNode->props->childtablename)  .
      ' SET ';
    $sql .= implode(",", $cleansentence);
    $sql .=' WHERE id=' . $this->props->id;
    return Node::get_db_gateway()->updateQuery($sql);
  }
  
  function db_updatemysort_order($new_sort_order){
    if ( !is_numeric($this->props->id) || !is_numeric($new_sort_order)) return false;
    $foreigncolumnname=$this->parentNode->getMySysKey();
    $positioncolumnname=$this->parentNode->getMySysKey('sort_order');
    $sql = 'UPDATE ' . constant($this->parentNode->props->childtablename)
    . ' SET ' . $positioncolumnname . '=' . $new_sort_order
    . ' WHERE ' . 'id=' . $this->props->id;
    $updated=Node::get_db_gateway()->updateQuery($sql);
    if ($updated > 0) { //update sibling sort_order
      Node::get_db_gateway()->update_sibling_sort_order(constant($this->parentNode->props->childtablename), $positioncolumnname, $foreigncolumnname, $this->props->id, $this->parentNode->partnerNode->props->id, $new_sort_order);
    }
    return $updated;
  }
}

?>
