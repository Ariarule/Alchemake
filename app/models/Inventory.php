<?php

class Inventory extends Phalcon\Mvc\Model {

  public $userid;
  public $itemid;
  public $qty;

  public function initialize() {
    $this->hasManyToMany('userid','Users','userid','itemid','Items','itemid');
  }

  public function transfer($from,$to,$itemid,$qty) {
    //TODO: Write transfer function
  }

  public function inventory_drop($userid) {
    $number_of_items = rand(2,4);
    $success_on_all = TRUE;
    for ($i = 0; $i < $number_of_items; $i++) {
      $itemno = rand(16,25); //should be selected out of an array of basic items
      $qty = rand(1,3);
      if(!add_items($userid,$itemno,$qty)) {
        $success_on_all = FALSE;
        }
      }
    return $success_on_all;
  }

}
