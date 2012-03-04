<?php
require_once('alchemake-inc.php');
context_setup();
?><pre><?php
echo "$userid\n";
print_r($_POST);
?></pre><?php

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