<?php

class Inventory extends Phalcon\Mvc\Model {

  public $userid;
  public $itemid;
  public $qty;

  public function initialize() {
    $this->belongsTo('userid','Users','userid');
    $this->hasOne('itemid','Items','itemid');
  }

  public static function addItems($userid,$itemno,$qty) {
    $inventory_line = Self::findFirst("userid = $userid and itemid = $itemno");
      if (!$inventory_line) {
          $inventory_line = new Self();
          $inventory_line->itemid = $itemno;
          $inventory_line->userid = $userid;
          $inventory_line->qty = $qty;
      }
      else {
          $inventory_line->qty += $qty; //note quantity can be negative
      }
      return $inventory_line->save();
  }
  
  public function inventoryDrop($userid) {
    $number_of_items = rand(2,4);
    $success_on_all = TRUE;
    for ($i = 0; $i < $number_of_items; $i++) {
      $itemno = Items::randomBasicItem()->itemid;
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
      $to_invent = self::addItems($to, $itemid, $qty);
      $from_invent = self::addItems($from, $itemid, 0 - $qty);
      if ((!$to_invent) || (!$from_invent)) {
        $status = FALSE;
      }
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
