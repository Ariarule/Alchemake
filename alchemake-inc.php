<?php
error_reporting(E_ALL);
$version = 'OSS_20120204'; //09312010
$alphamode = TRUE; //False for production

if ($alphamode) {
  $base_url = "http://alpha.alchemake.com/";
  }
else {
  $base_url = "http://alchemake.com/";
  }

require_once('facebook.php');
function gen_time_nonce($time) {
  $secret = "[...]";
  return md5($time . $secret);
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

function change_nom ($userid,$newnom) {
  global $mysql_link;
  $sql = "UPDATE `users` SET `nickname` = '$newnom' WHERE `users`.`userid` = '$userid' LIMIT 1;";
  $change_r = mysql_query($sql,$mysql_link);
  $number = mysql_affected_rows($mysql_link);
  
  if ($number == 1) {
    return TRUE;
    }
  else {
    return FALSE;
    }
  }

function does_user_exist($userid) {
  global $mysql_link;

  $userid = mysql_real_escape_string($userid); //FUTURE: Replace with robust check for valid userid. //Should do nothing
  $search_r = mysql_query("SELECT *  FROM `users` WHERE `userid` = '$userid';",$mysql_link);
  
  if ($search_r) {
    if (mysql_num_rows($search_r) > 0) {
      return TRUE;
      }
    }
  return FALSE;
  }

function return_button() {
  //FUTURE: Check context
  echo "<div id=\"returnbutton\"><a href=\"http://apps.facebook.com/alchemake/\">Return to Lab</a></div>";
  }

function possible_level_up($userid,$userrank) {
  if ($userrank > 8) {
    return FALSE;
    }

  $level_up = FALSE;
  if ($userrank < 17) {
    $rand = rand(1,10 + $userrank);
    if ($rand == 1) {
      $level_up = TRUE;
      }
    }
  //FUTURE: Finish me! Call me!
  }

function time_nonce($time,$nonce) {
  $secret = "[...]"; //must match gen_time_nonce
  if ($nonce !== md5($time . $secret)) {
    return FALSE;
    }
  if ((time() - $time) > 36000) {
    return FALSE;
    }
  return TRUE;
  }

function ay_per_dollar() {
  global $mysql_link;
  static $rate = NULL;

  if ($rate !== NULL) {
    return $rate;
    }

  $sql = "SELECT `price_100ay` FROM `ay_price_history` WHERE `current` = 1 LIMIT 1";
  $sql_r = mysql_query($sql,$mysql_link);
  $rate_arr = mysql_fetch_assoc($sql_r);
  $rate = 100/$rate_arr['price_100ay'];
  return $rate;
  }

function get_allowence() {
  global $mysql_link;
  $sql = "SELECT `allowence` FROM `ay_price_history` WHERE `current` = 1 LIMIT 1";
  $sql_r = mysql_query($sql,$mysql_link);
  $allowence = mysql_fetch_assoc($sql_r);
  return $allowence['allowence'];
  }

function add_items($to,$itemid,$qty) {
    global $mysql_link;
    $sql = "INSERT INTO `inventory` ( `userid` , `itemid` , `qty` ) VALUES ('$to', $itemid, $qty) ON DUPLICATE KEY UPDATE `qty` = `qty` + $qty;"; //c+p from combine.php -- should be function.
    $sql_r = mysql_query($sql,$mysql_link);
    if (!$sql_r) {
      trigger_error("Query failed in add_items <!--$sql-->",E_USER_NOTICE);
      }
    return $sql_r;
    }

function rm_item($from,$itemid,$qty) {
  global $mysql_link;
  $sql = "UPDATE `alchemake`.`inventory` SET `qty` = `qty` - $qty WHERE `inventory`.`userid` = '$from' AND `inventory`.`itemid` = $itemid LIMIT 1;";
  $sql_r = mysql_query($sql,$mysql_link);
  return $sql_r;
  }

function drop_items($userid) {
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

function parse_trade_table($old_trade_table) {
  //mysql returns a row for each line in tradedetails -- this combines them into a subarray
  $new_trade_table = array();
  foreach ($old_trade_table as $trade_row) {
    if (!isset($new_trade_table[$trade_row['tradeid']])) {
      $new_trade_table[$trade_row['tradeid']] = array(
	    'proposer_table' => array($trade_row['proposer_itemid'] => $trade_row['proposer_qty']), 
	    'proposed_table' => array($trade_row['proposed_itemid'] => $trade_row['proposed_qty']),
            'proposer_userid' => $trade_row['proposer_userid'],
            'proposed_userid' => $trade_row['proposed_userid'],
            'status' => $trade_row['status'],
            'networkid' => $trade_row['networkid'],
            'network' => $trade_row['network'],
            'nickname' => $trade_row['nickname'],
	    );
      }
      else {
	//this will usually give notices about undefined indexes; the default behavior is fine.
	@$new_trade_table[$trade_row['tradeid']]['proposer_table'][$trade_row['proposer_itemid']] += $trade_row['proposer_qty'];
	@$new_trade_table[$trade_row['tradeid']]['proposed_table'][$trade_row['proposed_itemid']] += $trade_row['proposed_qty'];
	}
      unset($new_trade_table[$trade_row['tradeid']]['proposer_table'][0]); //There is no item 0, Artifact of NULL proposal.
      unset($new_trade_table[$trade_row['tradeid']]['proposed_table'][0]);
      }
  return $new_trade_table;
  }

function set_of_rows_to_table($query_link) {
  //should mod this to accept a callback that can operate on things as they come up instead
  $return_array = array();

  if (!$query_link) {
    //no need to go further, something's not right, probably sent an empty result set
    return $return_array;
    }

  while ($c_element = mysql_fetch_assoc($query_link)) {
    $return_array[] = $c_element;
    }
  return $return_array;
  }

function get_userid_from_nick($userid) {
  global $mysql_link;

  $userid = trim($userid);
  $userid = mysql_real_escape_string($userid); //TODO: Replace with alphanum restriction
  $sql = "SELECT `userid` FROM `users` WHERE `nickname` = '$userid' LIMIT 1;";
  $sql_r = mysql_query($sql,$mysql_link);
  if ((!$sql_r) || (mysql_num_rows($sql_r) == 0)) {
    return FALSE;
    }
  $nom = mysql_fetch_assoc($sql_r);
  return $nom['userid'];
  }

function get_inventory_table($userid,$items = NULL,$nickmode = FALSE) {
  //items is an optional array of items that we're interested in knowing the status of
  //returns array
  global $mysql_link;

  if ($nickmode) {
    $userid = get_userid_from_nick($userid);
    if ($userid === FALSE) {
      return FALSE;
      }
    }
  
  $sql = "SELECT `inventory`.`itemid`,`image`,`qty`,`name`,`description`,`sum_proposer_qty`
  FROM `inventory`
  JOIN (
  `items`
  ) ON ( `items`.`itemid` = `inventory`.`itemid` )
  LEFT JOIN (

  SELECT `tradedetails`.`proposer_itemid` , `tradedetails`.`proposer_qty` , `tradedetails`.`proposed_itemid` , `tradedetails`.`proposed_qty` , `trades` . * , SUM( `proposer_qty` ) AS `sum_proposer_qty`
  FROM `tradedetails`
  JOIN `trades` ON ( `tradedetails`.`tradeid` = `trades`.`tradeid` )
  WHERE `status` = 'pending' AND `proposer_userid` = '$userid'
  GROUP BY proposer_itemid
  ) AS `pendingtable` ON (`pendingtable`.`proposer_itemid` = `inventory`.`itemid` )";
  $sql .= " WHERE `inventory`.`userid` = '$userid' ";

  if (is_array($items)) {
    $lookonly_clause = array();
    foreach ($items as $item) {
      $item = (int)$item; //should do nothing, just to be safe
      $lookonly_clause[] = " `inventory`.`itemid` = $item";
      }
    $lookonly_clause = implode(' OR ',$lookonly_clause);
    $sql .= "AND ($lookonly_clause)";
    }
  $sql .= ";"; //sic
  $inventory_r = mysql_query($sql,$mysql_link);
  if ($inventory_r) {
    $inventory_table = set_of_rows_to_table($inventory_r);
    }
  else {
    $inventory_table = array();
    }
  $cleaned_table = array();
  foreach ($inventory_table as $key => $item) {
    //Better way to do this? Kludge.
    $cleaned_table[$item['itemid']] = $item;
    $cleaned_table[$item['itemid']]['available'] = $cleaned_table[$item['itemid']]['qty'] - $cleaned_table[$item['itemid']]['sum_proposer_qty'];
    unset($inventory_table[$key]);
    }
  return $cleaned_table;
  }

function loaditems(&$items) {
  global $mysql_link;
  //UGLY HACK: Loads all item names into memory for trade tables. Should really be done in MySQL with multiple joins
  //OTOH: Can use for real-time checking of possible combinations, etc.
  $sql = "SELECT * FROM `items`;";
  $items_r = mysql_query($sql,$mysql_link);
  while ($c_item = mysql_fetch_assoc($items_r)) {
    $items[$c_item['itemid']] = $c_item;
    }
  }

//MESSAGES HERE
$general_oops = "We encountered a technical problem with our server. We're sorry. :( <br />";
$nofile = $general_oops . "File not found. <br />";
$pending_trades_header = "<h1>Your pending trades: </h1>";
$rank_texts = array("Trainee","Alchemist 3rd","Alchemist 2nd","Alchemist 1st","Master Alchemist 3rd","Master Alchemist 2nd","Master Alchemist 1st","Immortal Alchemist");
//CONSTANTS HERE
$universal_salt = 'ALCHEMAKE[...]';
$facebook_salt = "fb_[...]";
define('FACEBOOK',1);

function userhash($netid, $network = FACEBOOK) {
  global $facebook_salt;
  global $universal_salt;

  //FUTURE: Implement different networks
  return sha1($universal_salt.$facebook_salt.$netid);
  }

//CONTEXT SETUP
function context_setup($userless = FALSE, $itemless = FALSE) {
    global $gen_time; global $gen_nonce; global $mysql_link; global $items;
    global $universal_salt; global $facebook_salt; global $userid;
    global $facebook_uid; global $fb;

    $gen_time = time(); //NB: ALSO USED FOR SOCIALGOLD IFRAME!!!!
    $gen_nonce = gen_time_nonce($gen_time);

    //if (TRUE) {
    if (isset($_COOKIE['alchemode']) && ($_COOKIE['alchemode'] == 'local')) {
      $database = 'alchemake';
      $host = 'localhost';
      $user = '...';
      $pw = '...';
      }
    else {
      $host = '...';
      $database = 'alchemake';
      $user = '...';
      $pw = '...';
      }
  
  $mysql_link = mysql_connect($host,$user,$pw);
  mysql_select_db($database,$mysql_link);
  
  if(!$userless) {
    $fb = new Facebook('...','...');
    $facebook_uid = $fb->require_login();
    //$facebook_uid = "9";
    $userid = userhash($facebook_uid);
    }
  if(!$itemless) {
    $items = array();
    loaditems($items);
    }
  }

define("NONCEERR",0);
function bounce_back($reason = NULL, $numberic_reason = NULL) {
  $bounce_url = "http://apps.facebook.com/alchemake/"; //should be ABSOLUTE URL
  
  if ($numberic_reason === NONCEERR) {
    ?><h1>Have you been away from your computer for a while?</h1>
    Alchemake stops working if you leave your browser open for a long time without doing anything &ndash; this prevents other people from stealing your items, or otherwise 
    pretending to be you.
    No problem! Return to the lab, and start playing again.<br>
    <?php
    return_button();
    ?>
    If you get this message repeatedly, or under strange circumstances, it might be a bug. Let us know so we can fix it!
    <?php
    exit();
    }
  else {
    echo $general_oops;
    }
  if ($reason !== NULL) {
    echo "\n$reason\n";
    }
  exit();
  }

function does_user_exist_by_nom($nom) {
  global $mysql_link;

  $sql = "SELECT * FROM `users` WHERE `nickname` = '$nom' LIMIT 1";
  $query_r = mysql_query($sql,$mysql_link);
  if ($query_r) {
    return mysql_num_rows($query_r);
    }
  else {
    return $query_r;
    }
  }

function linebg($reset = FALSE) {
  static $i = 1;
  if ($reset) {
    $i = 1;
    }
  //returns a string intended to inserted as a style for a tr element
  $shadetext = '';
  if ($i % 2) {
    $shadetext = 'style="background-image:url(\'lineshade.png\')"';
    }
  $i++;
  return $shadetext;
  }

function error_handler($errno,$errstr,$errfile,$errline) {
  global $general_oops;
  switch($errno) {
  case E_STRICT:
    //who cares?
    return TRUE;
  case E_USER_ERROR:
    //mail('crowley[at]alchemake.com','A user error occurred',"The notice occurred in $errfile and was $errstr");
    echo "<div class='usernotice'>$errstr</div>";
    return_button();
    exit('</div>'); //end <div id="page"> in most cases
    break;
  case E_NOTICE:
    //who cares?
    //NB: If changing this, check for error reporting off, as index.php pending trade tables will break otherwise.
    return TRUE;
  default:
    echo "<div class='error'>$general_oops</div>";
    $errmsg = "Error $errno -- $errstr occurred at $errfile -- $errline\n"
      . " POST was " . print_r($_POST,TRUE) . "\n\n GET was " . print_r($_GET,TRUE) . "\n";
    //mail('crowley[at]alchemake.com',"AN ERROR OCCURED IN $errfile",$errmsg);
    return_button();
    exit('</div>'); //end <div id="page"> in most cases
    }
  }

set_error_handler('error_handler');