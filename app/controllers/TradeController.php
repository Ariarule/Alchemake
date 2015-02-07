<?php
class TradeController extends AlchemakeController {

private function is_logged_in_user_proposer($proposer) {
  if ($proposer === $this->userThatIsLoggedIn()->userid) {
    return true;
    }
  return false;
  }
  
private function clean_trade($unclean_trade) {
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

 private function cleanItems($dirty_items) {
     $clean = [];
     foreach ($dirty_items as $item_id => $qty) {
         $clean[(int)$item_id] = (int)$qty;
     }
     return $clean;
 }

private function proposedQtys($user) {
    $items = [];
    $proposed_trades = $user->getTrades("proposer_userid = {$user->userid}"
        . "and status='pending'");
    foreach ($proposed_trades as $trade) {
        foreach ($trade->trade_details as $trade_detail) {
            $items[$trade_detail->itemid] = $trade_detail->qty;
        }
    }
}

private function compareProposedInventory($user) {
    $net = [];
    $proposed_qtys = $this->proposedQtys($user);
    foreach ($proposed_qtys as $itemid => $proposed_qty) {
        $inventory_line = $user->getInventory("itemid = $itemid");
        $inv_qty = 0;
        if ($inventory_line && isset($inventory_line[0])) {
            $inv_qty += $inventory_line[0]->qty;
        }
        $net[$itemid] = $inv_qty - $proposed_qty;
    }
    return $net;
}

private function userQtyShorts($user,$proposals) {
    $problems = [];
    $availabilities = $this->compareProposedInventory($user);
    foreach ($proposals as $itemid => $proposed_qty) {
        if ($availabilities[$itemid] > $proposed_qty) {
            $problems[$items] = 
                    array('proposed' => $proposed_qty
                        , 'available' => $availabilities[$itemid]);
        }
    }
    return $problems;
}

public function proposeAction() {
    $posted_trade = $this->request->getPost();
    $trade = new Trades();
    $trade->proposer_userid = $this->userThatIsLoggedIn()->userid;
    $posted_trade['proposed'] = (int)$posted_trade['proposed'];
    
    $proposed_user = $this->userLookupBy($posted_trade['proposed'], 'userid');
    if (!$proposed_user) {
        $this->flashSession->error("I couldn't find the player you were"
            ." suggesting this trade to");
        return FALSE;
    }
    $trade->proposed_userid = $posted_trade['proposed'];
        //valid userid, just used to lookup user
    $proposer_user = $this->userThatIsLoggedIn();
    
    $proposed_items_info = $this->cleanItems($posted_trade['proposed_items']);
    $proposer_items_info = $this->cleanItems($posted_trade['proposer_items']);

    $trade->status =  'pending';
    $shorts = $this->userQtyShorts($proposer_user,$proposer_items_info);
    if (count($shorts) > 0) {
        $this->flashSession->error("You don't have enough of an item to suggest"
                ." this trade.");
        $this->dispatcher->forward(array('controller'=>'Users',
            'action'=>'index'));
    }
    
}
    
    

    
}  




//  $trade_error = FALSE;
//  foreach($trade['proposer']['items'] as $itemid => $proposer_table_qty) {
//    if (!isset($reformatted_inventory[$itemid]['available'])) {
//      $reformatted_inventory[$itemid] = array();
//      $reformatted_inventory[$itemid]['available'] = 0;
//      }
//    if ($proposer_table_qty > $reformatted_inventory[$itemid]['available']) {
//      $trade_error = TRUE;
//      echo "You have suggested trading {$proposer_table_qty}x {$reformatted_inventory[$itemid]['name']} but you have only {$reformatted_inventory[$itemid]['available']} available. <br />";
//      }
//    }
//  if ($trade_error) {
//    trigger_error("Cannot suggest this trade -- it's not possible to trade a larger quantity of something than you currently have available.",E_USER_ERROR);
//    }
//
//  $sql = "INSERT INTO `trades` (`proposer_userid`,`proposed_userid`,`status`) VALUES ('{$trade['proposer']['userid']}','{$trade['proposed']['userid']}','pending');";
//  $insert_link = mysql_query($sql,$mysql_link);
//  if ($insert_link) {
//    $tradeid = mysql_insert_id($mysql_link);
//    }
//  else {
//    trigger_error($general_oops,E_USER_ERROR);
//    //echo $sql . " gave " . mysql_error() . "\n";
//    }
//
//  $trade_detail_rows = max(sizeof($trade['proposer']['items']),sizeof($trade['proposed']['items']));
//  $proposer_item_keys = array_keys($trade['proposer']['items']);
//  $proposed_item_keys = array_keys($trade['proposed']['items']);
//
//
//  for ($i = 0; $i < $trade_detail_rows; $i++) {
//    if (isset($proposer_item_keys[$i])) {
//      $proposer_item_qty = $trade['proposer']['items'][$proposer_item_keys[$i]];
//      $proposer_item = $proposer_item_keys[$i];
//      }
//    else {
//      $proposer_item_qty = '0';
//      $proposer_item = '0';
//      }
//    if (isset($proposed_item_keys[$i])) {
//      $proposed_item_qty = $trade['proposed']['items'][$proposed_item_keys[$i]];
//      $proposed_item = $proposed_item_keys[$i];
//      }
//    else {
//      $proposed_item_qty = '0';
//      $proposed_item = '0';
//      }
//    //FUTURE: COMBINE INTO SINGLE QUERY!
//    $sql = "INSERT INTO `alchemake`.`tradedetails` (`tradeid`, `proposer_itemid`, `proposer_qty`, `proposed_itemid`, `proposed_qty`) VALUES ('$tradeid', '$proposer_item', '$proposer_item_qty', '$proposed_item', '$proposed_item_qty');";
//    $insert_link = mysql_query($sql,$mysql_link);
//    }
//}

public function rejectAction() {
  //TODO: Rewrite
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

public function withdraw() {
  //TODO: Rewrite
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
public function accept() {
  //TODO: Rewrite
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


}
