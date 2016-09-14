/*
configuration :{

    init : function that initiate recorder,
    recConf:{   
                    recorvideodsize : 0.4, 
                    webpquality     : 0.7, 
                    framerate       : 15,  
                    videoWidth      : "500",
                    videoHeight     : "375",            
    },

    recfuncConf :{
      
        showbuton : 2000,
        "url" : "http://localhost/angulrjstest/SignerView/js/lib/Html5Recorder/php/fileupload.php",
        "chunksize" : 1048576,
        "recordingtime" : 17,
        "requestparam" : "filename",
        "videoname" : "video.webm",
        "audioname" : "audio.wav", 

    },

    output :{
      
        recordingthumb : ""

    }

    recordingerror : function that calls when recording error occures

}


*/



var vaRecorder = angular.module("ngCamRecorder" , []);
vaRecorder.directive("ngcamcecorder" , function(){

    return{

        scope : {
            configuration : "=",
        },
        
        templateUrl : function(){
            var scripts = document.getElementsByTagName("script");
            src = scripts[scripts.length-1].src;
            src = src.substring(0, src.lastIndexOf("/"));
            return src+"/template/viditurecam.html";
        },

        link : function($scope, $element, $attrs){
                $scope.width =  $scope.configuration.recConf.videoWidth;
                $scope.height =  $scope.configuration.recConf.videoHeight;
                $scope.loadingimageshow = false;

                $scope.configuration.init = function(){
                        $scope.loadingimageshow  = true;
                        videorecorder($scope, $element, $attrs);
                }    
        }

    }


    function videorecorder($scope, $element, $attrs){

         var virec = new VIRecorder.initVIRecorder($scope.configuration.recConf ,
                function(){
                      //when video is ready 
                        setTimeout(function(){
                            $scope.$apply(function(){
                                $scope.loadingimageshow  = false;
                                $scope.videoready = true;
                            });
                        }, $scope.configuration.recfuncConf.showbuton);
                    
                },
                function(err){
                    //onerror callback, this will fire if browser does not support
                    $scope.configuration.recordingerror(err);
                }
         );


         $scope.startRecrodBut1 =  function(){
                $scope.recordingstarted = true;
                $scope.recordingonly = true;
                virec.startCapture(); 
                startCountDown(null);
         }
                
         $scope.stopRecBut1 = function(){
              if($scope.recordingstarted){
                $scope.recordingonly = false;
                $scope.recordingstarted = false;
                $scope.videoisavailabletoplay = true;
                virec.stopCapture(oncaptureFinish); 
              }
         }

         $scope.playback = function(){

              if($scope.videoisavailabletoplay){
                virec.play();
              } 
         }
        
         $scope.clearrecording =function(){
                $scope.videoisavailabletoplay = false;
                virec.clearRecording();
         }

        $scope.uploadrecord = function(){


              if(!$scope.recordingstarted){


                $scope.uploadpresentage = true;
                var uploadoptions = {
                        blobchunksize :         $scope.configuration.recfuncConf.chunksize,
                        requestUrl :            $scope.configuration.recfuncConf.url,
                        requestParametername :  $scope.configuration.recfuncConf.requestparam, 
                        videoname :             $scope.configuration.recfuncConf.videoname,
                        audioname :             $scope.configuration.recfuncConf.audioname
                };
                virec.uploadData( uploadoptions , function(totalchunks, currentchunk){
                      $scope.$apply(function(){
                            $scope.uploadpresentage = parseInt(((currentchunk/totalchunks)*100));
                            if(totalchunks == currentchunk){
                                    $scope.uploadpresentage =false;
                                     if($scope.configuration.output.recordinguploaded){
                                      $scope.configuration.output.recordinguploaded();
                                    }
                            }
                      });
                });
              }
         }
        

    //------------------------------- few functions that demo, how to play with the api --------------------------

    var countdowntime = $scope.configuration.recfuncConf.recordingtime;
    var functioncalltime = 0;

    function oncaptureFinish(audioblob, videoblob, framearray){
        var videobase64 = framearray[framearray.length/2];
        $scope.$apply(function(){
            $scope.configuration.output.recordingthumb = videobase64;
        });
    }

    function setCountDownTime(time){
         $scope.safeApply(function(){
            $scope.vicountervalue = time;   
         });
         
        if(time == 0){
            return -1;
        }else{
            return 1;
        }
    }

    
    function startCountDown(interval){
        if(interval == null){
            functioncalltime = countdowntime; 
            setCountDownTime(--functioncalltime); 
            var intervalcount = setInterval( function(){ startCountDown(intervalcount);  }, 1000 );
        }else{
           var val = setCountDownTime(--functioncalltime); 
           if(val == -1){
               clearInterval(interval);
                $scope.recordingonly = false;
               $scope.recordingstarted = false;
               virec.stopCapture(oncaptureFinish);
           }
        }
    }

    $scope.safeApply = function(fn) {
      var phase = this.$root.$$phase;
      if(phase == '$apply' || phase == '$digest') {
        if(fn && (typeof(fn) === 'function')) {
          fn();
        }
      } else {
        this.$apply(fn);
      }
    }
  }
});
