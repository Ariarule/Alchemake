<?php

use Phalcon\Loader,
    Phalcon\DI\FactoryDefault,
    Phalcon\Mvc\Application,
    Phalcon\Mvc\View,
    Phalcon\Config\Adapter\Ini;

try {
  $di = new \Phalcon\DI\FactoryDefault();

  $db_config = new Ini('../app/config/db.ini');

  $loader = new \Phalcon\Loader();
  $loader->registerDirs(['../app/controllers/',
                         '../app/models/'])->register();
  $di->set('view',function () {
                              $view = new View;
                              $view->setViewsDir('../app/views/');
                              return $view;
                            });
  $app = new Application($di);
  echo $app->handle()->getContent();
  }
catch (Exception $e) {
  //TODO: Exception Logging
  echo $e->getMessage();
  }
