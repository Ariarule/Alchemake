<?php
class TradeDetails extends Phalcon\Mvc\Model {

  public $tradeid;
  public $direction;
  public $party;
  public $itemid;
  public $qty;

  public function initialize() {
    $this->belongsTo('tradeid','Trades','tradeid');
    $this->hasOne('itemid','Items','itemid');
  }
}
