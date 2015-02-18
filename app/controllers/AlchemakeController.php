<?php
use Phalcon\Mvc\View;

class AlchemakeController extends \Phalcon\Mvc\Controller {

  public function shallOutputJSON() {
    $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
    $this->response->setHeader("Content-Type", "application/javascript");
    }
    
  protected function userIsLoggedIn() {
    return $this->session->has("userid");
  }

  protected function userLookupBy($identifier,$field = 'emailaddress') {
    return Users::findFirst("$field = '$identifier'");
  }

  protected function useridIsLoggedIn($userid) {
    return $this->session->get('userid') === $userid;
  }

  protected function userThatIsLoggedIn() {
    if ($this->userIsLoggedIn()) {
      return $this->userLookupBy($this->session->get('userid'),$field = 'userid');
    }
    else {
      return FALSE;
    }
  }

  public function nonceError($text = "") {
      $this->flashSession->notice("Please resubmit this form. To
        prevent cheating, forms time out after several minutes
        of inactivity. $text");
      return FALSE; //allows easy use in if statement after Nonce check
  }
}
