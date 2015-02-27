<?php
class Trades extends Phalcon\Mvc\Model  {

  public $status;
  public $timestamp;
  public $tradeid;
  public $proposer_userid;
  public $proposed_userid;

  public function initialize() {
    $this->skipAttributes(['timestamp']);
    $this->hasMany('tradeid','TradeDetails','tradeid');
    $this->belongsTo('proposer_userid','Users','userid');
    $this->belongsTo('proposed_userid','Users','userid');
  }
  
  public function acceptable() {
      return ((time() - strtotime($this->timestamp)) < 7776000
        && $this->status === 'proposed');
  }
  
}
