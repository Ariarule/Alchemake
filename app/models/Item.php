<?php

class Item extends Phalcon\Mvc\Model {

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
