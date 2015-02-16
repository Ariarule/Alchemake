<?php

class UsersController extends AlchemakeController {

  public function loginAction() {
    if($this->userIsLoggedIn()) {
      $this->flashSession->notice("You are already logged in.");
      $this->dispatcher->forward(array('action'=>'index'));
    }
  }

  public function logoutAction() {
    $this->session->destroy();
  }

  public function loginEmailAction() {
    //Accepts from the login form information on logging in using email
    //credentials

    $email    = $this->request->getPost("email");
    $password = $this->request->getPost("password");
    $user = $this->userLookupBy($email);

    if (!isset($email) || $email === NULL || $user->networkid !== 'email') {
      //only allow manual logins for accounts which have an email credential
      $this->dispatcher->forward(array('action'=>'loginError'));
    }
    elseif ($user->checkNetworkcredential($password)) {
      $this->session->set('userid',$user->userid);
      $this->flashSession->notice("You are now logged in, have fun!");
      $this->dispatcher->forward(array('action'=>'index'));
    }
    else {
      $this->dispatcher->forward(array('action'=>'loginError'));
    }
  }

  public function completeLoginAction() {
    $this->doDrops();
    }

  public function loginErrorAction() {
    //shows login error page
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

    if (!($this->nonce->check($this->request->getPost("time"),
      $this->request->getPost("hash")) || $this->nonceError()) ||
      ($user->save($values, //short-circuiting to skip save on nonceError
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

  private function doDrops() {
    if ($this->userIsLoggedIn()) {
      $user = $this->userLookupBy($this->session->get('userid'),'userid');
      $delay        = $this->general_config->game->min_time_ay;
      $allowence    = $this->general_config->game->min_time_ay;
      $probability  = $this->general_config->game->ay_probability;
        //supposed to be an integer between 0 and 100 inclusive
        //NOT a float between 0.0 and 1.0

      $time_from_ay    = time() - @strtotime($user->last_allowence);
      $time_from_drop  = time() - @strtotime($user->last_drop);
        //suppress the warning from strtotime about the how the system timezone
        //cannot be trusted. PHP cannot know anyway
        //that the system isn't set up correctly for the string that we have.
        //possible improvement: get the timezone from the db? ini file?

      if (($time_from_ay > $delay) && (rand(0,100) < $probability)) {
        $allowence = $user->giveAllowence();
        if ($allowence) {
          $this->flashSession->notice("You have been given $allowence AY.");
        }
        if ((rand(1,86400) < $time_from_drop) && ($time_from_drop > 120)) {
          if ($user->giveItems) {
            $this->flashSession->notice("New items! Check your inventory.");
          }
        }
      }
    }
  }

  public function show_card($user_pic,$userinfo) {
    global $gen_time;
    global $gen_nonce;
    global $rank_texts;
    global $userid;

    echo "<form method=\"POST\" action=\"changenom.php?time=$gen_time&nonce=$gen_nonce\">\n";
    echo "<div id=\"liscinfo\">";
    if (is_string($user_pic[0]['pic_square']) && ($user_pic[0]['pic_square'] != '')) {
      echo "<img src=\"{$user_pic[0]['pic_square']}\" style=\"float: left;\" height=\"50px\" width=\"50px\"/>\n";
      }
    echo "<h1 class=\"liscinfo\">Official Alchemaking&nbsp;Card</h1>\n<hr style=\"clear: both\"/>\n<ul class=\"liscinfo\"><li>Nom&nbsp;de&nbsp;Alchemake:<br />\n<input type=\"text\" style=\"border: 0px; border-bottom: 1px dotted black; background: #DDDDDD; opacity: 0.75;\" size=\"37\" value=\"{$userinfo['nickname']}\" name=\"newnom\">\n<button class=\"alchemakebutton\" type=\"submit\"><small>change Nom</small></button></li><li>Alchemake Guild #:<br />$userid</li><li>Rank: {$rank_texts[$userinfo['rank']]}</li><li>Status: In&nbsp;Good&nbsp;Standing</li></ul></div>\n"; //TODO: Implement Rank. Maybe insert facebook pic if available?
    echo "</form>";
    }

  public function change_nom () {
    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>alchemake.css?v=<?php echo $version;?>" />
    <div id="page">
    <?php

    $proposed_nom = trim($_POST['newnom']);
    if ((preg_match('|^\w+$|',$proposed_nom)) && (sizeof($proposed_nom > 3)) && (sizeof($proposed_nom < 40))) {
      //FUTURE: Make is_valid_nom function
      $proposed_nom = mysql_real_escape_string($proposed_nom); //should do nothing
      $success = change_nom($userid,$proposed_nom);
      if (!$success) {
        $proposed_nom = $proposed_nom . "_" . rand(1500,1799);
        $success = change_nom($userid,$proposed_nom);
        }
      if (!$success) { //SIC. Yes, the same test condition again. It might have changed. Or not.
        trigger_error("Your Nom de Alchemake could not be changed to $proposed_nom -- someone else might already have this Nom.",E_USER_ERROR);
        }
      else {
        echo "Your Nom de Alchemake has been changed to $proposed_nom.";
        ?><br />
        <a onclick="var attachment = {'name' : 'I changed my Nom de Alchemake.','caption':'{*actor*} is now known to the Alchemy guild as <?php echo $proposed_nom; ?>.','description':'You can use my Nom de Alchemake, <?php echo $proposed_nom; ?>, to trade items with me in Alchemake.'}; var actionLinks = [{ 'text': 'Alchemake', 'href': 'http://apps.facebook.com/alchemake/'}]; Facebook.streamPublish('',attachment,actionLinks);">Tell my friends my Nom de Alchemake.</a>
        <?php
        }
      }
    else {
      ?>
      Your Nom de Alchemake must be of letters and numbers, and at least 4 long, but not more than 39.
      <?php
      }
    return_button();
    ?></div><?php
  }

  protected function random_name($fnames,$lnames,$append = '') {
    $fullname = [];
    foreach ([$fnames,$lnames] as $names) {
      $fullname[] = $name[array_rand($name)];
      }
    $fullname[] = $append;
    implode('_',$fullname);
  }

}
