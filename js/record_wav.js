

//Record to file wav
  var audio_context;
  var recorder;

  function startUserMedia(stream) {
    var input = audio_context.createMediaStreamSource(stream);
    
    recorder = new Recorder(input);
  }

  function startRecording(itemid) {
    recordSTT(itemid);
    
    recorder && recorder.record();
    $('#journal_rec_btn').attr('disabled','disabled');
    $('#journal_stop_btn').removeAttr('disabled');
    //console.log($(button).attr("data-url"));
    $('#journal_rec_btn').parent().find(".speech-mic").removeClass('speech-mic').addClass('speech-mic-works');
  }

  function stopRecording(button, itemid) {
    $("#loader_"+itemid).show();
    
    recordSTT(itemid);
    
    recorder && recorder.stop();
    button.disabled = true;
    button.previousElementSibling.disabled = false;
    
    $(button).parent().find(".speech-mic-works").removeClass('speech-mic-works').addClass('speech-mic');
    
    // create WAV download link using audio data blob
    createDownloadLink(itemid);
    
    recorder.clear();
  }

  function createDownloadLink(ids) {
    recorder && recorder.exportWAV(function(blob) {
      var url = URL.createObjectURL(blob);
      var li = document.createElement("div");
      var au = document.createElement("audio");
      //var hf = document.createElement("a");
      
      au.controls = true;
      au.src = url;
      //hf.href = url;
      //hf.download = new Date().toISOString() + ".wav";
      //hf.innerHTML = hf.download;
      li.appendChild(au);
      //li.appendChild(hf);
      $("#recording_"+ids).html(li);
      
      var fd = new FormData();
      fd.append('fname', 'audio.wav');
      fd.append('id', $("input[name='id']").val());
      fd.append('data', blob);
      
      $.ajax({
          type: 'POST',
          url: '/moodle/mod/journal/upload.php',
          data: fd,
          processData: false,
          contentType: false
      }).done(function(data) {
          obj = JSON.parse(data);
          console.log(data);
          $("#loader_"+ids).hide();
          $("#filewav_"+ids).val(obj.id);
          //$("#recording_text_"+ids).html(obj.text);
          //$("#filetext_"+ids).val(obj.text);
      });
      
    });
  }

  window.onload = function init() {
    try {
      // webkit shim
      window.AudioContext = window.AudioContext || window.webkitAudioContext;
      navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia;
      window.URL = window.URL || window.webkitURL;
      
      audio_context = new AudioContext;
    } catch (e) {
      alert("No web audio support in this browser!");
    }
    
    navigator.getUserMedia({audio: true}, startUserMedia, function(e) {

    });
  };
  
  
  

