<?php

class Inventory extends Phalcon\Mvc\Model {

  public $userid;
  public $itemid;
  public $qty;

  public function initialize() {
    $this->belongsTo('userid','Users','userid');
    $this->hasOne('itemid','Items','itemid');
  }

  public function transfer($from,$to,$itemid,$qty) {
    //TODO: Write transfer function
  }

  public function inventory_drop($userid) {
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

  private static function proposedQtys($user) {
        $items = [];
        $proposed_trades = $user->getTrades("proposer_userid = {$user->userid}"
            . "and status='pending'");
        foreach ($proposed_trades as $trade) {
            foreach ($trade->trade_details as $trade_detail) {
                $items[$trade_detail->itemid] = $trade_detail->qty;
            }
        }
        return $items;
  }

  private static function tallyProposedInventory($user) {
        $net = [];
        $proposed_qtys = self::proposedQtys($user);
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

  public static function transferItem($from,$to,$itemid,$qty) {
      $from_invent = Self::findFirst("userid = $from and itemid = $itemid");
      $from_invent->qty -= $qty;
      $to_invent = Self::findFirst("userid = $to and itemid = $itemid");
      if (!$to_invent) {
          $to_invent = new Self();
          $to_invent->itemid = $itemid;
          $to_invent->userid = $to;
          $to_invent->qty = $qty;
      }
      else {
          $to_invent->qty += $qty;
      }
      $status = $to_invent->save();
      $status = $status && $from_invent->save();
      return [$status,$to_invent,$from_invent];
  }
  
  public static function userQtyShorts($user,$proposals) {
        $problems = [];
        $availabilities = self::tallyProposedInventory($user);
        foreach ($proposals as $itemid => $proposed_qty) {
                if (isset($availabilities[$itemid]) 
                    && ($availabilities[$itemid] > $proposed_qty)) {
                    $problems[$itemid] = array('proposed' => $proposed_qty
                                , 'available' => $availabilities[$itemid]);
                }
            }
            return $problems;
  }  
  
}
