<?php

use Phalcon\Loader,
    Phalcon\DI\FactoryDefault,
    Phalcon\Mvc\Application,
    Phalcon\Mvc\View,
    Phalcon\Config\Adapter\Ini;

try {
  $di = new \Phalcon\DI\FactoryDefault();

  $db_config      = new Ini('../app/config/db.ini');
  $general_config = new Ini('../app/config/general.ini');

  $loader = new \Phalcon\Loader();
  $loader->registerDirs(['../app/controllers/',
                         '../app/models/'])->register();
  $di->setShared('nonce',
    new NonceController(
      $general_config->nonce->password,$general_config->nonce->timeout));
  $di->setShared('db',new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => $db_config->database->host,
            "username" => $db_config->database->username,
            "password" => $db_config->database->password,
            "dbname" => $db_config->database->dbname)));
  $di->set('view',function () {
    $view = new View;
    $view->setViewsDir('../app/views/');
    return $view;
  });
  $di->setShared('session',function () {
     $session = new Phalcon\Session\Adapter\Files();
     $session->start();
     return $session;
  });
  $app = new Application($di);
  echo $app->handle()->getContent();
  }
catch (Exception $e) {
  //TODO: Exception Logging
  echo $e->getMessage();
  }
