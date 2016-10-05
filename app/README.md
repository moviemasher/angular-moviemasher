[![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/logo-120x60.png "MovieMasher.com")](http://moviemasher.com)
**[moviemasher.js](https://github.com/moviemasher/moviemasher.js "stands below angular-moviemasher, providing audiovisual playback handling and edit support in a web browser") | angular-moviemasher | [moviemasher.rb](https://github.com/moviemasher/moviemasher.rb "sits behind angular-moviemasher, providing processor intensive video transcoding services through a simple API")**

*Example deployment of moviemasher.js and moviemasher.rb utilizing AngularJS, Bootstrap and PHP*
# angular-moviemasher

Use angular-moviemasher to integrate audio/video editing features into your existing web site or as a starting point for further development. It builds upon both the moviemasher.js and moviemasher.rb projects, as well as the popular PHP middleware layer and AngularJS+Bootstrap for the UI (only Bootstrap's CSS is used, so no reliance on jQuery).

- **empower** your site visitors to edit high quality video and audio
- **integrate** with your existing authentication and storage mechanisms
- **customize** the user interface, workflow and available media

![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/ui.jpg "User Interfacee")

### Overview: Player + Transcoder = Editor
The project includes an AngularJS module that manifests a time-based video editing interface, as well as a set of server-side PHP scripts it interacts with. Both the client and server sides are effectively abstractions, sheltering you from the complexity of the underlying moviemasher.js and moviemasher.rb projects respectively.

- **System Documentation:** [MovieMasher.com](http://moviemasher.com/docs/)
- **Docker Image:** `moviemasher/angular-moviemasher` [(Dockerfile)](Dockerfile)

The client module wraps around a player object instanced from moviemasher.js, which provides the preview and maintains undo/redo history. The module adds familiar play/pause, volume and associated controls to the player as well as constructing a media browser, timeline and inspector panel. Files from the user's machine can be dropped into the media browser to initiate uploading and preprocessing, and there is a render button that will initiate encoding of the mash into true video format.

The PHP scripts wrap around and monitor the API provided by moviemasher.rb, which handles processor intensive transcode operations like preprocessing of uploads and rendering of mashes. The scripts add basic content management functionality, first authenticating users then storing and retrieving their data.

### AWS Integration
The system *optionally* supports [Amazon Web Services](http://aws.amazon.com) for media storage and job queueing, through several PHP configuration settings. When using S3 storage, users will securely upload files directly there without stressing your web server - each request is signed with your key so the bucket can safely remain read-only to the public. When using an SQS queue, jobs are placed in it instead of written to the local queue directory. Callbacks are also added to the job description so the progress of transcoding operations can be monitored.

Those new to AWS will be interested in the [Movie Masher AMI](https://aws.amazon.com/marketplace/pp/B00QKW0P2A) available in Amazon Marketplace, which includes all three projects preconfigured to run together as a standalone deployment for testing and building upon. There is also a [Deployment Wizard](http://moviemasher.com/product/cloudformation/) which utilizes their CloudFormation service to easily create an S3 bucket, SQS queue, and instances of the same AMI within an autoscaling pool - all the  infrastructure required to deploy Movie Masher so it automatically adjusts to user demand.

### Docker Usage
Docker's [`moviemasher/angular-moviemasher`](https://registry.hub.docker.com/u/moviemasher/angular-moviemasher/) image is automatically built from the official [`php:apache`](https://registry.hub.docker.com/_/php/) image, adding some configuration as well as copying this project into web root. To make the UI available at your docker IP on port 8080:

- `docker run -d -p 8080:80 --name=angular_moviemasher moviemasher/angular-moviemasher`

All functions should be available at this juncture, except uploading and rendering which will just trigger the saving of a job description file into queue_directory. Because there is a VOLUME instruction for this directory, it can be mounted by other containers - we'll attach it to one run from the moviemasher/moviemasher.rb image which will handle the actual transcoding operation:

- `docker run -d -t --volumes-from=angular_moviemasher --name=moviemasher_rb moviemasher/moviemasher.rb process_loop`

To stop and remove the containers:
- `docker stop angular_moviemasher`
- `docker rm angular_moviemasher`
- `docker stop moviemasher_rb`
- `docker rm moviemasher_rb`

The project also includes several docker-compose files in version 2 format, so you might need to update your Docker installation in order to utilize them. The simplest does what the commands above do - after downloading the repository `cd` into the *config/docker/production* directory and execute:
- `docker-compose up -d`

To stop and remove the containers:
- `docker-compose down -v`

See Developer Setup below for several other helpful docker-compose files.

### How to Install on Your Web Host

It's important to remember this project is just an *example deployment* and not intended to be a fully functional, user-driven site out of the box. For instance, by default it uses HTTP authentication that accepts any username and password combination - hardly a recommended mechanism! It also stores all data locally in JSON files instead of using a database or other mechanism.

1. edit the config/moviemasher.ini file and place outside your web root directory
2. add its parent directory to PHP's include_path configuration option somehow or edit app/php/include/authutils.php to specify its full path
3. if `file` option is 's3' then create the s3 bucket
4. or if `file` option is 'local' then change the permissions of the directory specified in the `user_media_directory` option such that the web server can work with it
5. if `client` option is 'sqs' then create the sqs queue, and set `sqs_queue_url` option in moviemasher.rb configuration
6. or if `client` option is 'local' then set `queue_directory` in moviemasher.rb and change its permissions so the web server can work with it
7. change the permissions of the directories specified in `temporary_directory` and `user_data_directory` such that the web server process can work with them
8. install the app, bower_components and dist directories somewhere under web root and load index.html in a web browser

### Further Customization
- override authentication mechanisms in app/php/include/authutils.php
- override data storage mechanisms in app/php/include/datautils.php
- change the interface by editing app.css and HTML fragments in the views directory
- add custom fonts and a font.json file for each describing the `font` module to the module directory
- create your own `theme`, `effect`, `transition`, `scaler` or `merger` modules that utilize the filters from moviemasher.js and add them to the module directory with a corresponding .json file describing each one

### Porting from PHP to Other Languages
Each of the PHP endpoints requested by JavaScript is configurable within the index.html file, so it can be overriden to point to scripts in other languages. The `__default_config` variable declaration near the top of the angular-moviemasher.js script file contains the PHP endpoints - note the nesting paths for the ones you want to change. These paths can be dash delimited and used as attributes within the main amm-ui tag to override values. For instance, the following will cause just the media metadata to be loaded from a Coldfusion endpoint:
```html
<div class='amm-ui'
  amm-rest-media-search-url='cfm/media.cfm?group=:group'
></div>
```

### Included Requirements
- angular
- angular-bootstrap-colorpicker
- angular-file-upload
- angular-resource
- bootstrap
- moviemasher.js
- opentype.js
- script.js

### User Feedback
If any problems arise while utilizing this repository, a [GitHub Issue Ticket](https://github.com/moviemasher/angular-moviemasher/issues) should be filed. Please include the job or mash description that's causing problems and any relevant log or console entries - issues with callbacks can typically be resolved faster after seeing entries from the receiving web server's log. Please post your issue ticket in the appropriate repository and refrain from cross posting - all projects are monitored with equal zeal.

### Contributing
Please join in the shareable economy by gifting your efforts towards improving this project in any way you feel inclined. Pull requests for fixes, features and refactorings are always appreciated, as are documentation updates. Creative help with graphics, video and the web site is also needed. Please contact through [MovieMasher.com](https://moviemasher.com) to discuss your ideas.

#### Developer Setup
Docker is used extensively to develop this project, specifically to update components using standard tools like npm, bower, grunt, and composer. Though not routinely tested, these same tools might work outside Docker - just `cd` to project directory and execute:

- `npm install`

- `bower install --production`

- `grunt`

- `cd app/php/service/aws`

- `composer install`

Or if docker is being used `cd` into the *config/docker/grunt* or *config/docker/composer* directory and execute...

- `docker-compose run --rm grunt`

- `docker-compose run --rm grunt bower install --production`

- `docker-compose run --rm composer`

It's also possible to run Movie Masher entirely from source code by first downloading the other two Movie Masher projects into the same directory that contains this repository:

- [moviemasher.js](https://github.com/moviemasher/moviemasher.js)
- [moviemasher.rb](https://github.com/moviemasher/moviemasher.rb)

Then to make Movie Masher available at your Docker IP on port 8080, `cd` into the *config/docker/development* directory and execute:

- `docker-compose up -d`

This essentially does the same as the `production` docker-compose command above, but actually builds special `development` images from source. It also mounts the relevant directories from each project, so changes to them can be made during runtime. Changes made to JavaScript typically require grunt be run (see above) and changes made to moviemasher.rb require that its container be restarted:

- `docker-compose restart moviemasher_rb`

If any errors are encountered during transcoding, the job related files from moviemasher.rb will all be in `tmp/error`, including its log. And there might be additional information in the logs for both projects in the `log` directory.

To stop and remove the containers:

- `docker-compose down -v`

#### Known issues in this version
- timeline allows clips to be positioned atop one another
- uploads can only be dragged into browser panel
- freeze frame not yet supported
- cut/copy/paste not yet supported
- a more angular approach could be taken with the codebase

#### Migrating from Version 1.0.1
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
