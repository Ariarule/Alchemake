<?php
class TradeController extends AlchemakeController {

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

private function addDetailToTrade($tradeid,$direction,$itemid,$qty) {
    $trade_detail = new TradeDetails();
    $trade_detail->tradeid = $tradeid;
    $trade_detail->itemid = $itemid;
    $trade_detail->direction = $direction;
    $trade_detail->qty = $qty;
    return $trade_detail->save();
}

private function saveTradeDetails($tradeid,$item_details,$direction) {
    foreach ($item_details as $itemno => $qty) {
        $this->addDetailToTrade($tradeid,$direction,$itemno,$qty);
    }
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
    
    $trade->save();
    $this->saveTradeDetails($trade->tradeid, $proposed_items_info,'TO_PROPOSER');
    $this->saveTradeDetails($trade->tradeid, $proposer_items_info,'FROM_PROPOSER');
}

private function delTrade($id_to_check,$new_status) {
  $tradeid = (int)$this->getPost('tradeid');
  $trade = Trades::findFirst('tradeid');
  $trade_info = (array)$trade;
  if ($trade_info[$id_to_check] == $this->userThatIsLoggedIn()->userid
          && ($trade->status == 'pending')) {
            $trade->status = $new_status;
          }
  return $trade->save();
  }

public function rejectAction() {
    return $this->delTrade('proposed_userid','rejected');
}

public function withdrawAction() {
    return $this->delTrade('proposer_userid','withdrawn')
}
    
public function acceptAction() {
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
