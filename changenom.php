<?php
require_once('alchemake-inc.php');

if (!time_nonce($_GET['time'],$_GET['nonce'])) {
  bounce_back("Nonce Mismatch",NONCEERR);
  exit();
  }
if ($alphamode) {
  context_setup(TRUE);
  $userid = '6897197ae54b3206da04390198a4d8fd46d8b803';
  restore_error_handler(); //on-screen errors when testing
  }
else {
  context_setup();
  }
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
?></div>