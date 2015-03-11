<?php

use Phalcon\Mvc\Model\Validator\Email,
    Phalcon\Mvc\Model\Validator\Uniqueness,
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
                           'networkcredential', //skip adding the default for 
                                                //custom message
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
    if (isset($this->networkcredential)
    &&  (len($this->networkcredential) > 0)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function validation() {
    $this->validate(new PresenceOf(['field' => 'nickname',
      'message' => 'A nickname is required.']));
    $this->validate(new Email(['field'   => 'emailaddress',
      'message' => 'Invalid email address.']));
    $this->validate(new Uniqueness(['field' => 'nickname',
      'message' => 'Someone already has that nickname.'])); 
    $this->validate(new PresenceOf(['field' => 'networkcredential',
      'message' => 'A password is required.']));
    //See setNetworkcredential above, which does actual checking
    //for this due to need to hash the passwords
    return $this->validationHasFailed() != TRUE;
  }

  private function giverUpdate($field) {
    $this->$field = ' NOW() ';
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
}
