<?php

class UsersController extends AlchemakeController {
    
  public function loginAction() {
    if($this->userIsLoggedIn()) {
      $this->flashSession->notice("You are already logged in.");
      $this->dispatcher->forward(array('action'=>'index'));
    }
  }
  
  public function randomUserAction() {
      $this->shallOutputJSON();
      $min = Users::findFirst(['order' => 'userid ASC'])->userid;
      $max = Users::findFirst(['order' => 'userid DESC'])->userid;
      $rand_id = mt_rand($min, $max); //biased for nonsequential IDs
      $rand_user = Users::findFirst(['conditions' => " userid <= $rand_id ",
              'order' => ' userid DESC ']);
      echo json_encode([$rand_user->userid,$rand_user->nickname]);
  }

  public function logoutAction() {
    $this->session->destroy();
  }
  
  public function renameAction() {
      $user = $this->userThatIsLoggedIn();
      if ($user) {
          $user->nickname = $this->request->getPost('nickname');
          $user->update();
             foreach ($user->getMessages() as $message) {
                $this->flashSession->notice("$message");
             } 
          }
      $this->dispatcher->forward(["controller" => "users","action" => 'index']);
  }
  
  public function reorderAction() {
      $user = $this->userThatIsLoggedIn();
      if ($user) {
          if (!$user->reorder((int)$this->request->getPost('direction'))) {
             foreach ($user->getMessages() as $message) {
                $this->flashSession->notice("$message");
             } 
          }
      }
      $user->save();
      $this->dispatcher->forward(["controller" => "users","action" => 'index']);
  }
  
  private function loginError () {
    $this->dispatcher->forward(array('action'=>'loginError'));
  }

  public function loginErrorAction() {
    //shows login error page
  }
  
  public function loginEmailAction() {
    //Accepts from the login form information on logging in using email
    //credentials

    $email    = $this->request->getPost("email");
    $password = $this->request->getPost("password");
    $user = $this->userLookupBy($email);

    if (!isset($email) 
            || $email === NULL 
            || $user->networkid !== 'email'
            || !$user->checkNetworkcredential($password)) {
      //only allow manual logins for accounts which have an email credential
      $this->loginError();
    }
    else {
      $this->completeLogin($user);
    }
  }

  private function completeLogin($user) {
    if ($user) {      
      $this->session->set('userid',$user->userid);
      $this->flashSession->notice("You are now logged in, have fun!");
      $messages = $user->doDrops($this->config);
      foreach ($messages as $message) {
        $this->flashSession->notice($message);
      }
      $this->dispatcher->forward(array('action'=>'index'));
      }
    else {
      $this->loginError();
      }
    }

  public function saveAction() {
    //shows save form
  }

  public function newAction() {
    $values['nickname'] = $this->request->getPost("nickname");
    $values['emailaddress'] = $this->request->getPost("emailaddress");
    $values['networkcredential'] = $this->request->getPost("password");
    $values['networkid'] = 'email'; //only email login by manual form
    $user = new Users();

    if (!$this->security->checkToken() ||
      ($user->save($values, //short-circuiting to skip save on nonce error
      ['nickname','emailaddress','networkcredential','networkid']) === FALSE)) {
        foreach ($user->getMessages() as $message) {
          $this->flashSession->notice("$message");
        }
        $this->dispatcher->forward(array("action"=>"save"));
      }
      else {
        //Shows success message by view.
      }
  }

  public function indexAction() {
    $logged_in = $this->userIsLoggedIn();
    if ($logged_in) {
      $this->view->setVar('userinfo',
        $this->userLookupBy($this->session->get("userid"),'userid'));
    }
    else {
      $this->flashSession->notice("You are not currently logged in");
      $this->dispatcher->forward(array("action"=>"login"));
    }
  }
  
  protected function randomName($fnames,$lnames,$append = '') {
    $fullname = [];
    foreach ([$fnames,$lnames] as $names) {
      $fullname[] = $names[array_rand($names)];
      }
    $fullname[] = $append;
    implode('_',$fullname);
  }

}
