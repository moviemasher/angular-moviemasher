[![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/logo-120x60.png "MovieMasher.com")](http://moviemasher.com)
**[moviemasher.js](https://github.com/moviemasher/moviemasher.js "stands below angular-moviemasher, providing audiovisual playback handling and edit support in a web browser") | angular-moviemasher | [moviemasher.rb](https://github.com/moviemasher/moviemasher.rb "sits behind angular-moviemasher, providing processor intensive video transcoding services through a simple API")**

*Example deployment of moviemasher.js and moviemasher.rb utilizing AngularJS, Bootstrap and PHP*
#angular-moviemasher

Use angular-moviemasher to integrate audio/video editing features into your existing web site or as a starting point for further development. It builds upon both the moviemasher.js and moviemasher.rb projects, as well as the popular PHP middleware layer and AngularJS+Bootstrap for the UI (only Bootstrap's CSS is used).

- **empower** your site visitors to edit high quality video and audio
- **integrate** with your existing authentication and storage mechanisms
- **customize** the user interface, workflow and available media

![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/ui.jpg "User Interfacee")

The project includes an AngularJS module that manifests a time-based video editing interface, as well as a set of server-side PHP scripts it interacts with. Both the client and server sides are effectively abstractions, sheltering you from the complexity of the underlying moviemasher.js and moviemasher.rb projects respectively. 

The client module wraps around a player object instanced from moviemasher.js, which provides the preview and maintains undo/redo history. The module adds familiar play/pause, volume and associated controls to the player as well as constructing a media browser, timeline and inspector panel. Files from the user's machine can be dropped into the media browser to initiate uploading and preprocessing, and there is a render button that will initiate encoding of the mash into true video format. 

The PHP scripts wrap around and monitor the API provided by moviemasher.rb, which handles processor intensive transcode operations like preprocessing of uploads and rendering of mashes. The scripts add basic content management functionality, first authenticating users then storing and retrieving their data. 

### Amazon Web Services Integration
There are optional PHP configuration settings that support two specfic AWS offerings: S3 for media storage and SQS for job queueing. If set to use an S3 bucket then users will securely upload files directly there without stressing your web server - each request is signed with your key so the bucket can remain read-only to the public. If set to use an SQS queue then jobs are placed in them instead of written to the local queue directory. Callbacks are also added to the job description so job progress can be monitored. 

Additionally, the Movie Masher AMI is available in Marketplace and includes all three projects preconfigured to run together as a standalone deployment for testing and building upon. Launching it from their console is by far the simplest way to see the whole system in action - no coding or set up required. It can also be launched with user data in a headless mode with only moviemasher.rb configured to process jobs found in an SQS queue. This allows the AMI to be used in a pooled cluster that can automatically scale up and down depending on the number of jobs pending in the SQS queue. 


### How to Install on Your Web Host

It's important to remember this project is just an *example* deployment and not intended to be a fully functional user-driven site out of the box. For instance, by default it uses HTTP authentication that accepts any username and password combination - hardly a recommended mechanism! It also stores all data locally in JSON files instead of using a database or other mechanism. 

1. edit the config/moviemasher.ini file and place outside your web root directory
2. add its parent directory to PHP's include_path configuration option somehow or edit app/php/include/authutils.php to specify its full path
3. if `file` option is 's3' then create the s3 bucket
4. or if `file` option is 'local' then change the permissions of the directory specified in the `user_media_directory` option such that the web server can work with it
5. if `client` option is 'sqs' then create the sqs queue, and set `sqs_queue_url` option in moviemasher.rb configuration
6. or if `client` option is 'local' then set `queue_directory` in moviemasher.rb and change its permissions so the web server can work with it
7. change the permissions of the directories specified in `temporary_directory` and `user_data_directory` such that the web server process can work with them
8. install the app directory somewhere under web root and load index.html in a web browser

### Further Customization
- change the interface by editing app.css and HTML fragments in the views directory
- add custom fonts and a font.json file for each describing the `font` module to the module directory
- override authentication mechanisms in app/php/include/authutils.php 
- override data storage mechanisms in app/php/include/datautils.php
- create your own `theme`, `effect`, `transition`, `scaler` or `merger` modules and add them to the module directory with a corresponding .json file describing each one

### Included Requirements 
- angular
- angular-bootstrap-colorpicker
- angular-file-upload
- angular-resource
- bootstrap
- Font.js
- moviemasher.js

### Developer Setup
1. install git, npm, bower and grunt
2. npm install
3. bower install
4. grunt

##### Known issues in Version 1.0.02
- timeline allows clips to be positioned atop one another
- uploads can only be dragged into browser panel
- freeze frame not yet supported
- cut/copy/paste not yet supported

##### Migrating from Version 1.0.01
- The `begin` key in video clips has been renamed `first`.
- The `length` key in clips has been renamed `frames`.
- The `audio` and `video` keys in mash tracks have been moved to mash.
- The `tracks` key in mashes has been removed. 
- The `fps` key in job outputs has been renamed `video_rate`.
- The `export_fps` key in moviemasher.ini has been renamed `export_video_rate`.
- The `audio_frequency` key in job outputs has been renamed `audio_rate`.
- The `export_audio_frequency` key in moviemasher.ini has been renamed `export_audio_rate`.
- The new `mash` key in mash inputs should be used for embedded mashes
- The `source` key in mash inputs should only contain a source object

