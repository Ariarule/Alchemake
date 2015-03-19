<?php
use Phalcon\Mvc\Model\Resultset;

class InventoryController extends AlchemakeController {

  public function indexAction() {
    //displays help
  }

  private function hasTool($user,$itemid) {
    $tool = Inventory::findFirst(['userid' => $user->userid,
        'itemid' => $itemid]);
    if (!$tool || $tool->qty < 1) {
        return FALSE;
    }
    return TRUE;
  }
  
  public function listUserInventoryAction($userid) {
      //this is fully public and without authentication on purpose
      $this->shallOutputJSON();
      $inventory_lister = Inventory::find(['userid' => $userid]);
      $inventory = [];
      if ($inventory_lister) {
      foreach ($inventory_lister as $inventory_line) {
                $outlist_line = [];
                $outlist_line['itemid'] = $inventory_line->itemid;
                $outlist_line['qty'] = $inventory_line->qty;
                $outlist_line['itemname'] = $inventory_line->items->name;
                $inventory[] = $outlist_line;
            }
        }
      echo json_encode($inventory);
  }
  
  public function alchemakeAction() {
        $user = $this->userThatIsLoggedIn();
        if(!$user) {
            $this->dispatcher->forward(array(
                "controller" => "Users",
                'action' => 'login'));
        }

        //TODO: Probably there's some duplicate logic here to recognize combos
        //with the ComboController. Check.
        $items = $this->request->getPost()['items'];
        $items = array_filter($items); //rm 0s
        $items = array_filter($items, 'is_numeric', ARRAY_FILTER_USE_KEY);
        foreach ($items as $itemno => $qty) {
            $items[$itemno] = (int) $qty; //not strictly needed but rm's nonsense
        }
        if ((sizeof($items) > 3) || (sizeof($items) < 2)) {
            $this->flashSession->notice("To alchemake,"
                    . " select a quantity of 2 or 3 items.");
        } else {
            $ingredients = [];
            $newqty = min($items); //can only make as many as the lowest prereq.
            $item_numbers = array_keys($items);
            sort($item_numbers);
            
            if (count($item_numbers) < 3) {
                //unfortunately, 0 is used as a special value in the ingredient3
                //to represent no item selected
                //overall, this should be normalized
                //but for now proceed as-is
                $item_numbers[] = 0;
            }
            foreach ($item_numbers as $i => $item_number) {
                $ingredients["ingredient" . ($i + 1) . "_itemid"]
                        = $item_number;
            }
            $combination = Combinations::findfirst($ingredients);

            if (!$combination) {
                $this->flashSession->notice("Sorry, these items are alchemically "
                        . "incompatible and don't make anything when you combine them.");
            } else {
                if ((isset($combination->preq_tool_itemid)) && ($combination->preq_tool_itemid > 0) && !$this->hasTool($user, $combination->preq_tool_itemid)) {
                    $this->flashSession->notice("Sorry, "
                            . "you are missing a tool required to combine"
                            . " these times.");
                } else {
                    //TODO: Swap items based on $newqty and combination
                    $ingredient_ids = []; 
                    $combo_array = (array)$combination;
                    foreach ([1,2,3] as $i) {
                        $ingredient_ids[] 
                                = $combo_array["ingredient{$i}_itemid"];
                    }
                    $ingredient_ids = array_values(array_filter($ingredient_ids));
                    $ingredient_inventory = [];
                    foreach ($ingredient_ids as $ingredient_id) {
                        $ingredient_inventory[] = Inventory::findFirst(
                                        "userid =  {$user->userid}   AND 
                                         itemid =   $ingredient_id   AND
                                         qty    >=  $newqty");
                    }
                    $ingredient_inventory = array_filter($ingredient_inventory);
                    if (count($ingredient_inventory) !== count($ingredient_ids)) {
                        $this->flashSession->notice("Sorry, "
                                . " you are missing an item required for this combo.");
                    } else {
                        $new_item = Inventory::findFirst(
                                "userid = {$user->userid} AND
                                 itemid = {$combination->itemid}");
                        if (!$new_item) {
                            $new_item = new Inventory;
                            $new_item->itemid = $combination->itemid;
                            $new_item->userid = $user->userid;
                            $new_item->qty = $newqty;
                        } else {
                            $new_item->qty += $newqty;
                        }
                        $new_item->save();
                        foreach ($ingredient_inventory as $ingredient_inventory_item) {
                            $ingredient_inventory_item->qty -= $newqty;
                            $ingredient_inventory_item->save();
                        }
                    }
                }
            }
        }
        $this->dispatcher->forward(array("controller"=>"Users",'action'=>'index'));
  }
}
