<?php
class AlchemartController extends AlchemakeController {

  public function buyAction() {
    if ($this->userThatIsLoggedIn() !== FALSE) {
      $user = $this->userThatIsLoggedIn();
    }
    else {
      $this->dispatcher->forward(['controller'=>'Users','action'=>'login']);
    }

    $start_buy_power = $user->getInventory('itemid = 1')[0]->qty;

    $posted_order = array_filter(Items::clean($this->request->getPost('order') 
            ? $this->request->getPost('order')
            : [])); //don't try to pass null to clean

    if ($posted_order) {
      $order = [];
      foreach ($posted_order as $itemid => $itemqty) {
        if ($itemid === 1) { //you can't buy or sell money
          continue;
        }
        $order[$itemid]['qty'] = $itemqty;
        $order[$itemid]['price'] = 50;
        if ($itemqty < 0) { //sell to the store
          $order[$itemid]['price'] = 40;
          $order[$itemid]['qty'] = max([$order[$itemid]['qty'], 
                  0 - Inventory::userItemQty($user->userid, $itemid)]);
        }
      }
      array_filter($order, function ($a) {return $a['qty'];}); //rm zeroed lines

      $total = 0;
      foreach ($order as $itemid => $instructions) {
        $total -= $instructions['qty'] * $instructions['price'];
      }
      $new_buying_power = $start_buy_power + $total;

      if ($new_buying_power < 0) {
        $this->flashSession->notice("You do not have sufficent AY for this "
          . "transaction.");
        $this->dispatcher->forward(['action'=>'index']);
      }
      else {
        foreach ($order as $itemid => $instructions) {
          Inventory::addItems($user->userid, $itemid, $instructions['qty']);
        }        
        Inventory::addItems($user->userid, 1, $total);
        $this->flashSession->success("Success! Your inventory has been updated.");
        $this->flashSession->notice("You currently have AY $new_buying_power");
        }
    }
    $this->dispatcher->forward(array('action'=>'index'));
  }

  public function indexAction() {

  }
}
