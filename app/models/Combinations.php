<?php
class Combinations extends Phalcon\Mvc\Model {

  public $itemid;
  public $ingredient1_itemid;
  public $ingredient2_itemid;
  public $ingredient3_itemid;
  public $preq_tool_itemid;

  public function initialize() {
    foreach ([1,2,3] as $i) {
      $this->hasMany("ingredient{$i}_itemid",'Items','itemid');
    }
    $this->hasOne('preq_tool_itemid','Items','itemid');
    $this->hasOne('itemid','Items','itemid');
  }

}
