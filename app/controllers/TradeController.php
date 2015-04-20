<?php
class TradeController extends AlchemakeController {

    private function addDetailToTrade($direction,$itemid,$qty) {
        $trade_detail = new TradeDetails();
        $trade_detail->itemid = $itemid;
        $trade_detail->direction = $direction;
        $trade_detail->qty = $qty;
        return $trade_detail;
    }

    private function saveTradeDetails($item_details,$direction) {
        $trade_details = [];
        foreach ($item_details as $itemno => $qty) {
            $trade_details[] = $this->addDetailToTrade($direction,$itemno,$qty);
        }
        return $trade_details;
    }

    public function proposeAction() {
        $posted_trade = $this->request->getPost();
        $trade = new Trades();
        $proposer_user = $this->userThatIsLoggedIn();
        $trade->proposer_userid = $proposer_user->userid;
        $posted_trade['proposed'] = (int)$posted_trade['proposed'];


        $proposed_user = 
                $this->userLookupBy($posted_trade['proposed'], 'userid');
        if (!$proposed_user) {
            $this->flashSession->error("I couldn't find the player you were"
                ." suggesting this trade to");
            return FALSE;
        }

        $trade->proposed_userid = $proposed_user->userid;
        $proposed_items_info = Items::clean($posted_trade['asking_for']);
        $proposer_items_info = Items::clean($posted_trade['items']);

        $trade->status =  'pending';
        $shorts = Inventory::userQtyShorts($proposer_user,$proposer_items_info);
        if (count($shorts) > 0) {
            $this->flashSession->error("You don't have enough of an item"
                    . " to suggest this trade.");
            $this->dispatcher->forward(['controller'=>'users','action'=>'index']);
        }

        $trade_details = 
            array_merge($this->saveTradeDetails($proposed_items_info,'TO_PROPOSER'),
                    $this->saveTradeDetails($proposer_items_info,'FROM_PROPOSER'));
        $trade->tradeDetails = $trade_details;
        if (!$trade->save()) {
            foreach ($trade->getMessages() as $message) {
                $this->flashSession->error($message);
            }
        }
        else {
            $this->flashSession->notice("Your trade has been proposed");
        }
        $this->dispatcher->forward(['controller'=>'users','action'=>'index']);
    }

    public function confirmProposalAction() {
        $items = Items::clean($this->request->getQuery('items'));
        $proposed = $this->userLookupBy(
                $this->request->getQuery('proposed_userid','int'),
                'userid');
        $asking_for = Items::clean($this->request->getQuery('asking_for'));
        if (!$proposed) {
            //should not happen
            $this->flashSession->error("Sorry, I don't know the user you're "
                    . "trying to trade with.");
            $this->dispatcher->forward(['controller' => 'users',
                'action' => 'index']);
        }
        $this->view->setVar('items',$items);
        $this->view->setVar('proposed',$proposed);
        $this->view->setVar('asking_for',$asking_for);
    }

    public function setupProposalAction() {
        $items = Items::clean($this->request->getPost('items'));
        $this->view->setVar("items",$items);
    }

    private function delTrade($id_to_check,$new_status,
        $new_location = ['controller' => 'users','action' => 'index']) {
      $tradeid = $this->request->getPost('tradeid',"int");
      $trade = Trades::findFirst("tradeid = $tradeid");
      $trade_info = (array)$trade;
      if ($trade_info[$id_to_check] == $this->userThatIsLoggedIn()->userid
              && ($trade->status == 'pending')) {
                $trade->status = $new_status;
              }
      $status = $trade->save();
      if (!$status) {
          foreach ($trade->getMessages() as $message) {
              $this->flashSession->notice("$message");
            }
        }
       if ($new_location) {
           $this->dispatcher->forward($new_location);
       }
      }

    public function rejectAction() {
        $this->delTrade('proposed_userid','rejected');
    }

    public function withdrawAction() {
        $this->delTrade('proposer_userid','withdrawn');
    }

    public function counterofferAction() {
        $this->delTrade('proposed_userid', 'counteroffered');
        //TODO: Show a new trade form with the previous details like old game
        $this->flashSession->notice("Trade has been marked as to be "
                . "counteroffered. Remember to suggest a new trade.");
    }
    
    public function acceptAction() {
        $trade = Trades::findFirst($this->request->getPost('tradeid','int'));
        if (!$trade 
                || ($this->userThatIsLoggedIn()->userid 
                        !== $trade->proposed_userid)) {
            $this->flashSession->error("You cannot accept this trade");
        }
        else {
          $trade->status = 'completed';
          $proposer = $trade->proposer_userid;
          $proposed = $trade->proposed_userid;
          foreach ($trade->tradedetails as $trade_detail) {
              if ($trade_detail->direction == 'TO_PROPOSER') {
                  $to = $proposer; $from = $proposed;
              }
              else {
                  $to = $proposed; $from = $proposer;
              }
              Inventory::transferItem($from,
                      $to,
                      $trade_detail->itemid,
                      $trade_detail->qty);
          }
          $trade->save();
        }
      $this->dispatcher->forward(['controller' => 'users',
                'action' => 'index']);
      }
    }
