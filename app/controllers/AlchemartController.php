<?php

class AlchemartController extends AlchemakeController {

}

/*$ay_table =  get_inventory_table($userid,1);

?>
<link href="<?php echo $base_url;?>alchemake.css?v=<?php echo $version;?>" rel="stylesheet" type="text/css"/>

<div id ="page">
<img src="<?php echo $base_url;?>alchemart.png?v=<?php echo $version;?>" alt="Welcome to Alchemart" />
<div id="martinfo">All items AY 50 except as marked.
<?php

if (isset($_POST['itemno'])) {
  $_POST['itemno'] = (int)$_POST['itemno'];
  $_POST['qty'] = (int)$_POST['qty'];
  if (is_item_basic($_POST['itemno'])) {
    $cost = $_POST['qty'] * 50;
    if ($cost > $ay_table[1]['available']) {
      echo "You don't currently have enough AY. (Cost: AY $cost)";
      }
    else {
      $success = add_items($userid,$_POST['itemno'],$_POST['qty']);
      if ($success) {
  echo "<div class=\"noticebox\">Success! You got {$_POST['qty']}x {$items[$_POST['itemno']]['name']}</div>";
  $cost_sql = "UPDATE `inventory` SET `qty` = `qty` - $cost WHERE `userid` = '$userid' AND `itemid` = 1 LIMIT 1";
  $cost_r = mysql_query($cost_sql,$mysql_link);
  }
      }
    }
  }
$ay_table =  get_inventory_table($userid,1); //needs reloading for next line
echo "You currently have AY {$ay_table[1]['available']} available.</div>";
?>

<?php for ($i = 16;$i < 26;$i++) { ?>
<form method="POST">
<input type="hidden" name="itemno" value="<?php echo $i;?>" />
<div class="buttonholder" style="clear: both; border-bottom: 1px black dotted;"><?php echo $items[$i]['name'];?></div>
<div><input class="alchemakebutton" type="submit" name="qty" value="1 <?php echo $items[$i]['name'];?>" />
<input class="alchemakebutton" type="submit" name="qty" value="2 <?php echo $items[$i]['name'];?>" />
<input class="alchemakebutton" type="submit" name="qty" value="5 <?php echo $items[$i]['name'];?>" /></div>
</form>
<?php } ?>

<div style="clear: both;"><?php return_button();?></div>
</div> */
