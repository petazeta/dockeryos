<?php
class NodeMale extends Node{
  public $parentNode;
  public $relationships=array();


  
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
    if (isset($this->parentNode->props->childtablename) && isset($this->parentNode->partnerNode->props->id) && $this->parentNode->getMySysKey('sort_order')) {
      $this->db_deletemylink(); //The pourpose of explicity deleting the link is to update the sort order of the brothers
    }
    $sql='DELETE FROM '
    . constant($this->parentNode->props->childtablename)
    . ' WHERE id=' . $this->props->id; //' LIMIT 1'; not valid in pgsql
    return Node::get_db_gateway()->updateQuery($sql);
  }

  function db_deletemylink() {
    if (($afected=$this->db_releasemylink())==0) return $afected;
    $foreigncolumnname=$this->parentNode->getMySysKey();
    $positioncolumnname=$this->parentNode->getMySysKey('sort_order');
    if (!$positioncolumnname) return;
    //Now we got to update the sort_order of the borothers
    $sql = 'UPDATE ' . constant($this->parentNode->props->childtablename)
    . ' SET ' . $positioncolumnname . '=' . $this->props->$positioncolumnname . ' - 1'
    . ' WHERE'
    . $foreigncolumnname . '=' . $this->parentNode->partnerNode->props->id
    . ' AND ' . $positioncolumnname . ' > ' . $this->props->$positioncolumnname;
    return Node::get_db_gateway()->updateQuery($sql);
  }
  
  //Pure link deletion
  function db_releasemylink(){
    if ( !is_numeric($this->props->id)) return false;
    $sql='UPDATE ' . constant($this->parentNode->props->childtablename)
    . ' SET ';
    $foreigncolumnname=$this->parentNode->getMySysKey();
    $sql .= $foreigncolumnname . '=' . ' NULL';
    $sql .=' WHERE id=' . $this->props->id;
    return Node::get_db_gateway()->updateQuery($sql);
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
      $sql = 'UPDATE ' . constant($this->parentNode->props->childtablename) . ' dt1, ' . constant($this->parentNode->props->childtablename) . ' dt2'
      . ' SET dt1.' . $positioncolumnname . '=dt2.' . $positioncolumnname
      . ' WHERE'
      . ' dt1.id !=' . Node::get_db_gateway()->escape_string($this->props->id)
      . ' AND dt1.' . $foreigncolumnname . '=' . $this->parentNode->partnerNode->props->id
      . ' AND dt1.' . $positioncolumnname . '=' . $new_sort_order
      . ' AND dt2.id=' . Node::get_db_gateway()->escape_string($this->props->id);
      Node::get_db_gateway()->updateQuery($sql);
    }
    return $updated;
  }
}

?>
