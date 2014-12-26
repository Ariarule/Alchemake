<?php

class Item extends Phalcon\Mvc\Model {

  public function transfer($from,$to,$itemid,$qty) {
    global $mysql_link;
    //to
    $sql_r = add_items($to,$itemid,$qty);
    if (!$sql_r) {
      trigger_error("Query failed in item_transfer <!-- $qty x $itemid TO $to -->",E_USER_NOTICE);
      }
    //from
    $sql_r = rm_item($from,$itemid,$qty);
    if (!$sql_r) {
      trigger_error("Query failed in item_transfer <!-- $qty x $itemid FROM $from -->",E_USER_NOTICE);
      }
  }

  function item_names(&$items) {
    global $mysql_link;
    //UGLY HACK: Loads all item names into memory for trade tables. Should really be done in MySQL with multiple joins
    //OTOH: Can use for real-time checking of possible combinations, etc.
    $sql = "SELECT * FROM `items`;";
    $items_r = mysql_query($sql,$mysql_link);
    while ($c_item = mysql_fetch_assoc($items_r)) {
      $items[$c_item['itemid']] = $c_item;
      }
    }

  function is_item_basic($itemno) {
    //possible improvement: mysql call?
    if (($itemno > 15) && ($itemno < 26)) {
      return TRUE;
      }
    else {
      return FALSE;
      }
  }

}
