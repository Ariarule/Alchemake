<?php
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class Trades extends Phalcon\Mvc\Model  {

  public $status;
  public $timestamp;
  public $tradeid;
  public $proposer_userid;
  public $proposed_userid;

  public function initialize() {
    $this->skipAttributes(['timestamp']);
    $this->hasMany('tradeid','TradeDetails','tradeid');
    $this->belongsTo('proposer_userid','Users','userid',['alias' => 'proposer']);
    $this->belongsTo('proposed_userid','Users','userid',['alias' => 'proposed']);
  }
  
  public function isAcceptable() {
      return strtotime($timestamp) < 2678400;
      //this intentionally has a slight grace period on it
      //compared to findAcceptable
      //the intention is that a trade is available for action for 
      //a while after its been viewed
      //I think the original had something like this.
  }
  
  public static function findAcceptable($conditions = NULL) {
      //because we are using raw MySQL style SQL here,
      //public functions must validate their added conditions
      $trade = new Trades();
      $sql = "select * from trades where status = 'pending' "
            . "AND TIMESTAMPDIFF(DAY,timestamp,now()) < 30 ";
      if ($conditions) {
          $sql .= " AND $conditions ";
      }
      return new Resultset(null,
              $trade,
              $trade->getReadConnection()->query($sql));
  }
  
  public static function findAcceptableByUserId($userid,$field) {
      $userid = (int)$userid;
      if ($field === 'both') {
          $fields = ['proposer','proposed'];
      }
      elseif (($field === 'proposer') || ($field === 'proposed')) {
          $fields = [$field];
      }
      
      foreach ($fields as &$query_field) {
          $query_field = " {$query_field}_userid = $userid";
      }
      $query = implode(" AND ", $fields);

      return self::findAcceptable($query);
  }
  
  
  
}
