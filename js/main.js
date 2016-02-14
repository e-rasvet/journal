window.recordmark = 0;
window.active     = 1;

try {
    var recognition = new webkitSpeechRecognition();
} catch(e) {
    var recognition = Object;
}
recognition.continuous = true;
recognition.interimResults = true;
recognition.lang = "en";

var interimResult = '';

recognition.onresult = function (event) {
    window.recordmark = 1;
};

recognition.onstart = function() {
    $('#speech-content-mic_'+window.active).removeClass('speech-mic').addClass('speech-mic-works');
    $('#answer_'+window.active).focus();
    window.recordmark = 1;
};

recognition.onend = function() {
    $('#speech-content-mic_'+window.active).removeClass('speech-mic-works').addClass('speech-mic');
    window.recordmark = 0;
};




function recordSTT(ids){
    //console.log("mark:"+window.recordmark);
    if (window.recordmark == 0) {
      //console.log('started '+ids);
      recognition.start();
      window.active = ids;
      window.recordmark = ids;
    } else {
      recognition.stop();
      window.recordmark = 0;
    }
}



$(document).ready(function() {
  $(".sassessment_rate_box").change(function() {
    var value = $(this).val();
    var data  = $(this).attr("data-url");
    
    var e = $(this).parent();
    e.html('<img src="img/ajax-loader.gif" />');
    
    $.get("ajax.php", {act: "setrating", data: data, value: value}, function(data) {
      e.html(data); 
    });
  });
 });