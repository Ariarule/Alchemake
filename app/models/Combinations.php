<?php
class Combinations extends Phalcon\Mvc\Model {

  public $this->itemid;
  public $this->ingredient1_itemid;
  public $this->ingredient2_itemid;
  public $this->ingredient3_itemid;
  public $this->preq_tool_itemid;

  public function initialize() {
    foreach ([1,2,3] as $i) {
      $this->hasMany("ingredient{$i}_itemid",'Items','itemid');
    }
    $this->hasOne('preq_tool_itemid','Items','itemid');
    $this->hasOne('itemid','Items','itemid');
  }
}
