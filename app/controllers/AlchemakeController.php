<?php

class AlchemakeController extends \Phalcon\Mvc\Controller {


  protected function userIsLoggedIn() {
    return $this->session->has("userid");
  }

  protected function userLookupBy($identifier,$field = 'emailaddress') {
    return Users::findFirst("$field = '$identifier'");
  }

  protected function useridIsLoggedIn($userid) {
    return $this->session->get('userid') === $userid;
  }

  public function nonceError($text = "") {
      $this->flashSession->notice("Please resubmit this form. To
        prevent cheating, forms time out after several minutes
        of inactivity. $text");
      return FALSE; //allows easy use in if statement after Nonce check
  }
}
