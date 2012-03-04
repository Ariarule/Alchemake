<?php
//Create Trade Script
//Receives an array of the form
/*
$_POST['trade'] = array(
  'proposer' = array(
    userid = '...',
    items = array(
      $itemno => qty,
      $itemno => qty,
      )
    )
   'proposed' = array(
    userid = '...',
    items = array(
      $itemno => qty,
      $itemno => qty,
      )
    )
    )
*/
//require_once('alchemake-inc.php');
//context_setup();
//THIS FILE IS INVOKED FROM setuptrade.php

function is_logged_in_user_proposer($proposer) {
  global $userid;
  if ($proposer == $userid) {
    return true;
    }
  return false;
  }

function clean_trade_element(&$element,$key) {
  if ($key == 'userid')	{
    $element = mysql_real_escape_string($element);
    }
  elseif (is_array($element)) {
    //Should never happen, included as sanity check
    return TRUE;
    }
  else {
    $element = (int)$element;
    }
  }

function clean_trade($unclean_trade) {
  array_walk_recursive($unclean_trade,'clean_trade_element');
  
  if (!does_user_exist($unclean_trade['proposed']['userid'])) {
    trigger_error("Trading partner is not present in Alchemake. :(",E_USER_ERROR);
    }
  if (!does_user_exist($unclean_trade['proposer']['userid'])) {
    trigger_error("Trading partner is not present in Alchemake. :(",E_USER_ERROR);
    }
  if (!is_logged_in_user_proposer($unclean_trade['proposer']['userid'])) {
    trigger_error("Technical problem: cannot make trade. Sorry. :(",E_USER_ERROR);
    }

  return $unclean_trade; //the trade is now clean
  }

//NB: $trade = $_POST['trade'] in setuptrade.php
$trade = clean_trade($trade);

$inventory_table = get_inventory_table($userid);
$reformatted_inventory = array();
foreach ($inventory_table as $inventory_item) {
  $reformatted_inventory[$inventory_item['itemid']] = array ('name' => $inventory_item['name'],
      'description' => $inventory_item['description'],
      'available' => $inventory_item['available']);
  }
unset($inventory_table); //Not needed anymore, probably rather large

$trade_error = FALSE;
foreach($trade['proposer']['items'] as $itemid => $proposer_table_qty) {
  if (!isset($reformatted_inventory[$itemid]['available'])) {
    $reformatted_inventory[$itemid] = array();
    $reformatted_inventory[$itemid]['available'] = 0;
    }
  if ($proposer_table_qty > $reformatted_inventory[$itemid]['available']) {
    $trade_error = TRUE;
    echo "You have suggested trading {$proposer_table_qty}x {$reformatted_inventory[$itemid]['name']} but you have only {$reformatted_inventory[$itemid]['available']} available. <br />";
    }
  }
if ($trade_error) {
  trigger_error("Cannot suggest this trade -- it's not possible to trade a larger quantity of something than you currently have available.",E_USER_ERROR);
  }

$sql = "INSERT INTO `trades` (`proposer_userid`,`proposed_userid`,`status`) VALUES ('{$trade['proposer']['userid']}','{$trade['proposed']['userid']}','pending');";
$insert_link = mysql_query($sql,$mysql_link);
if ($insert_link) {
  $tradeid = mysql_insert_id($mysql_link);
  }
else {
  trigger_error($general_oops,E_USER_ERROR);
  //echo $sql . " gave " . mysql_error() . "\n";
  }

$trade_detail_rows = max(sizeof($trade['proposer']['items']),sizeof($trade['proposed']['items']));
$proposer_item_keys = array_keys($trade['proposer']['items']);
$proposed_item_keys = array_keys($trade['proposed']['items']);


for ($i = 0; $i < $trade_detail_rows; $i++) {
  if (isset($proposer_item_keys[$i])) {
    $proposer_item_qty = $trade['proposer']['items'][$proposer_item_keys[$i]];
    $proposer_item = $proposer_item_keys[$i];
    }
  else {
    $proposer_item_qty = '0';
    $proposer_item = '0';
    }
  if (isset($proposed_item_keys[$i])) {
    $proposed_item_qty = $trade['proposed']['items'][$proposed_item_keys[$i]];
    $proposed_item = $proposed_item_keys[$i];
    }
  else {
    $proposed_item_qty = '0';
    $proposed_item = '0';
    }
  //FUTURE: COMBINE INTO SINGLE QUERY!
  $sql = "INSERT INTO `alchemake`.`tradedetails` (`tradeid`, `proposer_itemid`, `proposer_qty`, `proposed_itemid`, `proposed_qty`) VALUES ('$tradeid', '$proposer_item', '$proposer_item_qty', '$proposed_item', '$proposed_item_qty');";
  $insert_link = mysql_query($sql,$mysql_link);
  }