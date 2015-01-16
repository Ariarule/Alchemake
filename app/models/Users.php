<?php

use Phalcon\Mvc\Model\Validator\Email,
    Phalcon\Mvc\Model\Validator\PresenceOf;

class Users extends Phalcon\Mvc\Model {

  public $userid;
  public $networkid;
  public $nickname;
  public $rank;
  public $last_drop;
  public $last_allowence;
  public $main_order;
  public $emailaddress;

  protected $networkcredential;

  public function initialize() {
    $this->skipAttributes(['userid',
                           'emailaddress',
                           'rank',
                           'last_drop',
                           'last_allowence',
                           'main_order']);
    $this->hasMany('userid','Inventory','userid');
    $this->hasMany('userid','Trades','proposer_userid');
    $this->hasMany('userid','Trades','proposed_userid');
    }

  public function setNetworkcredential($plaintext) {
    if (strlen($plaintext) >= 8) {
      $this->networkcredential = password_hash($plaintext,PASSWORD_DEFAULT);
    }
     else {
       $this->networkcredential = ''; //caught by model validation below to show
                                      //correct message
    }
  }

  public function checkNetworkcredential($plaintext) {
    return password_verify($plaintext,$this->networkcredential);
  }

  public function getNetworkcredential() {
    trigger_error("get",E_USER_ERROR);
    if (isset($this->networkcredential)
    &&  (len($this->networkcredential) > 0)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function validation() {
    $this->validate(new Email(array('field'   => 'emailaddress',
      'message' => 'Invalid email address.')));
    //FIXME: networkcredential not working for new signups
    //$this->validate(new PresenseOf(array('field' => 'networkcredential',
    //  'message' => 'A password or equivalent login credential is required.')));
    return $this->validationHasFailed() !== TRUE;
  }

  private function giverUpdate($field) {
    $field = 'NOW()';
    $this->save();
  }

  public function giveItems() {
    //TODO: Write giveItems
    $this->giverUpdate($this->last_drop);
    return TRUE; //TODO: Only return true if actually added items to inventory
  }

  public function giveAllowence($value = 100) {
    //TODO: Write giveAllowence
    $this->giverUpdate($this->last_allowence);
    return $value;
  }

/*

  private function get_inventory_table($userid,$items = NULL,$nickmode = FALSE) {
    //items is an optional array of items that we're interested in knowing the status of
    //returns array
    global $mysql_link;

    if ($nickmode) {
      $userid = get_userid_from_nick($userid);
      if ($userid === FALSE) {
        return FALSE;
        }
      }

    $sql = "SELECT `inventory`.`itemid`,`image`,`qty`,`name`,`description`,`sum_proposer_qty`
    FROM `inventory`
    JOIN (
    `items`
    ) ON ( `items`.`itemid` = `inventory`.`itemid` )
    LEFT JOIN (

    SELECT `tradedetails`.`proposer_itemid` , `tradedetails`.`proposer_qty` , `tradedetails`.`proposed_itemid` , `tradedetails`.`proposed_qty` , `trades` . * , SUM( `proposer_qty` ) AS `sum_proposer_qty`
    FROM `tradedetails`
    JOIN `trades` ON ( `tradedetails`.`tradeid` = `trades`.`tradeid` )
    WHERE `status` = 'pending' AND `proposer_userid` = '$userid'
    GROUP BY proposer_itemid
    ) AS `pendingtable` ON (`pendingtable`.`proposer_itemid` = `inventory`.`itemid` )";
    $sql .= " WHERE `inventory`.`userid` = '$userid' ";

    if (is_array($items)) {
      $lookonly_clause = array();
      foreach ($items as $item) {
        $item = (int)$item; //should do nothing, just to be safe
        $lookonly_clause[] = " `inventory`.`itemid` = $item";
        }
      $lookonly_clause = implode(' OR ',$lookonly_clause);
      $sql .= "AND ($lookonly_clause)";
      }
    $sql .= ";"; //sic
    $inventory_r = mysql_query($sql,$mysql_link);
    if ($inventory_r) {
      $inventory_table = set_of_rows_to_table($inventory_r);
      }
    else {
      $inventory_table = array();
      }
    $cleaned_table = array();
    foreach ($inventory_table as $key => $item) {
      //Better way to do this? Kludge.
      $cleaned_table[$item['itemid']] = $item;
      $cleaned_table[$item['itemid']]['available'] = $cleaned_table[$item['itemid']]['qty'] - $cleaned_table[$item['itemid']]['sum_proposer_qty'];
      unset($inventory_table[$key]);
      }
    return $cleaned_table;
  }

    protected function add_items($to,$itemid,$qty) {
      global $mysql_link;
      $sql = "INSERT INTO `inventory` ( `userid` , `itemid` , `qty` ) VALUES ('$to', $itemid, $qty) ON DUPLICATE KEY UPDATE `qty` = `qty` + $qty;"; //c+p from combine.php -- should be function.
      $sql_r = mysql_query($sql,$mysql_link);
      if (!$sql_r) {
        trigger_error("Query failed in add_items <!--$sql-->",E_USER_NOTICE);
        }
      return $sql_r;
      }
 */
}
