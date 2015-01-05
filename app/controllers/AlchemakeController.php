<?php

class AlchemakeController extends \Phalcon\Mvc\Controller {
  public function nonceError($text = "") {
      $this->flashSession->notice("Please resubmit this form. To
        prevent cheating, forms time out after several minutes
        of inactivity. $text");
      return FALSE; //allows easy use in if statement after Nonce check
  }
}
