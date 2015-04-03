<?php

class IndexController  extends AlchemakeController {

  public function indexAction() {
      $this->dispatcher->forward(['controller'=>'users','action'=>'index']);
  }

}
