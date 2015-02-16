<?php

class NonceController {

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
    return password_hash($this->passwordify($time),PASSWORD_DEFAULT);
  }

  public function currentHash() {
    $time = time();
    return array('time' => $time,
                 'hash' => $this->encode($time));
  }

  public function check($time,$nonce) {
    $elapsed_time = time() - $time;
    return (($elapsed_time < $this->expiry)
      && (password_verify($this->passwordify($time),$nonce)));
  }
}
