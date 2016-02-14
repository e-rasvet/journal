<?php // $Id: report.php,v 1.3 2011/04/06 15:20:30 davmon Exp $

require_once("../../config.php");
require_once("lib.php");


$id = required_param('id', PARAM_INT);   // course module

if (! $cm = get_coursemodule_from_id('journal', $id)) {
    print_error("Course Module ID was incorrect");
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error("Course module is misconfigured");
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/journal:manageentries', $context);


if (! $journal = $DB->get_record("journal", array("id" => $cm->instance))) {
    print_error("Course module is incorrect");
}


// Header
$PAGE->requires->js('/mod/journal/js/jquery.min.js', true);  //FIX DISI
$PAGE->requires->js('/mod/journal/js/recorder.js', true);  //FIX DISI
$PAGE->requires->js('/mod/journal/js/record_wav.js?10', true);  //FIX DISI
$PAGE->requires->js('/mod/journal/js/main.js?10', true);  //FIX DISI

$PAGE->set_url('/mod/journal/report.php', array('id'=>$id));

$PAGE->navbar->add(get_string("entries", "journal"));
$PAGE->set_title(get_string("modulenameplural", "journal"));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("entries", "journal"));


// make some easy ways to access the entries.
if ( $eee = $DB->get_records("journal_entries", array("journal" => $journal->id))) {
    foreach ($eee as $ee) {
        $entrybyuser[$ee->userid] = $ee;
        $entrybyentry[$ee->id]  = $ee;
    }

} else {
    $entrybyuser  = array () ;
    $entrybyentry = array () ;
}

// Group mode
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);


/// Process incoming data if there is any
if ($data = data_submitted()) {

    confirm_sesskey();

    $feedback = array();
    $data = (array)$data;

    // Peel out all the data from variable names.
    foreach ($data as $key => $val) {
        if ($key <> "id") {
            $type = substr($key,0,1);
            $num  = substr($key,1);
            $feedback[$num][$type] = $val;
        }
    }

    $timenow = time();
    $count = 0;
    foreach ($feedback as $num => $vals) {
        $entry = $entrybyentry[$num];
        // Only update entries where feedback has actually changed.
        $rating_changed = false;
        if (($vals['r'] <> $entry->rating) && !($vals['r'] == '' && $entry->rating == "0")) {
          $rating_changed = true;
        }
        if (($rating_changed) || (addslashes($vals['c']) <> addslashes($entry->entrycomment))) {
            $newentry = new StdClass();
            $newentry->rating     = $vals['r'];
            $newentry->entrycomment    = $vals['c'];
            $newentry->teacher    = $USER->id;
            $newentry->timemarked = $timenow;
            $newentry->mailed     = 0;           // Make sure mail goes out (again, even)
            $newentry->id         = $num;
            if (!$DB->update_record("journal_entries", $newentry)) {
                notify("Failed to update the journal feedback for user $entry->userid");
            } else {
                $count++;
            }
            $entrybyuser[$entry->userid]->rating     = $vals['r'];
            $entrybyuser[$entry->userid]->entrycomment    = $vals['c'];
            $entrybyuser[$entry->userid]->teacher    = $USER->id;
            $entrybyuser[$entry->userid]->timemarked = $timenow;

            $journal = $DB->get_record("journal", array("id" => $entrybyuser[$entry->userid]->journal));
            $journal->cmidnumber = $cm->idnumber;

            journal_update_grades($journal, $entry->userid);
        }
    }

    // Trigger module feedback updated event.
    $event = \mod_journal\event\feedback_updated::create(array(
        'objectid' => $journal->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('journal', $journal);
    $event->trigger();

    notify(get_string("feedbackupdated", "journal", "$count"), "notifysuccess");

} else {

    // Trigger module viewed event.
    $event = \mod_journal\event\entries_viewed::create(array(
        'objectid' => $journal->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('journal', $journal);
    $event->trigger();
}

/// Print out the journal entries

if ($currentgroup) {
    $groups = $currentgroup;
} else {
    $groups = '';
}
$users = get_users_by_capability($context, 'mod/journal:addentries', '', '', '', '', $groups);

if (!$users) {
    echo $OUTPUT->heading(get_string("nousersyet"));

} else {

    groups_print_activity_menu($cm, $CFG->wwwroot . "/mod/journal/report.php?id=$cm->id");

    $grades = make_grades_menu($journal->grade);
    if (!$teachers = get_users_by_capability($context, 'mod/journal:manageentries')) {
        print_error('noentriesmanagers', 'journal');
    }

    echo '<form action="report.php" method="post">';

    if ($usersdone = journal_get_users_done($journal, $currentgroup)) {
        foreach ($usersdone as $user) {
            journal_print_user_entry($course, $user, $entrybyuser[$user->id], $teachers, $grades);
            unset($users[$user->id]);
        }
    }

    foreach ($users as $user) {       // Remaining users
        journal_print_user_entry($course, $user, NULL, $teachers, $grades);
    }

    echo "<p class=\"feedbacksave\">";
    echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"" . sesskey() . "\" />";
    echo "<input type=\"submit\" value=\"".get_string("saveallfeedback", "journal")."\" />";
    echo "</p>";
    echo "</form>";
}



/*
* FIX DISI
*/
$i = 1;
echo '
<style>
    .black_overlay{
        display: none;
        position: absolute;
        top: 0%;
        left: 0%;
        width: 100%;
        height: 100%;
        background-color: black;
        z-index:1001;
        -moz-opacity: 0.8;
        opacity:.80;
        filter: alpha(opacity=80);
    }
    .white_content {
        display: none;
        position: absolute;
        top: 25%;
        left: 25%;
        width: 50%;
        height: 50%;
        padding: 16px;
        border: 16px solid gray;
        background-color: white;
        z-index:1002;
        overflow: auto;
    }
    #light_text{
      color:red;
    }

.speech-mic {
    background: url("img/mic.gif") no-repeat 50% 50%;
    background-size: contain;
}

.speech-mic-works {
    background: url("img/mic-animate.gif") no-repeat 50% 50%;
    background-size: contain;
}
</style>
<script>
function getSel(e){
    var text = "";
    var obj = window.getSelection().getRangeAt(0);
    $("#startOffset_1").val(obj.startOffset);
    $("#endOffset_1").val(obj.endOffset);
        
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    
    $("#light_text").text(text);
}
function jsubmit(){
  $.post("ajax.php", { iid: $("#iid_1").val(), rid: $("#filewav_1").val(), text: $("#light_text").text(), startOffset: $("#startOffset_1").val(), endOffset: $("#endOffset_1").val()}, function(html){
    $("#"+$("#idoftext_1").val()).html(html);
  });
}
</script>
<div id="light" class="white_content">
  <div style="margin-left: 640px;"><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'none\';document.getElementById(\'fade\').style.display=\'none\'"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAABRUlEQVQ4T63USUpEMRDG8X+v1PvoGXrh3glth4ULF4IgXsKFSiMiOIvzgK7Es9mKfJCSMl3Je4JZdiW/1EtVdYd/Xh3njQIHwBtw3/KeFWACWAc+dMZAYS9AF/gC5oGbBnQVOEx7noBZoQaeAz0HNKEes2N9ZWqgMrpwGWuT0AXgOss0wj6BKeDZv+EScBKgyvwqoSVMn/vg39CSWAaOA3QNGAN2smyV2Q8WgfpNlTvK0Kg+Q1gJbIOGWA1UTL21V2gdvetlFPNFyeObwHYB1Fuf/QVUEfYrjV3s0yjDJszuCdEcjDAVQJVX22jW/Zmh5vfBEjYDPKa0FoHTWvMbGE3AABCmwferhM4BdwbqVo2erRJm8QjdBTYMHEmZTALCpjXolSor5NHb9Ecy8G8oVLP6Drw2YBbWZ44DWymRXxVradS3fQPJ71AVG4hrnAAAAABJRU5ErkJggg==" /></a></div>
  
  <div><span>Text:</span> <span id="light_text"></span></div>
  
  <div>
  <div id="answerbox_'.$i.'">
  <div class="fitem femptylabel"><div class="fitemtitle"><div class="fstaticlabel"><label>Your comment</label></div></div>
  <div class="felement fstatic">
            <div style="float:left;width: 220px;">
            <div id="speech-content-mic_'.$i.'" class="speech-mic" style="float:left;width: 45px;height: 45px;margin-top: -8px;"></div>
            <button onclick="startRecording('.$i.');" data-url="speech-content-mic_'.$i.'" id="journal_rec_btn">record</button>
            <button onclick="stopRecording(this, '.$i.');" data-url="speech-content-mic_'.$i.'" id="journal_stop_btn" disabled>stop</button>
            <img src="img/ajax-loader.gif" id="loader_'.$i.'" style="margin-top: -10px;display:none;"/>
            <input type="hidden" name="filewav['.$i.']" value="" id="filewav_'.$i.'"/>
            <input type="hidden" name="iid['.$i.']" value="" id="iid_'.$i.'"/>
            <input type="hidden" name="idoftext['.$i.']" value="" id="idoftext_'.$i.'"/>
            <input type="hidden" name="startOffset['.$i.']" value="" id="startOffset_'.$i.'"/>
            <input type="hidden" name="endOffset['.$i.']" value="" id="endOffset_'.$i.'"/>
  </div><div id="recording_'.$i.'" style="float:left;"></div><div style="clear:both;"></div><div style="clear:both;"></div></div></div></div>
  </div>
  
  <div><input type="button" value="Submit" onclick="jsubmit();document.getElementById(\'light\').style.display=\'none\';document.getElementById(\'fade\').style.display=\'none\';" /></div>
</div>
<div id="fade" class="black_overlay"></div>';



echo $OUTPUT->footer();
