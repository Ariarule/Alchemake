<?php
header("content-type: application/javascript");
require_once('alchemake-inc.php');
context_setup(TRUE,TRUE);

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