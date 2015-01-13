<?php
class Trades extends Phalcon\Mvc\Model  {

  public $status;
  public $timestamp;
  public $tradeid;
  public $proposer_userid;
  public $proposed_userid;

  public function initialize() {
    $this->hasMany('tradeid','TradeDetails','tradeid');
    $this->belongsTo('proposer_userid','Users','userid');
    $this->belongsTo('proposed_userid','Users','userid');
  }
}
