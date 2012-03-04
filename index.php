<?php
require_once('alchemake-inc.php');
require_once('random-name.php');
if ($alphamode) {
  context_setup(TRUE);
  $userid = '6897197ae54b3206da04390198a4d8fd46d8b803';
  restore_error_handler(); //on-screen errors when testing
  }
else {
  if(!isset($_POST['fb_sig_in_canvas'])) {
    header("Location: http://apps.facebook.com/alchemake/");
    exit("<html><body><a href=\"http://apps.facebook.com/alchemake/\">Continue to Alchemake</a></body></html>");
    }
  context_setup();
  }
function show_pending_trades() {
  global $pending_trades_header; //TODO: Fix. Where is this defined?
  global $userid;
  global $mysql_link;

  echo $pending_trades_header;?>

  <h2>Trades you've suggested:</h2>
  <?php
  $sql = "SELECT * FROM (SELECT `tradedetails`.`proposer_itemid`,`tradedetails`.`proposer_qty`,`tradedetails`.`proposed_itemid`,`tradedetails`.`proposed_qty`,`trades`.*
  FROM `tradedetails` , `trades`
  WHERE `tradedetails`.`tradeid` = `trades`.`tradeid`
  AND `proposer_userid` = '$userid' AND `status` = 'pending') as `trades` JOIN `users` on (`users`.`userid` = `trades`.`proposed_userid`) WHERE 1";
  $trades_r = mysql_query($sql,$mysql_link);
  $pending_num = mysql_num_rows($trades_r);
  $trade_table = set_of_rows_to_table($trades_r);
  $trade_table = parse_trade_table($trade_table);
  ?>
  <table>
  <tr><td>Trading Partner (Nom&nbsp;de&nbsp;Alchemake)</td><td>Items Offered (by&nbsp;You)</td><td>Items Requested (from&nbsp;Them)</td><td>Status</td></tr>
  <?php
  foreach ($trade_table as $tradeid => $trade) {
    $shadetext = linebg();
    echo "<tr $shadetext><td>{$trade['nickname']}</td><td>"; //NOTE: New cell starts here!
    foreach ($trade['proposer_table'] as $itemno => $item_qty) {
      echo "{$item_qty}&ndash;{$items[$itemno]['name']}<br />";
      }
    echo "</td><td>";
    foreach ($trade['proposed_table'] as $itemno => $item_qty) {
      echo "{$item_qty}&ndash;{$items[$itemno]['name']}<br />";
      }
    echo "</td><td>{$trade['status']}<br /><span class=\"actionlink\"><a href=\"tradeaction.php?action=withdraw&tradeid=$tradeid&time=$gen_time&nonce=$gen_nonce\">Withdraw</span></td></tr>";
    }

  ?>
  </table>
  <?php
  if ($pending_num === 0) {
    echo "[You haven't suggested any trades]";
    }
  ?>
  <h2>Trades others have suggested:</h2>
  <?php
  $sql = "SELECT * FROM (SELECT `tradedetails`.`proposer_itemid`,`tradedetails`.`proposer_qty`,`tradedetails`.`proposed_itemid`,`tradedetails`.`proposed_qty`,`trades`.*
  FROM `tradedetails` , `trades`
  WHERE `tradedetails`.`tradeid` = `trades`.`tradeid`
  AND `proposed_userid` = '$userid' AND `status` = 'pending') as `trades` JOIN `users` on (`users`.`userid` = `trades`.`proposer_userid`) WHERE 1";
  $trades_r = mysql_query($sql,$mysql_link);
  $pending_num = mysql_num_rows($trades_r);
  $trade_table = set_of_rows_to_table($trades_r);
  $trade_table = parse_trade_table($trade_table);
  ?>
  <table>
  <tr><td>Suggested by (Nom&nbsp;de&nbsp;Alchemake)</td><td>Items Requested (for&nbsp;Them)</td><td>Items Offered (to&nbsp;You)</td><td>Status</td></tr>
  <?php
  foreach ($trade_table as $tradeid =>  $trade) {
    $shadetext = linebg();
    echo "<tr $shadetext><td>{$trade['nickname']}</td><td>"; //NOTE: New cell starts here!
    foreach ($trade['proposed_table'] as $itemno => $item_qty) {
      echo "{$item_qty}&ndash;{$items[$itemno]['name']}<br />";
      }
    echo "</td><td>";
    foreach ($trade['proposer_table'] as $itemno => $item_qty) {
      echo "{$item_qty}&ndash;{$items[$itemno]['name']}<br />";
      }
    echo "</td><td>{$trade['status']}
    <br /><span class=\"actionlink\"><a href=\"tradeaction.php?action=reject&tradeid=$tradeid&time=$gen_time&nonce=$gen_nonce\">Reject</span>
    <br /><span class=\"actionlink\"><a href=\"tradeaction.php?action=accept&tradeid=$tradeid&time=$gen_time&nonce=$gen_nonce\">Accept</span>
    </td></tr>";
    }
  ?>
  </table>
  <?php
  if ($pending_num === 0) {
    echo "[No one's suggested any trades to you]";
    }
  }

function campaign_box() {
  echo "<div class=\"campaign_box\">This will show the campaign text when fully implemented.</div>";
  }

function game_logo() {
  echo "<img src=\"http://alchemake.com/logo.png\" alt=\"Alchemake\" style=\"display: block;\"/>";
  }

function show_inventory($inventory_table) {
  global $gen_nonce;
  global $gen_time;

  echo "<form method=\"POST\" action=\"action.php?time=$gen_time&nonce=$gen_nonce\">\n";
  ?>
  <table>
  <tr><td>Item Name</td><td>&nbsp;</td><td>Description</td><td>Quantity</td><td>Quantity Available</td><td>Use?</td></tr>
  <?php
  foreach ($inventory_table as $inventory_row) {
    if (is_item_basic($inventory_row['itemid'])) {
      $storetext = "<a href=\"alchemart.php?time=$gen_time&nonce=$gen_nonce\" title=\"50 AY\">&ndash;&curren;&ndash;</a>";
      }
    else {
      $storetext = '';
      }

    $shadetext = linebg();
    $imgtext = "";
    if ($inventory_row['image'] != "") {
      $imgtext = "<img src=\"http://alchemake.com/itempics/01029260/{$inventory_row['image']}\" />";
      }

    //$inventory_row['available'] = $inventory_row['qty'] - (int)$inventory_row['sum_proposer_qty'];
    echo "<tr id=\"row{$inventory_row['itemid']}\" $shadetext><td>{$inventory_row['name']}{$storetext}</td><td>$imgtext</td><td>{$inventory_row['description']}</td><td>{$inventory_row['qty']}</td><td>{$inventory_row['available']}</td><td>" . make_checkbox($inventory_row['itemid'],$inventory_row['available']) . "</td></tr>";
    }
  ?>
  </table>
  <div class="buttonholder"><input class="alchemakebutton" type="submit" name="alchemake" value="Alchemake" title="Alchemake The Checked Items!"/><input class="alchemakebutton" type="submit" name="alchemake" value="Trade" title="Trade Items"/></div>
  </form>
  <?php
  }

function show_card($user_pic,$userinfo) {
  global $gen_time;
  global $gen_nonce;
  global $rank_texts;
  global $userid;

  echo "<form method=\"POST\" action=\"changenom.php?time=$gen_time&nonce=$gen_nonce\">\n";
  echo "<div id=\"liscinfo\">";
  if (is_string($user_pic[0]['pic_square']) && ($user_pic[0]['pic_square'] != '')) {
    echo "<img src=\"{$user_pic[0]['pic_square']}\" style=\"float: left;\" height=\"50px\" width=\"50px\"/>\n";
    }
  echo "<h1 class=\"liscinfo\">Official Alchemaking&nbsp;Card</h1>\n<hr style=\"clear: both\"/>\n<ul class=\"liscinfo\"><li>Nom&nbsp;de&nbsp;Alchemake:<br />\n<input type=\"text\" style=\"border: 0px; border-bottom: 1px dotted black; background: #DDDDDD; opacity: 0.75;\" size=\"37\" value=\"{$userinfo['nickname']}\" name=\"newnom\">\n<button class=\"alchemakebutton\" type=\"submit\"><small>change Nom</small></button></li><li>Alchemake Guild #:<br />$userid</li><li>Rank: {$rank_texts[$userinfo['rank']]}</li><li>Status: In&nbsp;Good&nbsp;Standing</li></ul></div>\n"; //TODO: Implement Rank. Maybe insert facebook pic if available?
  echo "</form>";
  }

function do_drops($time_from_ay,$time_from_drop) {
  if ($time_from_ay > 604801) {
    $allowence = get_allowence();
    if(($allowence > 0) && (add_items($userid,1,$allowence))) {
      echo "<div class='noticebox'>You have been given AY $allowence from the Alchemake Alchemy Guild. Thank you for Alchemaking.</div>";
      $sql = "UPDATE `alchemake`.`users` SET `last_allowence` = NOW() WHERE `users`.`userid` = '$userid' LIMIT 1;";
      $sql_r = mysql_query($sql,$mysql_link);
      if (mysql_affected_rows($mysql_link) != 1) {
	trigger_error("Couldn't update last_allowence correctly for $userid.",E_USER_NOTICE);
	}
      }
    }
  else {
    $droproll = rand(1,86400);
    if (($droproll < $time_from_drop) && ($time_from_drop > 120)) {
      if (drop_items($userid)) {
	echo "<div class='noticebox'>You have new items!</div>";
	$sql = "UPDATE `alchemake`.`users` SET `last_drop` = NOW() WHERE `users`.`userid` = '$userid' LIMIT 1;";
	$sql_r = mysql_query($sql,$mysql_link);
	if (mysql_affected_rows($mysql_link) != 1) {
	  trigger_error("Couldn't update last_drop correctly.",E_USER_NOTICE);
	  }
	}
      }
    }
  }

function make_checkbox($itemid,$available) {
  if ($available > 0) {
    return " <input type=\"checkbox\" name=\"items[]\"  onclick=\"statechange({$itemid});\" value=\"{$itemid}\"> ";
    //FUTURE: JS TO DISABLE ALCHEMAKE BUTTON IF THIS IS YEK!
    }
  else {
    return "";
    }
  }

function ay_available() {
//BUGGY, TODO: FIX
  return TRUE; //TODO: Get rid of return true here.
  //is there AY Stock available to be sold?
  /*global $mysql_link;
  //called at least twice, so cache result
  static $already_checked = FALSE;
  static $available = FALSE;

  if ($already_checked) {
    return $available;
    }

  $query = "SELECT `AY_Stock` FROM `ay_price_history` WHERE `current` = 1 LIMIT 1;";
  $stock_r = mysql_query($query,$mysql_link);
  
  $already_checked = TRUE;

  $stock = mysql_fetch_assoc($stock_r);
  if ($stock['AY_Stock'] >= 100) {
    $available = TRUE;
    return TRUE;
    }
  else {
    $available = FALSE;
    return FALSE;
    }*/
  }
?>
<link href="<?php echo $base_url;?>alchemake.css?v=<?php echo $version;?>" rel="stylesheet" type="text/css"/>
<script type="text/javascript">
//<!--

function highlighter(itemarray) {
  //FUTURE: Smooth Animation for highlighting
  if (typeof highlighter.alchetalk == 'undefined') {
    highlighter.chighlighted = {};
    highlighter.alchetalk = new Ajax;
    highlighter.alchetalk.responseType = Ajax.JSON;
    //highlighter.alchetalk.url = 'http://alchemake.com/allcombs.js.php';
    highlighter.alchetalk.ondone = function(data) {
      for (highlighted in highlighter.chighlighted) {
	highlighter.chighlighted[highlighted].setStyle('background',"");
	}
      delete highlighter.chighlighted;
      highlighter.chighlighted = {};
      var c_element;
      for (item in data) {
	c_element = document.getElementById("row" + item);
	if (typeof c_element.setStyle == "function") {
	  c_element.setStyle('background',"#dddd99");
	  highlighter.chighlighted[item] = c_element;
	  }
	}
      }
    }
  postarr = {0:0,1:0};
  highlighter.alchetalk.abort(); //Give up on previous if no response yet.
  
  i = 0;
  for (item in itemarray) {
    postarr[i] = item; //SIC. want the key, not the value "True"
    i++;
    }
  
  highlighter.alchetalk.post('<?php echo $base_url;?>/allcombs.js.php',postarr);
  return true;
  }

function statechange(number) {
  if (typeof statechange.items == 'undefined') {
    statechange.items = {};
    statechange.checkcount = 0;
    }
  if (typeof statechange.items[number] == 'undefined') {
    statechange.items[number] = true;
    statechange.checkcount++;
    }
  else {
    statechange.items[number] = !statechange.items[number];
    if (statechange.items[number]) {
      statechange.checkcount++;
      }
    else {
      //This is messed up. FUTURE: Compact this code.
      statechange.checkcount--;
      statechange.items[number] = false; // Just in case next line fails
      delete statechange.items[number];
      }
    }
  
  if ((statechange.checkcount >= 0) && (statechange.checkcount < 3)) {
    highlighter(statechange.items);
    }

  return true;
  }
//-->
</script>
<div id="page">
<?php
game_logo();
//FUTURE: Better way of detecting new users. -- COOKIE
$random_nom = random_name(rand(1000,1799));
$make_user_row = "INSERT INTO `alchemake`.`users` (`userid` ,`networkid` ,`network`,`nickname`) VALUES ('$userid', '$facebook_uid', 'facebook','$random_nom');";
$status = mysql_query($make_user_row,$mysql_link);
if ($status) {
  echo "Hello, welcome to Alchemake. Your inventory is below -- when you visit, you'll regularly receive new items to Alchemake with! Have fun.";
  ?><br />
  <a onclick="var attachment = {'name' : 'I became an Alchemist in Alchemake!',
  'caption':'{*actor*} has been accepted to the Alchemy guild as <?php echo $random_nom; ?>.',
  };
  var actionLinks = [{ 'text': 'Alchemake', 'href': 'http://apps.facebook.com/alchemake/'}];
  Facebook.streamPublish('',attachment,actionLinks);">Share my Nom de Alchemake</a>?<br />
  <?php
  add_items($userid,1,250);
  drop_items($userid);drop_items($userid);drop_items($userid);
  }
else {
  if ($fb) {
    $user_pic = $fb->api_client->users_getInfo($facebook_uid, 'pic_square');
    }
  else {
    $user_pic = array();
    $user_pic[0]['pic_square'] = '';
    }
  $userinfo_sql = "SELECT * FROM `users` WHERE `userid` = '$userid'";
  $userinfo_r = mysql_query($userinfo_sql,$mysql_link);
  $userinfo = mysql_fetch_assoc($userinfo_r);
  show_card($user_pic,$userinfo);
  $time_from_drop = time() - strtotime($userinfo['last_drop']);
  $time_from_ay = time() - strtotime($userinfo['last_allowence']);
  do_drops($time_from_ay,$time_from_drop);
  }

campaign_box();
?>

<?php
$inventory_table = get_inventory_table($userid);
show_inventory($inventory_table);
show_pending_trades();

echo "</div>"; //end page