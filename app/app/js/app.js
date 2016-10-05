var mainapp = angular.module("testapp" , ['ngCamRecorder']);


mainapp.controller("angular.moviemasher" , function ($scope) {

    var configuration  = {
            init : $scope.initiateRecord,
            recConf:{   
                recorvideodsize : 0.4, 
                webpquality     : 0.7, 
                framerate       : 15,  
                videoWidth      : 500,
                videoHeight     : 375,            
            },

            recfuncConf :{
                showbuton : 2000,
                url : "http://localhost/ngCam/js/modules/vaRecorder/php/fileupload.php",
                chunksize : 1048576,
                recordingtime : 17,
                requestparam : "filename",
                videoname : "video.webm",
                audioname : "audio.wav", 
            },

            output :{
                recordingthumb : null,
                recordinguploaded : function(){
                    alert("uploaded");
                }
            },

            recordingerror : function(){
                alert("browser not compatible");
            }      
    }
    $scope.camconfiguration = configuration;
});