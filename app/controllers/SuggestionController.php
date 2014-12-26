<?php
class SuggestionController extends AlchemakeController {

  //TODO: Access control

  public function reject() {
  //TODO
  }

  public function list() {
    //TODO: Rewrite
    $sql = "SELECT * FROM `combo-suggest` WHERE 1 LIMIT 0,250";
    $sql_r = mysql_query($sql,$mysql_link);
    $num_suggestions = mysql_num_rows($sql_r);
    echo "Pending suggestions &ndash $num_suggestions:<br /><ul>";
    while ($c_suggestion = mysql_fetch_assoc($sql_r)) {
      echo "<li><form method=\"GET\">{$c_suggestion['userid']}: {$items[$c_suggestion['ingredient1_itemid']]['name']} + {$items[$c_suggestion['ingredient2_itemid']]['name']} + {$items[$c_suggestion['ingredient3_itemid']]['name']} = "
        ."<input type=\"hidden\" name=\"userid\" value=\"{$c_suggestion['userid']}\"/>"
        ."<input type=\"hidden\" name=\"suggestionid\" value=\"{$c_suggestion['suggestionid']}\"><input type=\"text\" name=\"suggestion\" value=\"{$c_suggestion['suggestion']}\"><br/>"
        ."<input type=\"hidden\" name=\"item3\" value=\"{$c_suggestion['ingredient3_itemid']}\"><input type=\"hidden\" name=\"item2\" value=\"{$c_suggestion['ingredient2_itemid']}\"><input type=\"hidden\" name=\"item1\" value=\"{$c_suggestion['ingredient1_itemid']}\"><label>Man ID: <input type=\"text\" value=\"0\" name=\"manid\"></label>"
        ."<input type=\"text\" name=\"description\" value=\"description\"/><button type=\"submit\" name=\"action\" value=\"acceptsuggest\">Accept</button>"
        ."<button type=\"submit\" name=\"action\" value=\"rejectsuggest\">Reject</button></form></li>";
      }
    ?></ul><?php
  }

  public function accept() {
    //TODO: Rewrite
    foreach ($_GET as &$getvar) {
      if (is_string($getvar)) {
        $getvar = mysql_real_escape_string($getvar);
        }
      }
    if ($_GET['manid'] !== 0) {
      $sql = "INSERT INTO `items` (`itemid` ,`name` ,`description` ,`image`) VALUES (NULL , '{$_GET['suggestion']}', '{$_GET['description']}', '');";
      $sql_r = mysql_query($sql,$mysql_link);
      $new_itemid = mysql_insert_id($mysql_link);
      $sql = "INSERT INTO `combinations` (`itemid`, `ingredient1_itemid`, `ingredient2_itemid`, `ingredient3_itemid`, `preq_tool_itemid`) VALUES ('$new_itemid', '{$_GET['item1']}', '{$_GET['item2']}', '{$_GET['item3']}', NULL);";
      $sql_r = mysql_query($sql,$mysql_link);
      }
    else {
      $sql = "INSERT INTO `combinations` (`itemid`, `ingredient1_itemid`, `ingredient2_itemid`, `ingredient3_itemid`, `preq_tool_itemid`) VALUES ('{$GET['manid']}', '{$_GET['item1']}', '{$_GET['item2']}', '{$_GET['item3']}', NULL);";
      $sql_r = mysql_query($sql,$mysql_link);
      }
    add_items($_GET['userid'],3,1);
    add_items($_GET['userid'],$_GET['item1'],1);
    add_items($_GET['userid'],$_GET['item2'],1);
    if ($_GET['item3'] != 0) {
      add_items($_GET['userid'],$_GET['item3'],1);
      }
    $sql = "DELETE FROM `alchemake`.`combo-suggest` WHERE `combo-suggest`.`suggestionid` = {$_GET['suggestionid']} LIMIT 1";
    $sql_r = mysql_query($sql,$mysql_link);
    ?>
    <div class="actionnotice">
    Accepting Suggestion
    </div>
    <?php
    break;
  }
}
