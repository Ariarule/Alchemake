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

  public function setNetworkcredential($plaintext) {
    if (strlen($plaintext) >= 8) {
      $this->networkcredential = password_hash($plaintext,PASSWORD_DEFAULT);
    }
     else {
       $this->networkcredential = ''; //caught by model validation below to show
                                      //correct message
    }
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

  public function initialize() {
    $this->skipAttributes(['userid',
                           'emailaddress', //NULL allowed, optional
                           'rank',
                           'last_drop',
                           'last_allowence',
                           'main_order']);
  }

  public function validation() {
    $this->validate(new Email(array('field'   => 'emailaddress',
      'message' => 'Invalid email address.')));
    //FIXME: networkcredential not working for new signups
    //$this->validate(new PresenseOf(array('field' => 'networkcredential',
    //  'message' => 'A password or equivalent login credential is required.')));
    return $this->validationHasFailed() !== TRUE;
  }

  /*
  public function change_nom ($userid,$newnom) {
    global $mysql_link;
    $sql = "UPDATE `users` SET `nickname` = '$newnom' WHERE `users`.`userid` = '$userid' LIMIT 1;";
    $change_r = mysql_query($sql,$mysql_link);
    $number = mysql_affected_rows($mysql_link);

    if ($number == 1) {
      return TRUE;
      }
    else {
      return FALSE;
      }
    }

  protected function does_user_exist_by_nom($nom) {
    global $mysql_link;

    $sql = "SELECT * FROM `users` WHERE `nickname` = '$nom' LIMIT 1";
    $query_r = mysql_query($sql,$mysql_link);
    if ($query_r) {
      return mysql_num_rows($query_r);
      }
    else {
      return $query_r;
      }
    }

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

  protected function get_userid_from_nick($userid) {
    global $mysql_link;

    $userid = trim($userid);
    $userid = mysql_real_escape_string($userid); //TODO: Replace with alphanum restriction
    $sql = "SELECT `userid` FROM `users` WHERE `nickname` = '$userid' LIMIT 1;";
    $sql_r = mysql_query($sql,$mysql_link);
    if ((!$sql_r) || (mysql_num_rows($sql_r) == 0)) {
      return FALSE;
      }
    $nom = mysql_fetch_assoc($sql_r);
    return $nom['userid'];
  }

  private function parse_trade_table($old_trade_table) {
    //mysql returns a row for each line in tradedetails -- this combines them into a subarray
    $new_trade_table = array();
    foreach ($old_trade_table as $trade_row) {
      if (!isset($new_trade_table[$trade_row['tradeid']])) {
        $new_trade_table[$trade_row['tradeid']] = array(
        'proposer_table' => array($trade_row['proposer_itemid'] => $trade_row['proposer_qty']),
        'proposed_table' => array($trade_row['proposed_itemid'] => $trade_row['proposed_qty']),
              'proposer_userid' => $trade_row['proposer_userid'],
              'proposed_userid' => $trade_row['proposed_userid'],
              'status' => $trade_row['status'],
              'networkid' => $trade_row['networkid'],
              'network' => $trade_row['network'],
              'nickname' => $trade_row['nickname'],
        );
      }
      else {
        //this will usually give notices about undefined indexes; the default behavior is fine.
        @$new_trade_table[$trade_row['tradeid']]['proposer_table'][$trade_row['proposer_itemid']] += $trade_row['proposer_qty'];
        @$new_trade_table[$trade_row['tradeid']]['proposed_table'][$trade_row['proposed_itemid']] += $trade_row['proposed_qty'];
        }
      unset($new_trade_table[$trade_row['tradeid']]['proposer_table'][0]); //There is no item 0, Artifact of NULL proposal.
      unset($new_trade_table[$trade_row['tradeid']]['proposed_table'][0]);
      }
    return $new_trade_table;
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

  protected function possible_level_up($userid,$userrank) {
    //TODO: Finish me! Call me!
    if ($userrank > 8) {
      return FALSE;
      }

    $level_up = FALSE;
    if ($userrank < 17) {
      $rand = rand(1,10 + $userrank);
      if ($rand == 1) {
        $level_up = TRUE;
        }
      }
    }

  public function does_user_exist($userid) {
    global $mysql_link;

    $userid = mysql_real_escape_string($userid); //FUTURE: Replace with robust check for valid userid. //Should do nothing
    $search_r = mysql_query("SELECT *  FROM `users` WHERE `userid` = '$userid';",$mysql_link);

    if ($search_r) {
      if (mysql_num_rows($search_r) > 0) {
        return TRUE;
        }
      }
    return FALSE;
  } */
}
