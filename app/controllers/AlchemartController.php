<?php
class AlchemartController extends AlchemakeController {

  public function buyAction() {
    if ($this->userThatIsLoggedIn() !== FALSE) {
      $user = $this->userThatIsLoggedIn();
    }
    else {
      $this->dispatcher->forward(array('controller'=>'Users','action'=>'login'));
    }

    $start_buy_power = $user->getInventory('itemid = 1')[0]->qty;

    $posted_order = $this->request->getPost('order');

    if (!$posted_order) {
      return FALSE;
    }

    $order = [];
    foreach ($posted_order as $itemid => $itemqty) {
      $itemid = (int)$itemid;
      $itemqty = (int)$itemqty;
      if ($itemid !== 1) { //you can't buy or sell money at a ratio not 1:1
        $order[$itemid]['qty'] = $itemqty;
        if ($itemqty >= 0) {
          $order[$itemid]['price'] = 50;
        }
        else { //sell to the store
          $order[$itemid]['price'] = 40;
        }
      }
    }

    $total = 0;
    foreach ($order as $itemid => $instructions) {
      $total += $instructions['qty'] * $instructions['price'];
    }

    $new_buying_power = $start_buy_power - $total;

    if ($new_buying_power < 0) {
      $this->flashSession->notice("You do not have sufficent AY for this "
        . "transaction.");
      $this->dispatcher->forward(array('action'=>'index'));
    }
    else {
      $order[1] = 0 - $total;
      $resultset_query = ["itemid = 1"]; //special value for money
      foreach ($order as $itemid => $instructions) {
        $resultset_query[] = "itemid = $itemid";
      }
      $resultset_query = implode(' or ',$resultset_query);
      $inventory_to_update = $user->getInventory($resultset_query);

      foreach ($inventory_to_update as $line_to_update) {
        $line_to_update->qty += $order[$line_to_update->itemid]['qty'];
        $line_to_update->save();
      }
      $this->flashSession->notice("Success! Your inventory has been updated.");
      $this->flashSession->notice("You currently have AY $new_buying_power");
    }
  }

  public function indexAction() {

  }

}
