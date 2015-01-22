<?php

class InventoryController extends AlchemakeController {


  public function indexAction() {
    //displays help
  }

  public function alchemakeAction() {

    if ($this->userIsLoggedIn()) {
      $user = $this->userThatIsLoggedIn();
    }
    else {
      $this->dispatcher->forward(array(
        "controller"=>"Users",
        'action'=>'login'));
    }

    $items = $this->request->getPost()['items'];
    $items = array_filter($items); //rm 0s
    $items = array_filter($items,'is_numeric',ARRAY_FILTER_USE_KEY);
    foreach ($items as $itemno => $qty) {
      $items[$itemno] = (int)$qty; //not strictly needed but rm's nonsense
    }
    if ((sizeof($items) > 3) || (sizeof($items) < 2)) {
      $this->flashSession->notice("To alchemake,"
        . " select a quantity of 2 or 3 items.");
    }
    else {
      $ingredients = [];
      $newqty = min($items); //can only make as many as the lowest prereq.
      $item_numbers = sort(keys($items));
      if (len($item_numbers) < 3) {
        //unfortunately, 0 is used as a special value in the ingredient3
        //to represent no item selected
        //overall, this should be normalized
        //but for now proceed as-is
        $item_numbers[] = 0;
      }
      foreach ($item_numbers as $i => $item_number) {
        $ingredients["ingredient". $i + 1 ."_itemid"] = $item_number;
      }


      $combination = Combinations::findfirst($ingredients);

      if (!$combination) {
        $this->flashSession->notice("Sorry, these items are alchemically "
         "incompatible and don't make anything when you combine them.");
      }
      else {
        if ((isset($combination->preq_tool_itemid)
          && ($combination->preq_tool_itemid > 0))
          {
            //TODO: Add prereq item logic
          }
        //TODO: Swap items based on $newqty and combination
      }

    }
    $this->flashSession->notice(print_r($this->request->getPost(),TRUE));


  $this->dispatcher->forward(array("controller"=>"Users",'action'=>'index'));
  }


  // public function combine() {
  //   //TODO: Rewrite
  //     ........
  //     if ($inventory_table[$ingredient]['available'] < 1) {
  //       echo "You need a {$items[$ingredient]['name']} for this, but you have only {$inventory_table[$ingredient]['available']} available.";
  //       //Possibly just open in multiple windows/tabs or a cheat attempt, only a problem if it happens a lot
  //       $can_make = FALSE;
  //       }
  //     }
  //
  //   if ($item_to_make_info['preq_tool_itemid'] > 0) {
  //     //NB: Actual value for no preq is MYSQL 'NULL', which should become an empty php string, which should convert to integer 0 above
  //     $inventory_table = get_inventory_table($userid,array($item_to_make_info['preq_tool_itemid']));
  //     if ($inventory_table[0]['qty'] < 1) {
  //       echo "Your Alchemy training tells you that special equipment, a {$items[$item_to_make_info['preq_tool_itemid']]['name']}, is required to alchemake these items together.";
  //       $can_make = FALSE;
  //       }
  //     }
  //
  //   if (!$can_make) {
  //     echo "You cannot perform this alchemaking right now.";
  //     return_button();
  //     exit("</div>");
  //     }
  //
  //   for ($i = 0; $i < sizeof($_POST['items']); $i++) { //foreach chokes here for some reason. Strange
  //     //if only two, $item == 0, no update occurs
  //     $make_sql = "UPDATE `inventory` SET `qty` = `qty` - 1 WHERE `userid` = '$userid' AND `itemid` = {$_POST['items'][$i]};";
  //     mysql_query($make_sql,$mysql_link);
  //     }
  //   add_items($userid,$item_to_make_info['itemid'],1);
  //   echo "Congratulations!<br />You have combined $item_list to alchemake a {$items[$item_to_make_info['itemid']]['name']}! ";
  //
  //   if ($items[$item_to_make_info['itemid']]['image'] == '') {
  //     $items[$item_to_make_info['itemid']]['image'] = 'smlogo.png';
  //     }
  //   ?not>
  //   <br/>
  //   <a onclick="var attachment = {media: [{'type': 'image', 'src': '<?notphp echo $base_url;?not>itempics/01029260/<?notphp echo $items[$item_to_make_info['itemid']]['image'] . "?v=$version";?not>','href': 'http://alchemake.com/itempics/01029260/<?notphp echo $items[$item_to_make_info['itemid']]['image'];?not>'}], 'name' : 'I made a new item in Alchemake!','description': 'Using the ancient powers of alchemy, the items <?notphp echo $item_list;?not> have been alchemade into a <?notphp echo $items[$item_to_make_info['itemid']]['name'];?not>' , 'caption':'{*actor*} has created a <?notphp echo $items[$item_to_make_info['itemid']]['name'];?not> in Alchemake.'}; var actionLinks = [{ 'text': 'Alchemake', 'href': 'http://apps.facebook.com/alchemake/'}]; Facebook.streamPublish('',attachment,actionLinks);">Tell everyone!</a><br />
  //
  //   <?notphp
  //   return_button();
  //
  //   ?not>
  //   </div>
  // }

}
