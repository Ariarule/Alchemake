<?php
use Phalcon\Mvc\View;

class ComboController  extends AlchemakeController {

  public function indexAction() {
      
  }
    
  public function listAction() {
    $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
    $this->response->setHeader("Content-Type", "application/javascript");

    $items = array_map(function ($n) {return (int)$n;},
            $this->request->getPost('items'));
    sort($items);
    $items = array_values(array_filter($items));

    $query = "ingredient1_itemid = {$items[0]} AND "
            . " ingredient2_itemid = {$items[1]} ";
    if (isset($items[2])) {
        $query .= "AND ingredient3_itemid = {$items[2]} ";
    }
    
    $combos = Combinations::find($query);
    
    $combo_array = [];
    foreach ($combos as $combo) {
        $combo_array[] = [$combo->itemid,$combo->preq_tool_itemid];
    }
    
    echo json_encode($combo_array);
  }
}