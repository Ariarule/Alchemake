<?php
class TradeController extends AlchemakeController {

    private function cleanItems($dirty_items) {
        //TODO: Fetch from the db a valid list of item ids to check
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
        return $items;
    }

    private function tallyProposedInventory($user) {
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
        $availabilities = $this->tallyProposedInventory($user);
        foreach ($proposals as $itemid => $proposed_qty) {
                if (isset($availabilities[$itemid]) 
                    && ($availabilities[$itemid] > $proposed_qty)) {
                    $problems[$itemid] = array('proposed' => $proposed_qty
                                , 'available' => $availabilities[$itemid]);
                }
            }
            return $problems;
    }

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
        $proposed_items_info = $this->cleanItems($posted_trade['asking_for']);
        $proposer_items_info = $this->cleanItems($posted_trade['items']);

        $trade->status =  'pending';
        $shorts = $this->userQtyShorts($proposer_user,$proposer_items_info);
        if (count($shorts) > 0) {
            $this->flashSession->error("You don't have enough of an item to suggest"
                    ." this trade.");
            $this->dispatcher->forward(array('controller'=>'Users',
                'action'=>'index'));
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
        $items = $this->cleanItems($this->request->getQuery('items'));
        $proposed = $this->userLookupBy(
                $this->request->getQuery('proposed_userid','int'),
                'userid');
        $asking_for = $this->cleanItems($this->request->getQuery('asking_for'));
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
        $items = $this->cleanItems($this->request->getPost('items'));
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
        $this->delTrade('proposed_userid', 'counteroffered',
            ["action" => 'propose']);
    }

    private function transferItem($from,$to,$itemid,$qty) {
        $from_invent = Inventory::findFirst("userid = $from and itemid = $itemid");
        $from_invent->qty -= $qty;
        $to_invent = Inventory::findFirst("userid = $to and itemid = $itemid");
        if (!to_invent) {
            $to_invent = new Inventory();
            $to_invent->itemid = $itemid;
            $to_invent->userid = $to;
            $to_invent->qty = $qty;
        }
        else {
            $to_invent->qty += $qty;
        }
        $to_invent->save();
        $from_invent->save();
    }

    public function acceptAction() {
        $trade = Trades::findFirst("" . (int)$this->getPost('tradeid'));
        if (!$trade || $this->userThatIsLoggedIn() !== $trade->proposed_userid) {
            $this->flashSession->error("You cannot accept this trade");
            $this->dispatcher->forward(array('controller' => 'users',
                'action' => 'index'));
        }
        $trade->status = 'completed';
        $proposer = $trade->proposer_userid;
        $proposed = $trade->proposed_userid;
        foreach ($trade->tradedetails as $trade_detail) {
            if ($trade_detail->direction = 'TO_PROPOSER') {
                $to = $proposer; $from = $proposed;
            }
            else {
                $to = $proposed; $from = $proposer;
            }
            $this->transferItem($from,
                    $to,
                    $trade_detail->itemid,
                    $trade_detail->qty);
        }
        $trade->save();
      }
    }
