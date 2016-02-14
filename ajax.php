<?php

    //FIX DISI

    require_once '../../config.php';
    require_once 'lib.php';
    
    $iid                        = optional_param('iid', NULL, PARAM_INT);
    $rid                        = optional_param('rid', NULL, PARAM_INT);
    $endOffset                  = optional_param('endOffset', NULL, PARAM_INT);
    $startOffset                = optional_param('startOffset', NULL, PARAM_INT);
    $endPosition                = optional_param('endPosition', NULL, PARAM_INT);
    $text                       = optional_param('text', NULL, PARAM_TEXT);

    $student = $DB->get_record("user", array("id" => $USER->id));
    
    if (!empty($id))
      $context = get_context_instance(CONTEXT_MODULE, $id);
    else
      $context = get_context_instance(CONTEXT_USER, $USER->id);
    
    if ($data = $DB->get_record("journal_entries", array("id"=>$iid))){

      $t = explode(">", str_replace("]]", ">", $data->text));

      $mark = false;

      $newtext = "";
      
      //echo "<pre>";
      //var_dump ($t);

      foreach($t as $k => $t_){
        //echo "$k => $t_<br />";
        for ($i=-3;$i<=3;$i++) {
          $check = substr($t_, $startOffset + $i, ($endOffset - $startOffset));

          if ($check == "<" || $check == "[")
            break;

          if ($check == $text && $mark == false){
            //echo "$check == $text<br />";
            //echo "OLD:{$t_}<br />";
            
            $t_ = substr($t_, 0, $startOffset + $i) . "[[{$text} jaudio={$rid}]]" . substr($t_, $endOffset + $i);
            //echo "NEW:{$t_}<br />";
            $mark = true;
          }
        }

        
        
        if ($k > 0) {
          $n = substr($t[$k - 1], -1);
          if (is_numeric($n)) {
            $newtext .= "]]".$t_;
          } else {
            $newtext .= ">".$t_;
          }
        } else {
          $newtext .= $t_;
        }
      }
      
      
      if ($mark == false) {
        //$data->text = str_replace($text, "[[{$text} jaudio={$rid}]]", $data->text);
        echo "DEBUG: SORRY, YOUR WORD \"{$text}\" NOTFOUND.";
      } else {
        $data->text = $newtext;
      }
      
      /*
      $t = str_replace("[[", "", strip_tags($data->text));
      $t = preg_replace('/ jaudio=(.*)]]/', '', $t);
      
      $sp = $endPosition - strlen($text);
      $ep = strlen($text);
      
      echo var_dump(substr($t, $sp, $ep));
      */
      
      //$data->text = str_replace($text, " [[{$text} jaudio={$rid}]] ", $data->text); //<span style='background-color:#FFFF00'>
      //$data->text = substr($data->text, 0, $startOffset) . str_replace($text, " [[{$text} jaudio={$rid}]] ", substr($data->text, $startOffset, $endOffset)) . substr($data->text, $endOffset);
      
      $data->text = preg_replace('/\s+/', ' ', $data->text);
      
      $add       = new stdClass;
      $add->id   = $data->id;
      $add->text = $data->text;
      
      //print_r ($add);
      //die();
      
      $DB->update_record("journal_entries", $add);
    }
    
    
    echo format_text($data->text).'<br />';
    
    echo html_writer::link('#', get_string("voiceannotations", "journal"), array("onclick"=>"document.getElementById('light').style.display='block';document.getElementById('fade').style.display='block';getSel();$('#iid_1').val({$data->id});startRecording(1);$('#idoftext_1').val($(this).parent().attr('id'));return false;"));
    