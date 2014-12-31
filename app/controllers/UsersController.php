<?php

class UsersController extends AlchemakeController {

  public function saveAction() {

  }

  public function newAction() {
    $values['nickname'] = $this->request->getPost("nickname");
    $values['emailaddress'] = $this->request->getPost("emailaddress");
    $values['networkcredential'] = $this->security->hash($this->request->getPost("password"));
    if ($this->nonce->check($this->request->getPost("time"),
      $this->request->getPost("hash"))) {
        $user = new Users();
        $user->save($values);
      }
    else {
      echo $this->request->getPost("time") . " ";
      echo $this->request->getPost("hash") . " ";
      echo $this->nonce->encode($this->request->getPost("time")) . " ";
      echo (int)$this->nonce->check($this->request->getPost("time"),
        $this->request->getPost("hash"));
      echo "<br />Nope";
    }
  }

  public function indexAction() {
    //TODO: "Your account" screen
  }

  private function do_drops($time_from_ay,$time_from_drop) {
    if ($time_from_ay > 604801) {
      $allowence = get_allowence();
      if(($allowence > 0) && (add_items($userid,1,$allowence))) {
        echo "<div class='noticebox'>You have been given AY $allowence from the Alchemake Alchemy Guild. Thank you for Alchemaking.</div>";
        $sql = "UPDATE `alchemake`.`users` SET `last_allowence` = NOW() WHERE `users`.`userid` = '$userid' LIMIT 1;";
        $sql_r = mysql_query($sql,$mysql_link);
        if (mysql_affected_rows($mysql_link) != 1) {
    trigger_error("Couldn't update last_allowence correctly for $userid.",E_USER_NOTICE);
    }
        }
      }
    else {
      $droproll = rand(1,86400);
      if (($droproll < $time_from_drop) && ($time_from_drop > 120)) {
        if (drop_items($userid)) {
    echo "<div class='noticebox'>You have new items!</div>";
    $sql = "UPDATE `alchemake`.`users` SET `last_drop` = NOW() WHERE `users`.`userid` = '$userid' LIMIT 1;";
    $sql_r = mysql_query($sql,$mysql_link);
    if (mysql_affected_rows($mysql_link) != 1) {
      trigger_error("Couldn't update last_drop correctly.",E_USER_NOTICE);
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
