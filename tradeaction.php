<?php
//updates the status of trade
//takes form $_GET['action'], $_GET['tradeid']
require_once('alchemake-inc.php');

context_setup();

function item_transfer($from,$to,$itemid,$qty) {
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

if (!time_nonce($_GET['time'],$_GET['nonce'])) {
  bounce_back("Nonce Mismatch",NONCEERR);
  }
?>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>/alchemake.css?v=<?php echo $version;?>" />
<div id="page">
<?
$_GET['tradeid'] = (int)$_GET['tradeid'];
$sql = "SELECT * FROM `trades` WHERE `tradeid` =  {$_GET['tradeid']}";
$sql_r = mysql_query($sql,$mysql_link);

if (mysql_num_rows($sql_r) == 0) {
  trigger_error("I can't find this trade in my records, please try again.<!--  {$_GET['tradeid']} -->",E_USER_ERROR);
  }

$trade = mysql_fetch_assoc($sql_r);

if ($trade['status'] != 'pending') {
  trigger_error("This trade is not pending, it is {$trade['status']}",E_USER_ERROR);
  }

// $user_role is either PROPOSED or PROPOSER
define('PROPOSED',1);
define('PROPOSER',2);
$user_role = 0;
if ($userid == $trade['proposer_userid']) {
  $user_role = ($user_role | PROPOSER);
  }
if ($userid == $trade['proposed_userid']) {
  $user_role = ($user_role | PROPOSED);
  }
if ($user_role == 0) {
  trigger_error("You are not a party to this trade. It was proposed by {$trade['proposer_userid']} to {$trade['proposed_userid']} (your id # is $userid)",E_USER_ERROR);
  }

if ($_GET['action'] == 'reject') {
  if ($user_role & PROPOSED) {
    $sql = "UPDATE `alchemake`.`trades` SET `status` = 'rejected' WHERE `trades`.`tradeid` ={$_GET['tradeid']} LIMIT 1 ;";
    $sql_r = mysql_query($sql,$mysql_link);
    if ($sql_r) {
      echo "Your request to reject the trade has been received.";
      }
    else {
      trigger_error("$general_oops -- Your request wasn't understood. If you get this message repeatedly, please let us know.",E_USER_ERROR);
      }
    }
  else {
    trigger_error("Only the person that has received this offer can reject it.",E_USER_ERROR);
    }
  }
elseif ($_GET['action'] == 'withdraw') {
  if ($user_role & PROPOSER) {
    $sql = "UPDATE `alchemake`.`trades` SET `status` = 'withdrawn' WHERE `trades`.`tradeid` ={$_GET['tradeid']} LIMIT 1 ;";
    $sql_r = mysql_query($sql,$mysql_link);
    if ($sql_r) {
      echo "Your request to withdraw the trade has been received, and will be processed within 5 minutes.";
      }
    else {
      trigger_error("$general_oops -- Your request wasn't understood. If you get this message repeatedly, please let us know.",E_USER_ERROR);
      }
    }
  else {
    trigger_error("Only the person that made this offer can reject it.",E_USER_ERROR);
    }
  }
elseif ($_GET['action'] == 'accept') {
    if ($user_role & PROPOSED) {
      $sql = "SELECT * FROM `tradedetails` WHERE `tradeid` = {$_GET['tradeid']}";
      $tradedetails_r = mysql_query($sql,$mysql_link);
      $tradedetails = set_of_rows_to_table($tradedetails_r);
      
      foreach ($tradedetails as $details) {
	if ($details['proposer_qty'] > 0) {
	  item_transfer($trade['proposer_userid'],$trade['proposed_userid'],$details['proposer_itemid'],$details['proposer_qty']);
	  }
	if ($details['proposed_qty'] > 0) {
	  item_transfer($trade['proposed_userid'],$trade['proposer_userid'],$details['proposed_itemid'],$details['proposed_qty']);
	  }
	}
      $sql = "UPDATE `alchemake`.`trades` SET `status` = 'complete' WHERE `trades`.`tradeid` ={$_GET['tradeid']} LIMIT 1 ;";
      $sql_r = mysql_query($sql,$mysql_link);
      if ($sql_r) {
	echo "Trade complete!";
	}
      else {
	trigger_error("<!--FAILED in tradeaction.php -->",E_USER_NOTICE);
	}
      }
  else {
    trigger_error("Only the person that has received this offer can accept it.",E_USER_ERROR);
    }
  }
//if ($_GET['action'] == 'counteroffer') {
// ADD PREFILL HERE
// }
return_button();
?>
</div>