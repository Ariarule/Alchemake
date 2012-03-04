<?php
require_once('alchemake-inc.php');
if ($_POST['alchemake'] == 'Alchemake') {
  require_once('combine.php');
  }
elseif ($_POST['alchemake'] == 'Trade') {
  require_once('setuptrade.php');
  }
else {
  bounce_back("Nonce Mismatch",NONCEERR); //TODO: WRONG ERROR MESSAGE!
  }