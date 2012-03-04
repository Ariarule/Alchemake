<?php
require_once('session.php');
global $userid;
$current_campaign_sql = "SELECT `campaign`,`session` FROM `users` WHERE `userid` = '$userid' LIMIT 1";
$sql_r = mysql_query($sql,$mysql_link);
$camp_info = mysql_fetch_assoc($sql_r);
require_once("campaigns/{$camp_info['campaign']}/{$camp_info['session']}.php"); //sets $c_session
if (isset($_GET["camp_do"])) {
  $c_session->do_action((int)$_GET["camp_do"]);
  }
else {
  $c_session->show();
  }