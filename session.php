<?php
class session {
  public function __construct($headline,$text,$required,$gives,$next,$questmsg = NULL,$questvar = NULL) {
    //required, gives should be an array of 'itemid', 'qty' pairs
    $this->headline = $headline;
    $this->text = $text;
    $this->required = $required;
    $this->gives = $gives;
    $this->next = $next; //reset should be special
    $this->questmsg = $questmsg;
    $this->questvar = $questvar;
    }

  private function reset_camp() {
    //TODO
    }

  public function  change_next($new_next) {
    $this->next = $new_next;
    }

  public function quest_vars() {
    //returns array of quest vars
    //TODO: write me
    }

  public function has_required_items() {
    global $userid;
    $has_required_items = TRUE;
    $required_list = array();
    foreach ($this->required as $required) {
      $required_list[] = $required['itemid'];
      }
    unset($required);
    $inventory_selection = get_inventory_table($userid,$required_list);
    foreach ($this->required as $required) {
      if((!isset($inventory_selection[$required['itemid']])) || ($inventory_selection[$required['itemid']]['available'] < $required['itemid'])) {
	$has_required_items = FALSE;
	}
      }
    return $has_required_items;
    }

  public function show() {
    //TODO: check for "reset" for next
    global $userid;
    global $gen_time;
    global $gen_nonce;

    echo "<div class=\"campaignbox\"><h1>{$this->headline}</h1><div>{$this->text}</div>";
    
    if ($this->has_required_items()) { 
      foreach ($next as $number => $next_text) {
	echo "<a class=\"nextposs\" href=\"sessions.php?next=$number&time=$gen_time&nonce=$gen_nonce\">Next&nbsp;{$next_text}&rarr;</a>"; //check href for correct absolute location, add time nonce
	//remember to do server side check in loadsess.php to make sure player CAN load session
	}
      }
    else {
      echo "<span class=\"disabled-next\">Next&nbsp;{$next_text}&ndash;Items are still required.&rarr;</span>";
      }
    echo "</div>"; //campaignbox
    }

  public function do_action($action = 0) {
    global $userid,$mysql_link;
    if (!$this->has_required_items()) {
      echo "<div class=\"noticebox\">You do not yet have all the items required to proceed.</div>";
      $this->show();
      return FALSE;
      }
    else {
      foreach ($this->gives as $given) {
	add_items($userid,$given['itemid'],$given['qty']);
	}
      foreach ($this->required as $required) {
	rm_item($userid,$required['itemid'],$required['qty']);
	}
      $sql = "UPDATE `users` SET 'session' = {$next[$action]} WHERE `userid` = '$userid' LIMIT 1;";
      $sql_r = mysql_query($sql,$mysql_link);
      if (!$sql_r) {
	trigger_error("Technical problem: action failed. :( <!--$sql-->",E_USER_NOTICE);
	}
      }
    }
  }
