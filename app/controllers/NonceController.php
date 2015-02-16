<?php

class NonceController extends \Phalcon\Mvc\Controller  {

  private $password;
  private $expiry;

  public function __construct($password,$expiry) {
    $this->password = $password;
    $this->expiry = $expiry;
  }

  private function passwordify($s) {
    return "$s{$this->password}$s";
  }

  public function encode($time) {
    $hash = password_hash($this->passwordify($time),PASSWORD_DEFAULT);
    $this->session->set($hash,TRUE);
    return $hash;
  }
  
  public function currentHash() {
    $time = time();
    return array('time' => $time,
                 'hash' => $this->encode($time));
  }

  public function check($time,$nonce) {
    $elapsed_time = time() - $time;
    return ($this->session->has($nonce) 
      && ($elapsed_time < $this->expiry)
      && (password_verify($this->passwordify($time),$nonce)));
  }
}
