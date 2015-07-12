[![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/logo-120x60.png "MovieMasher.com")](http://moviemasher.com)
**[moviemasher.js](https://github.com/moviemasher/moviemasher.js "stands below angular-moviemasher, providing audiovisual playback handling and edit support in a web browser") | angular-moviemasher | [moviemasher.rb](https://github.com/moviemasher/moviemasher.rb "sits behind angular-moviemasher, providing processor intensive video transcoding services through a simple API")**

*Example deployment of moviemasher.js and moviemasher.rb utilizing AngularJS, Bootstrap and PHP*
#angular-moviemasher

Use angular-moviemasher to integrate audio/video editing features into your existing web site or as a starting point for further development. It builds upon both the moviemasher.js and moviemasher.rb projects, as well as the popular PHP middleware layer and AngularJS+Bootstrap for the UI (only Bootstrap's CSS is used, so no reliance on jQuery).

- **empower** your site visitors to edit high quality video and audio
- **integrate** with your existing authentication and storage mechanisms
- **customize** the user interface, workflow and available media

![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/ui.jpg "User Interfacee")

### Overview
The project includes an AngularJS module that manifests a time-based video editing interface, as well as a set of server-side PHP scripts it interacts with. Both the client and server sides are effectively abstractions, sheltering you from the complexity of the underlying moviemasher.js and moviemasher.rb projects respectively.

- **Docker Image:** `moviemasher/angular-moviemasher` [(Dockerfile)](Dockerfile)

The client module wraps around a player object instanced from moviemasher.js, which provides the preview and maintains undo/redo history. The module adds familiar play/pause, volume and associated controls to the player as well as constructing a media browser, timeline and inspector panel. Files from the user's machine can be dropped into the media browser to initiate uploading and preprocessing, and there is a render button that will initiate encoding of the mash into true video format.

The PHP scripts wrap around and monitor the API provided by moviemasher.rb, which handles processor intensive transcode operations like preprocessing of uploads and rendering of mashes. The scripts add basic content management functionality, first authenticating users then storing and retrieving their data.

### AWS Integration
The system *optionally* supports [Amazon Web Services](http://aws.amazon.com) for media storage and job queueing, through several PHP configuration settings. If set to use an S3 bucket then users will securely upload files directly there without stressing your web server - each request is signed with your key so the bucket can remain read-only to the public. If set to use an SQS queue then jobs are placed in them instead of written to the local queue directory. Callbacks are also added to the job description so job progress can be monitored.

Additionally, the Movie Masher AMI is available in Marketplace and includes all three projects preconfigured to run together as a standalone deployment for testing and building upon. Launching it from their console is by far the simplest way to see the whole system in action - no coding or set up required. It can also be launched with user data in a headless mode with only moviemasher.rb configured to process jobs found in an SQS queue. This allows the AMI to be used in a pooled cluster that can automatically scale up and down depending on the number of jobs pending in the SQS queue.

### Docker Usage
The [`moviemasher/angular-moviemasher`](https://registry.hub.docker.com/u/moviemasher/angular-moviemasher/) image on [docker.com](https://docker.com) is based off the official [`php:apache`](https://registry.hub.docker.com/_/php/) image, adding some configuration and copying this project into web root. The Dockerfile contains a **VOLUME** instruction for each directory it works with.

- To make the web site available at your docker IP on port 8080:

    `docker run -d -p 8080:80 --name=angular moviemasher/angular-moviemasher`

    You'll need to subsequently execute `docker stop angular` and `docker rm angular` to stop serving the web site and remove the container created.

All functions should be available at this juncture, except uploading and rendering which will trigger the saving of a job description file into **queue_directory**. Because there is a **VOLUME** instruction for this directory, it can be mounted by other containers - we'll attach it to one run from the [`moviemasher/moviemasher.rb`](https://registry.hub.docker.com/u/moviemasher/moviemasher.rb/) image which will handle the actual transcoding operation.

- To process queued jobs using shared volumes:

	`docker run -d -t --volumes-from=angular --name=moviemasher moviemasher/moviemasher.rb process_loop`

  Note the `t` switch - it's required for Ecasound to function properly. You'll need to subsequently execute `docker stop moviemasher` and `docker rm moviemasher` to stop queue processing and remove the container created. See the [moviemasher.rb](https://github.com/moviemasher/moviemasher.rb) project for other ways to run its image.


### How to Install on Your Web Host

It's important to remember this project is just an *example* deployment and not intended to be a fully functional user-driven site out of the box. For instance, by default it uses HTTP authentication that accepts any username and password combination - hardly a recommended mechanism! It also stores all data locally in JSON files instead of using a database or other mechanism.

1. edit the config/moviemasher.ini file and place outside your web root directory
2. add its parent directory to PHP's include_path configuration option somehow or edit app/php/include/authutils.php to specify its full path
3. if `file` option is 's3' then create the s3 bucket
4. or if `file` option is 'local' then change the permissions of the directory specified in the `user_media_directory` option such that the web server can work with it
5. if `client` option is 'sqs' then create the sqs queue, and set `sqs_queue_url` option in moviemasher.rb configuration
6. or if `client` option is 'local' then set `queue_directory` in moviemasher.rb and change its permissions so the web server can work with it
7. change the permissions of the directories specified in `temporary_directory` and `user_data_directory` such that the web server process can work with them
8. install the app, bower_components and dist directories somewhere under web root and load index.html in a web browser

### Further Customization
- change the interface by editing app.css and HTML fragments in the views directory
- add custom fonts and a font.json file for each describing the `font` module to the module directory
- override authentication mechanisms in app/php/include/authutils.php
- override data storage mechanisms in app/php/include/datautils.php
- create your own `theme`, `effect`, `transition`, `scaler` or `merger` modules and add them to the module directory with a corresponding .json file describing each one

### Porting from PHP to Other Languages
Each of the PHP endpoints requested by the JavaScript is configurable within the index.html file, so it can be overriden to point to scripts in other languages. The __default_config variable declaration near the top of the angular-moviemasher.js script file contains the PHP endpoints - note the nesting paths for the ones you want to change. These paths can be dash delimited and used as attributes within the main amm-ui tag to override values. For instance, the following will cause just the media metadata to be loaded from a Coldfusion endpoint:
`<div class='amm-ui' amm-rest-media-search-url='cfm/media.cfm?group=:group'></div>`

### Included Requirements
- angular
- angular-bootstrap-colorpicker
- angular-file-upload
- angular-resource
- bootstrap
- opentype.js
- script.js
- moviemasher.js

### User Feedback
If any problems arise while utilizing this repository, a [GitHub Issue Ticket](https://github.com/moviemasher/angular-moviemasher/issues) should be filed. Please include the job or mash description that's causing problems and any relevant log or console entries - issues with callbacks can typically be resolved faster after seeing entries from the receiving web server's log. Please post your issue ticket in the appropriate repository and refrain from cross posting - all projects are monitored with equal zeal.

### Contributing
Please join in the shareable economy by gifting your efforts towards improving this project in any way you feel inclined. Pull requests for fixes, features and refactorings are always appreciated, as are documentation updates. Creative help with graphics, video and the web site is also needed. Please contact through [MovieMasher.com](https://moviemasher.com) to discuss your ideas.

### Developer Setup
Various components of angular-moviemasher can be updated or rebuilt after installing git, npm, bower, grunt and composer. Once applications are installed `cd` to project directory and execute:

1. npm install
2. bower install
3. grunt
4. cd app/php/service/aws
5. composer install

Or if docker is being used, a helpful development version of the image can be built by uncommenting the last section in Dockerfile. These commands add the components.

- To build and run a development image, `cd` to project directory and execute:

  `sed -i '' 's/^## //' Dockerfile`

  `docker build --tag="moviemasher/angular-moviemaser:dev" .`

  `docker run -d -v $(pwd):/var/www/html/angular-moviemasher --name=angular moviemasher/angular-moviemaser:dev`

##### Known issues in this version
- timeline allows clips to be positioned atop one another
- uploads can only be dragged into browser panel
- freeze frame not yet supported
- cut/copy/paste not yet supported

##### Migrating from Version 1.0.1
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

