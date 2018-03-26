[![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/logo-120x60.png "MovieMasher.com")](http://moviemasher.com)
**[moviemasher.js](https://github.com/moviemasher/moviemasher.js "stands below angular-moviemasher, providing audiovisual playback handling and edit support in a web browser") | angular-moviemasher | [moviemasher.rb](https://github.com/moviemasher/moviemasher.rb "sits behind angular-moviemasher, providing processor intensive video transcoding services through a simple API")**

*Example deployment of moviemasher.js and moviemasher.rb utilizing AngularJS, Bootstrap and PHP*
# angular-moviemasher

Use angular-moviemasher to integrate audio/video editing features into your existing web site or as a starting point for further development. It builds upon both the moviemasher.js and moviemasher.rb projects, as well as the popular PHP middleware layer and AngularJS+Bootstrap for the UI (only Bootstrap's CSS is used, so no reliance on jQuery).

- **empower** your site visitors to edit high quality video and audio
- **integrate** with your existing authentication and storage mechanisms
- **customize** the user interface, workflow and available media

![Image](https://github.com/moviemasher/angular-moviemasher/raw/master/README/ui.jpg "User Interfacee")

**Docker Image:** `moviemasher/angular-moviemasher` [(Dockerfile)](Dockerfile)

### Overview
The project includes an AngularJS module that manifests a time-based video editing interface, as well as a set of server-side PHP scripts it interacts with. Both the client and server sides are effectively abstractions, sheltering you from the complexity of the underlying moviemasher.js and moviemasher.rb projects respectively.

The client module wraps around a player object instanced from moviemasher.js, which provides the preview and maintains undo/redo history. The module adds familiar play/pause, volume and associated controls to the player as well as constructing a media browser, timeline and inspector panel. Files from the user's machine can be dropped into the media browser to initiate uploading and preprocessing, and there is a render button that will initiate encoding of the mash into true video format.

The PHP scripts wrap around and monitor the API provided by moviemasher.rb, which handles processor intensive transcode operations like preprocessing of uploads and rendering of mashes. The scripts add basic content management functionality, first authenticating users then storing and retrieving their data.

### AWS Integration
The system *optionally* supports [Amazon Web Services](http://aws.amazon.com) for media storage and job queueing, through several PHP configuration settings. If set to use an S3 bucket then users will securely upload files directly there without stressing your web server - each request is signed with your key so the bucket can remain read-only to the public. If set to use an SQS queue then jobs are placed in them instead of written to the local queue directory. Callbacks are also added to the job description so job progress can be monitored.

Additionally, the Movie Masher AMI is available in Marketplace and includes all three projects preconfigured to run together as a standalone deployment for testing and building upon. Launching it from their console is by far the simplest way to see the whole system in action - no coding or set up required. It can also be launched with user data in a headless mode with only moviemasher.rb configured to process jobs found in an SQS queue. This allows the AMI to be used in a pooled cluster that can automatically scale up and down depending on the number of jobs pending in the SQS queue.

### Docker Usage
The [`moviemasher/angular-moviemasher`](https://registry.hub.docker.com/u/moviemasher/angular-moviemasher/) image on [hub.docker.com](https://hub.docker.com) is based off the official [`php:apache`](https://registry.hub.docker.com/_/php/) image, adding some configuration and copying this project into web root. It should be run alongside the [`moviemasher/moviemasher.rb`](https://registry.hub.docker.com/u/moviemasher/moviemasher.rb/) image which provides transcoding functionality. This project contains a docker-compose file that makes it easy to launch both and hook them together, following these steps:
1. clone or download this repository
1. `cd angular-moviemasher/config/docker/development`
1. `docker-compose up -d` (to bring the system up)
1. load http://localhost:8080 in your web browser to access the UI
1. any username/password combination will work
1. `docker-compose down -v` (to bring the system down)

Alternatively, you can just run the containers directly with docker:
1. `docker run -d -p 8080:80 --name=angular_moviemasher moviemasher/angular-moviemasher`
1. `docker run -d -t --volumes-from=angular_moviemasher --name=moviemasher_rb moviemasher/moviemasher.rb process_loop`
1. load http://localhost:8080 in your web browser to access the UI
1. any username/password combination will work
1. `docker stop angular_moviemasher moviemasher_rb`
1. `docker rm angular_moviemasher moviemasher_rb`

### Installation

It's important to remember this project is just an *example* deployment and not intended to be a fully functional, user-driven site out of the box. For instance, by default it uses HTTP authentication that accepts any username and password combination - hardly a recommended mechanism! It also stores all data locally in JSON files instead of using a database or other mechanism.

##### From ZIP archive

[Download the latest ZIP](https://github.com/moviemasher/moviemasher.js/archive/master.zip) and grab the compiled files in the `dist` folder. An example application is `app` and if you don't have the required modules installed they are in `node_modules`.

##### Using npm
- `npm install --save @moviemasher/angular-moviemasher`

### How to Install on Your Web Host
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
```html
<div class='amm-ui' amm-rest-media-search-url='cfm/media.cfm?group=:group'></div>
```
### Developer Setup
Several docker-compose files provide a simple mechanism to update dependencies.

##### To Update PHP AWS SDK
1. `cd config/docker/composer`
1. `docker-compose run --rm composer`

##### To Update NPM Modules
1. `cd config/docker/node`
1. `docker-compose run npm`

##### To Run Grunt after Making Changes to JavaScript Source
1. `cd config/docker/node`
1. `docker-compose run --rm grunt`

### User Feedback
If any problems arise while utilizing this repository, a [GitHub Issue Ticket](https://github.com/moviemasher/angular-moviemasher/issues) should be filed. Please include the job or mash description that's causing problems and any relevant log or console entries - issues with callbacks can typically be resolved faster after seeing entries from the receiving web server's log. Please post your issue ticket in the appropriate repository and refrain from cross posting - all projects are monitored with equal zeal.

### Contributing
Please join in the shareable economy by gifting your efforts towards improving this project in any way you feel inclined. Pull requests for fixes, features and refactorings are always appreciated, as are documentation updates. Creative help with graphics, video and the web site is also needed. Please contact through [MovieMasher.com](https://moviemasher.com) to discuss your ideas.

### Please Note
##### Known issues in this version
- timeline allows clips to be positioned atop one another
- uploads can only be dragged into browser panel
- freeze frame not yet supported
- cut/copy/paste not yet supported
