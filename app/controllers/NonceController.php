<?php

class NonceController {

  private $password;
  private $expiry;

  public function __contruct($password,$expiry) {
    $this->password = $password;
  }

  public function encode($time) {
    return password_hash("$time{$this->password}$time");
  }

  public function current_hash() {
    $time = time();
    return array('time' => $time,
                 'hash' => $this->encode($time));
  }

  public function check($time,$nonce) {
    $elapsed_time = time() - $time;
    if (($elapsed_time < $this->expiry)
      && ($this->encode($time) === $nonce)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
