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

    $posted_order = Items::clean($this->request->getPost('order') 
            ? $this->request->getPost('order')
            : []);
    //don't try to pass null to clean

    if ($posted_order) {
      $order = [];
      foreach ($posted_order as $itemid => $itemqty) {
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
        $this->dispatcher->forward(['action'=>'index']);
      }
      else {
        $resultset_query = ["itemid = 1"]; //special value for money
        foreach ($order as $itemid => $instructions) {
          $resultset_query[] = "itemid = $itemid";
        }
        $resultset_query = implode(' or ',$resultset_query);
        $inventory_to_update = $user->getInventory($resultset_query);

        $order[1] = array('qty' => 0 - $total);

        foreach ($inventory_to_update as $line_to_update) {
          $line_to_update->qty += $order[$line_to_update->itemid]['qty'];
          $line_to_update->save();
        }
        $this->flashSession->success("Success! Your inventory has been updated.");
        $this->flashSession->notice("You currently have AY $new_buying_power");
        }
    }
    $this->dispatcher->forward(array('action'=>'index'));
  }

  public function indexAction() {

  }
}
