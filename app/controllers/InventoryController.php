<?php

class InventoryController extends AlchemakeController {

  public function indexAction($userid) {
    //displays the player's inventory
    $userid_is_logged_in = $this->useridIsLoggedIn($userid);

  }

  protected function inventory_drop($userid) {
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

  protected function rm_inventory_item($from,$itemid,$qty) {
    global $mysql_link;
    $sql = "UPDATE `alchemake`.`inventory` SET `qty` = `qty` - $qty WHERE `inventory`.`userid` = '$from' AND `inventory`.`itemid` = $itemid LIMIT 1;";
    $sql_r = mysql_query($sql,$mysql_link);
    return $sql_r;
  }

  public function combine() {
    //TODO: Rewrite
    //this method accepts item ids via POST and replaces the player inventory via the 'combinations' table

    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>alchemake.css?v=<?php echo $version;?>" />
    <div id="page">

    <?php
    if ((sizeof($_POST['items']) > 3) || (sizeof($_POST['items']) < 2)) {
      ?>
      <h1>How to Alchemake</h1>
      <ul>
      <li>Select two or three items in your inventory. Each time you select an item, possible combinations will be highlighted as a hint after just a moment, so don't worry if you don't know them all, or if there's a new one you don't know about yet.</li>
      <li>Click the Alchemake button to confirm your choice. An item will be created from the ones you selected, and placed in your inventory!</li>
      <li>What happens if you select two or three items that don't Alchemake into anything? Try it and see!</li>
      <li>This help message appears when you select less than 2 or more than three items.</li>
      </ul>

      <?php
      return_button();
      exit("</div>"); //end page
      }

    //First, sort the item ids so they're in accending order, lowest to highest
    foreach ($_POST['items'] as &$item) {
      $item = (int)$item; //only integers allowed
      if ($item == 1) {
        echo "The Yek is a completely inert item. It cannot be Alchemade with anything else";
        return_button();
        exit('</div>');
        }
      elseif ($item < 2) {
        trigger_error("Item ID of $item given",E_USER_NOTICE);
        }
      }
    sort($_POST['items']);

    if (sizeof($_POST['items']) == 2) {
      $_POST['items'][2] = 0;
      }

    $find_item_sql = "SELECT * FROM `combinations` WHERE `ingredient1_itemid` = {$_POST['items'][0]} AND `ingredient2_itemid` = {$_POST['items'][1]} AND `ingredient3_itemid` = {$_POST['items'][2]}";
    $item_to_make_r = mysql_query($find_item_sql,$mysql_link);
    $item_found = mysql_num_rows($item_to_make_r);

    $item_list = array();
    foreach ($_POST['items'] as $itemno) {
      if ($itemno > 0) {
        $item_list[] = $items[$itemno]['name'];
        }
      }
    $item_list[sizeof($item_list) - 1] = "and " . $item_list[sizeof($item_list) - 1];
    $glue = " ";
    if (sizeof($item_list) > 2) {
      $glue = ", ";
      }
    $item_list = implode($glue,$item_list);

    if(!$item_found) {
      echo "This combination of $item_list does not Alchemake anything -- yet! Submit a suggestion to the Alchemy guild! If it's used, you'll be able to combine the items you want, and will also receive a gift in your Alchemake inventory (if you are the first person to make that suggestion).";
      ?>
      <form method="POST" action="combo-suggest.php">

      <input type="hidden" name="items[]" value="<?php echo $_POST['items'][0];?>">
      <input type="hidden" name="items[]" value="<?php echo $_POST['items'][1];?>">
      <input type="hidden" name="items[]" value="<?php echo $_POST['items'][2];?>">
      <!-- onclick="this.value.replace('This combination should make a...','');" -->
      <input type="text" name="suggestion" value="This combination should make a..." onclick="if(this.value == 'This combination should make a...') {this.value = '';}" >
      <button type="submit">Suggest Alchemake Combination</button>
      </form>
      <?php
      return_button();
      exit('</div>');
      }

    $item_to_make_info = mysql_fetch_assoc($item_to_make_r);
    //check inventory to see if player has the items in question AVAILABLE
    $inventory_table = get_inventory_table($userid,$_POST['items']);

    $can_make = TRUE;
    foreach ($inventory_table as $inventory_row) {
      $inventory_row['available'] = $inventory_row['qty'] - (int)$inventory_row['sum_proposer_qty'];
      }


    foreach ($_POST['items'] as &$ingredient) {
      if ($ingredient == 0) {
        continue;
        }
      $ingredient = (int)$ingredient;

      if ($inventory_table[$ingredient]['available'] < 1) {
        echo "You need a {$items[$ingredient]['name']} for this, but you have only {$inventory_table[$ingredient]['available']} available.";
        //Possibly just open in multiple windows/tabs or a cheat attempt, only a problem if it happens a lot
        $can_make = FALSE;
        }
      }

    if ($item_to_make_info['preq_tool_itemid'] > 0) {
      //NB: Actual value for no preq is MYSQL 'NULL', which should become an empty php string, which should convert to integer 0 above
      $inventory_table = get_inventory_table($userid,array($item_to_make_info['preq_tool_itemid']));
      if ($inventory_table[0]['qty'] < 1) {
        echo "Your Alchemy training tells you that special equipment, a {$items[$item_to_make_info['preq_tool_itemid']]['name']}, is required to alchemake these items together.";
        $can_make = FALSE;
        }
      }

    if (!$can_make) {
      echo "You cannot perform this alchemaking right now.";
      return_button();
      exit("</div>");
      }

    for ($i = 0; $i < sizeof($_POST['items']); $i++) { //foreach chokes here for some reason. Strange
      //if only two, $item == 0, no update occurs
      $make_sql = "UPDATE `inventory` SET `qty` = `qty` - 1 WHERE `userid` = '$userid' AND `itemid` = {$_POST['items'][$i]};";
      mysql_query($make_sql,$mysql_link);
      }
    add_items($userid,$item_to_make_info['itemid'],1);
    echo "Congratulations!<br />You have combined $item_list to alchemake a {$items[$item_to_make_info['itemid']]['name']}! ";

    if ($items[$item_to_make_info['itemid']]['image'] == '') {
      $items[$item_to_make_info['itemid']]['image'] = 'smlogo.png';
      }
    ?>
    <br/>
    <a onclick="var attachment = {media: [{'type': 'image', 'src': '<?php echo $base_url;?>itempics/01029260/<?php echo $items[$item_to_make_info['itemid']]['image'] . "?v=$version";?>','href': 'http://alchemake.com/itempics/01029260/<?php echo $items[$item_to_make_info['itemid']]['image'];?>'}], 'name' : 'I made a new item in Alchemake!','description': 'Using the ancient powers of alchemy, the items <?php echo $item_list;?> have been alchemade into a <?php echo $items[$item_to_make_info['itemid']]['name'];?>' , 'caption':'{*actor*} has created a <?php echo $items[$item_to_make_info['itemid']]['name'];?> in Alchemake.'}; var actionLinks = [{ 'text': 'Alchemake', 'href': 'http://apps.facebook.com/alchemake/'}]; Facebook.streamPublish('',attachment,actionLinks);">Tell everyone!</a><br />

    <?php
    return_button();

    ?>
    </div>
  }
}
