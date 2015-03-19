<?php

class Items extends Phalcon\Mvc\Model {

  public $itemid;
  public $name;
  public $basic;
  public $description;
  public $image;
  const MIN_BASIC = 16;
  const MAX_BASIC = 25;

  public static function clean($dirty_items) {
        //TODO: Fetch from the db a valid list of item ids to check
        $clean = [];
        foreach ($dirty_items as $item_id => $qty) {
            $clean[(int)$item_id] = (int)$qty;
        }
        return $clean;
    }
  
  public function initialize() {
    $this->belongsTo('itemid','Inventory','itemid');
    foreach ([1,2,3] as $i) {
      //this really needs fixing of the normalization in the schema instead
      $this->belongsTo('itemid','Combinations',"ingredient{$i}_itemid");
    }
    $this->belongsTo('itemid','Combinations','preq_tool_itemid');
    $this->belongsTo('itemid','Combinations','itemid');
    $this->belongsTo('itemid','TradeDetails','itemid');
  }

  public static function basicNumber($itemno) {
    //possible improvement: mysql call?
    return (($itemno >= self::MIN_BASIC) && ($itemno <= self::MAX_BASIC));
    }
    
  public function isBasic() {
      return self::basicNumber($this->itemid);
  }

}
