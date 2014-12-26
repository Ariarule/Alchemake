<?php

class ComboController  extends AlchemakeController {

  public function suggest() {
    //TODO: Rewrite

    //First, sort the item ids so they're in accending order, lowest to highest //C&P from combine.php
    foreach ($_POST['items'] as $key=> &$item) {
      $item = (int)$item; //only integers allowed
      if ($item == 0) {
        unset($_POST['items'][$key]);
        }
      }
    sort($_POST['items']);
    if (sizeof($_POST['items']) == 2) {
      $_POST['items'][2] = 0;
      }

    $_POST['suggestion'] = mysql_real_escape_string($_POST['suggestion']);

    $sql = "INSERT INTO `combo-suggest` (`userid` ,`ingredient1_itemid` ,`ingredient2_itemid` ,`ingredient3_itemid` , `suggestion`)"
      . "VALUES ('$userid', '{$_POST['items'][0]}', '{$_POST['items'][1]}', '{$_POST['items'][2]}', '{$_POST['suggestion']}')";
    $sql_r = mysql_query($sql,$mysql_link);

    ?>
    Thank you for suggesting a new combination for Alchemake.
    <?php
    return_button();
  }

  public function all() {
    //TODO: Rewrite
    header("content-type: application/javascript");

    $possible_items = array(); //the array that's returned via JSON of combinable items

    @$_POST[0] = (int)$_POST[0];
    @$_POST[1] = (int)$_POST[1];

    if ($_POST[0] == '0') {
      echo json_encode(array());
      }

    $sql = "SELECT `ingredient1_itemid` as `1`,`ingredient2_itemid`  as `2`,`ingredient3_itemid`  as `3` FROM `combinations`"
        . " WHERE (`ingredient1_itemid` = {$_POST[0]} OR `ingredient2_itemid` = {$_POST[0]} OR `ingredient1_itemid` = {$_POST[0]})";
    if ($_POST[1] != 0) {
      $sql .= " AND (`ingredient1_itemid` = {$_POST[1]} OR `ingredient2_itemid` = {$_POST[1]} OR `ingredient1_itemid` = {$_POST[1]})";
      }

    $sql_r = mysql_query($sql,$mysql_link);
    $allcombs = set_of_rows_to_table($sql_r);

    foreach ($allcombs as $combo) {
      foreach ($combo as $item) {
        $possible_items[$item] = TRUE;
        }
      }

    unset($possible_items[0]);
    echo json_encode($possible_items);
  }
