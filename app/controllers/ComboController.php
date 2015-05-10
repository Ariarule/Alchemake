<?php

class ComboController  extends AlchemakeController {

  public function listAction() {
    $this->shallOutputJSON();
    $items = array_map(function ($n) {return (int)$n;},
              array_keys(array_filter(
              $this->request->getQuery('items'))));
    sort($items);
    $combo_array = [];
    if (in_array(count($items), [2,3])) {
      $combos = Combinations::find("ingredient1_itemid = {$items[0]} "
              . " AND ingredient2_itemid = {$items[1]} "
              . (isset($items[2]) ? 
                      "AND ingredient3_itemid = {$items[2]} " :
                      ''));
      foreach ($combos as $combo) {
        //this can find more than one combo in the case of two items selected
        $combo_array[] = 
          array_map (function ($i) {return (is_numeric($i) ? (int)$i : $i);},
            [$combo->itemid,$combo->preq_tool_itemid]);
      }
    }
    echo json_encode($combo_array);
  }
}