<?php
require_once('alchemake-inc.php');
require_once('facebook.php');
if ($alphamode) {
  context_setup(TRUE);
  $userid = '6897197ae54b3206da04390198a4d8fd46d8b803';
  restore_error_handler(); //on-screen errors when testing
  }
else {
  context_setup();
  }
$proposed_nom = '';
if (isset($_POST['proposednom'])) {
  $proposed_nom = mysql_real_escape_string(trim($_POST['proposednom'])); //TODO: Replace with alphanum restriction
  }

function friends_table() {
  global $mysql_link;
  global $fb;
  
  $return_array = array();
  //each element is array(Nom, Friends Bool, Image URL)

  $friends = $fb->api_client->friends_list;
  $sql = "SELECT `nickname`,`networkid` FROM `users` WHERE 0";
  $whereclause = array();

  foreach ($friends as $friend) {
    $whereclause[] = " (`userid` = '" . userhash($friend) . "') ";
    }

  if (sizeof($friends > 0)) {
    $sql .= (' OR ' . implode(' OR ',$whereclause) . ";");
    }
  $friends_r = mysql_query($sql,$mysql_link);
  $friends_table = set_of_rows_to_table($friends_r);

  shuffle($friends_table);
  $i = 0;
  foreach ($friends_table as $friend_playing) {
    $i++;
    $fb_friend_info = $fb->api_client->users_getInfo($friend_playing['networkid'], 'pic_square');
    $return_array[] = array($friend_playing['nickname'],true,$fb_friend_info[0]['pic_square']);
    if ($i > 14) {
      break; //these calls are expensive, so max out at 15
      }
    }

  if ($i < 10) {
    $sql = "SELECT `nickname` FROM `users` WHERE 1 ORDER BY RAND() LIMIT 5;";
    $friends_r = mysql_query($sql,$mysql_link);
    $friends_table = set_of_rows_to_table($friends_r);
    foreach ($friends_table as $friend_playing) {
      $return_array[] = array($friend_playing['nickname'],FALSE,NULL);
      }
    }

  return $return_array;
  }

if (!time_nonce($_GET['time'],$_GET['nonce'])) {
  bounce_back("Nonce Mismatch",NONCEERR);
  }
?>

<script type="text/javascript">
function nomToForm(nom) {
  var nomArea;
  nomArea = document.getElementById("proposednom");
  nomArea.setValue(nom);
  }
</script>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>/alchemake.css?v=<?php echo $version;?>" />

<div id="page">
<?php echo "<form method=\"POST\" action=\"setuptrade.php?time={$_GET['time']}&nonce={$_GET['nonce']}\">";?>
You are offering to trade the following:<br />
<?php
foreach($_POST['items'] as $trade_order => $item) {
  $item = (int)$item;
  if (!isset($_POST['qty'][$trade_order])) {
    $_POST['qty'][$trade_order] = 1;
    }
  $_POST['qty'][$trade_order] =  (int)$_POST['qty'][$trade_order];
  echo "<input type=\"hidden\" name=\"items[$trade_order]\" value=\"$item\"><input type=\"text\" value=\"{$_POST['qty'][$trade_order]}\" name=\"qty[$trade_order]\"> x {$items[$item]['name']}<br />";
  }
?>

Who do you want to trade with?

<?php
$friends = friends_table();
$i = 1;
echo "<table>";
foreach ($friends as $friend) {
  if (($i % 5) == 1) {
    if ($i > 1) {
      echo "</tr>";
      }
    echo "<tr>";
    }
  $i++;
  echo "<td onclick='nomToForm(\"{$friend['0']}\")'>";
  if (trim($friend[2]) != '') {
    echo "<img src=\"{$friend[2]}\" alt='{$friend['0']}'><br />";
    }
  echo $friend['0'] . " </td>\n";
  }
echo "</tr></table>";

?>
Click on a Trading Partner or Copy &amp; Paste their Nom de Alchemake here: <input name="proposednom" id="proposednom" type="text" value="<?php echo $proposed_nom ?>">
<input type="submit" class="alchemakebutton" name="action" value="Select Trading Partner" />
<?php 
if (isset($_POST['proposednom'])) {
  if(!does_user_exist_by_nom($proposed_nom)) {
    echo "Sorry, I can't find $proposed_nom or their inventory.";
    //not error, as option to type in
    }
  else { 
    if ($_POST['action'] === 'Select Trading Partner') {
      $proposed_inventory = get_inventory_table($_POST['proposednom'],NULL,TRUE);
      ?>
      
      <table>
      <caption><?php echo $proposed_nom; ?> Inventory:</caption>
      <thead>
      <tr><td>Item</td><td>Qty Available</td><td>Ask For:</td></tr>
      </thead>
      <tbody>
      <?php
      foreach ($proposed_inventory as $inventory_row) {
	if (isset($_POST['proposed_items_qty'][$inventory_row['itemid']])) {
	   $_POST['proposed_items_qty'][$inventory_row['itemid']] = (int)$_POST['proposed_items_qty'][$inventory_row['itemid']];
	   }
	else {
	  $_POST['proposed_items_qty'][$inventory_row['itemid']] = 0;
	  }
	$shadetext = linebg();
	echo "<tr $shadetext><td>{$inventory_row['name']}</td><td>{$inventory_row['available']}</td><td><input type=\"textbox\" name=\"proposed_items_qty[{$inventory_row['itemid']}]\" value=\"{$_POST['proposed_items_qty'][$inventory_row['itemid']]}\"></td></tr>";
	}
      ?>
      </tbody>
      </table>
      <input type="submit" name="action" class="alchemakebutton" value="Suggest Trade" />
      </form>
      <?
      }
    elseif ($_POST['action'] === 'Suggest Trade') {
      $proposed_user = get_userid_from_nick($proposed_nom);
      if ($proposed_user === FALSE) {
	echo $general_oops;
	trigger_error("Can't find $proposed_nom.",E_USER_ERROR);
	}
      $trade = array('proposer' => array('userid' => $userid,'items' => array()),'proposed' => array('userid' => $proposed_user,'items' => array()));
      foreach ($_POST['items'] as $index => $proposer_item) {
	$proposer_item = (int)$proposer_item; //should do nothing
	$index = (int)$index; //should do nothing
	$qty = (int)$_POST['qty'][$index];
	if ($qty > 0) {
	  $trade['proposer']['items'][$proposer_item] = $qty;
	  }
	}
      foreach ($_POST['proposed_items_qty'] as $itemno => $qty) {
	$itemno = (int)$itemno; //should do nothing
	$qty = (int)$qty; //should do nothing
	if ($qty > 0) {
	  $trade['proposed']['items'][$itemno] = $qty;
	  }
	}
      include('maketrade.php');
      echo "<div class=\"noticebox\">Trade suggestion has been sent!</div>";
      }
    }
  }
?>
<div style="clear: both;">&nbsp;</div>
<?php
return_button();
?>
</div>