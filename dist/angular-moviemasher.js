/*! angular-moviemasher - v1.0.23 - 2021-01-05
* Copyright (c) 2021 Movie Masher; Licensed  */
/*globals MovieMasher:true, angular:true*/
(function(){
  'use strict';
  var __default_config = {
    rest: {
      media: {
        search: {
          url: 'php/media.php?group=:group',
          params: {group: '@group'},
          method: 'get',
          isArray: true,
        },
      },
      module: {
        search: {
          url: 'php/media.php?group=:group',
          params: {group: '@group'},
          method: 'get',
          isArray: true,
        },
      },
      mash: {
        search: {
          url: 'php/media.php?group=mash',
          method: 'get',
          isArray: true,
        },
        data: {
          url: 'php/mash.php?id=:id',
          params: {id: '@id'},
          method: 'get',
        },
        save: {
          url: 'php/save.php?id=:id',
          params: {id: '@id'},
          method: 'post',
        },
      },
      import: {
        api: {
          url: 'php/import_api.php',
          method: 'post',
        },
        init: {
          url: 'php/import_init.php',
          method: 'post',
        },
        monitor: {
          url: 'php/import_monitor.php',
          method: 'post',
        },
      },
      export: {
        init: {
          url: 'php/export_init.php',
          method: 'post',
        },
        monitor: {
          url: 'php/export_monitor.php',
          method: 'post',
        },
      },
    }
  };
  var __globalx_to_local = function(x, element) {
    return x - element[0].getBoundingClientRect().left;
  };
  var __indexOf = function(a, v){
    var i, z;
    if (a) {
      if (a.indexOf) return a.indexOf(v);
      z = a.length;
      for (i = 0; i < z; i++) {
        if (a[i] === v) return i;
      }
    }
    return -1;
  };
  var __next_directive_element = function(target){
    var directive;
    while(target){
      directive = target.data('amm-directive');
      if (directive) break;
      target = target.parent();
    }
    return target;
  };
  var __php_parsed_error = function(response){
    // console.error('response error', response);
    var key = 0, value, values = [];
    value = response[key];
    while(value){
      values.push(value);
      key++;
      value = response[key];
    }
    return values.join('');
  };
  var module = angular.module('angular.moviemasher', [
    'ngResource',
    'ngAnimate',
    'colorpicker.module',
    'angularFileUpload',
    'ui.bootstrap',
  ]); // angular.moviemasher
  module.config([
    '$httpProvider',
    function ($httpProvider) {
      $httpProvider.defaults.useXDomain = true;
      $httpProvider.defaults.withCredentials = true;
      //delete $httpProvider.defaults.headers.common['X-Requested-With'];
    }
  ]);// CONFIG CORS
  module.constant('amm_resources', {});
  var __init_rest = function(config, amm_resources, $resource){
    if (! config) config = __default_config;
    var action_config, action, actions, service_id, default_config, override_config;
    for (service_id in __default_config.rest) {
      default_config = __default_config.rest[service_id];
      override_config = config.rest[service_id];
      if ((default_config === override_config) || (true === override_config)) override_config = default_config;
      if (override_config) {
        actions = {};
        for (action in override_config) {

          // console.log(action, default_config[action]);
          action_config = {};
          if (default_config[action].isArray) action_config.isArray = true;
          action_config.method = default_config[action].method;
          action_config.url = override_config[action].url;
          if (override_config[action].params) action_config.params = override_config[action].params;
          actions[action] = action_config;
        }
        amm_resources[service_id] = $resource(null, null, actions);
      }
    }
  };
  module.config([
    '$provide',
    function ($provide) {
      var inject = ['$http'];
      var service = MovieMasher; // moviemasher.js needs to have been included by now
      var pt = service.prototype;
      service.$inject = inject;
      pt.initialize = function() {
        // for convenience in our templates
        this.registered = service.registered;
        this.supported = service.supported;
        var i, z = inject.length;
        this.amm_injected = {};
        for (i = 0; i < z; i++) {
          this.amm_injected[inject[i]] = this.instance_arguments[i];
        }
      };
      pt.__player = null;
      Object.defineProperty(pt, "player", {
        get: function() {
          if (! this.__player) {
            this.__player = service.player();
            // console.log('$amm.player created', this.__player, this.amm_injected.$http);
          }
          return this.__player;
        },
        set: function(obj) {
          this.__player = obj;
          // console.log('player=', this.__player);
        }
      });
      $provide.service('$amm', service);
    }
  ]); // $amm
  module.directive('ammUi', ['$amm', 'amm_resources', '$resource', function($amm, amm_resources, $resource){
    return {
      templateUrl: 'views/ui.html',
      restrict: 'AEC',
      controller: [
        '$scope', '$window', '$interval', 'amm_resources',
        function($scope, $window, $interval, amm_resources) {
          $scope.amm_resources = amm_resources;
          var __action_index = -1;
          var __mash_for_id = function(id){
            var i, z = $amm.mashes.length;
            for (i = 0; i < z; i++) {
              if ($amm.mashes[i].id === id) return $amm.mashes[i];
            }
            return false;
          };
          var __unsaved_mashes = {};
          var MAX_EXPORT_MONITOR_ERRORS = 5;
          var __monitor_export = function(monitor, mash_id) {
            var requested = false;
            var interval_id = 0;
            var error_count = 0;
            interval_id = $interval(function() {
              if (! requested) {
                requested = true;
                // console.log('Calling export.monitor', monitor);
                amm_resources.export.monitor(monitor, function(export_monitor_response){
                  // console.log('export.monitor', export_monitor_response);
                  requested = false;
                  if (export_monitor_response.ok) {
                    if ((export_monitor_response.completed > 0) && (export_monitor_response.completed < 1)) {
                      $scope.amm_export_completed = Math.max(0.03, export_monitor_response.completed);
                    } else {
                      $interval.cancel(interval_id);
                      if (export_monitor_response.completed === 1) {
                        var m = __mash_for_id(mash_id);
                        if (m) m.source = export_monitor_response.source;
                        // console.log('OK', mash_id, m, m.source);
                        $scope.amm_export_completed = 0;
                      }
                    }
                  }
                  else {
                    $scope.amm_export_status = (export_monitor_response.error || __php_parsed_error(export_monitor_response));
                    $scope.amm_export_completed = -1;
                    $interval.cancel(interval_id);
                  }
                }, function(export_err_response){
                  // console.warn('export.monitor', export_err_response);
                  requested = false;
                  if (! export_err_response.status) error_count++;
                  if (error_count > MAX_EXPORT_MONITOR_ERRORS) {
                    $scope.amm_export_status = String(error_count) + ' error responses from export monitor cgi';
                    $scope.amm_export_completed = -1;
                    $interval.cancel(interval_id);
                  }
                });
              }
            }, 5 * 1000);
          };
          $amm.player.did = function(removed_count){
            // callback for whenever action redone or undone
            if (removed_count && (__action_index >= $amm.player.action_index)) {
              // console.log('did - save enabled until next called', removed_count, __action_index, $amm.player.action_index);
              __action_index = -2;
            }
          };
          $scope.amm_export_displayed_status = function(){
            $scope.amm_export_completed = 0;
          };
          $scope.amm_export_completed = 0;
          $scope.amm_import_completed = 0;
          $scope.amm_export_status = '';
          $scope.amm_import_status = '';
          $amm.render = function(){
            var rendering_mash_id = $amm.mash_id;
            if (amm_resources.export) {
              $scope.amm_export_completed = 0.01;
              amm_resources.export.init({id: rendering_mash_id}, function(export_init_response){
                // console.log('export.init', export_init_response);
                if (export_init_response.ok) {
                  $scope.amm_export_completed = 0.02;
                  __monitor_export(export_init_response, rendering_mash_id);
                } else {
                  $scope.amm_export_status = (export_init_response.error || __php_parsed_error(export_init_response));
                  $scope.amm_export_completed = -1;
                }
              }, function(export_init_error_response) {
                console.error('export.init', export_init_error_response);
                $scope.amm_export_status = 'error response from export init';
                $scope.amm_export_completed = -1;
              });
            }
          };
          $amm.can = function(action){
            var could = false;
            switch(action){
              case 'render': {
                could = (amm_resources.export && $amm.player.duration && (! $amm.can('save')));
                break;
              }
              case 'save': {
                could = (amm_resources.mash && amm_resources.mash.save && (__action_index !== $amm.player.action_index));
                break;
              }
            }
            return could;
          };
          $amm.save = function(and_load_mash_id){
            if (amm_resources.mash && amm_resources.mash.save){
              var saving_mash_id = $amm.mash_id;
              var action_index = $amm.player.action_index;
              // console.log('calling mash.save', saving_mash_id, $amm.player.mash);
              amm_resources.mash.save({id: saving_mash_id}, $amm.player.mash, function(save_response){
                // console.log('mash.save', save_response);
                if (save_response.ok) {
                  if (saving_mash_id === $amm.mash_id) {
                    __action_index = action_index;
                    if (__unsaved_mashes[saving_mash_id]) delete __unsaved_mashes[saving_mash_id];
                    var m = __mash_for_id(saving_mash_id);
                    if (m) m.label = $amm.player.mash.label;
                    if (and_load_mash_id) {
                      $amm.mash_id = and_load_mash_id;
                       $amm.mash_id_change();
                    }
                  }
                } else $window.alert(save_response.error || __php_parsed_error(save_response));
              });
            }
          };
          $scope.amm_view_mash = function(){
            $window.open($amm.selected_mash.source);
          };
          $amm.mash_id_change = function(){
            // console.log('amm.mash_id_change', $amm.mash_id);
            var selected_mash_id = $amm.mash_id;
            var current_mash_id = $amm.player.mash.id;
            if ($amm.can('save')){
              $amm.mash_id = current_mash_id;
              if ($window.confirm('Save changes to "' + $amm.player.mash.label + '"?')) {
                $amm.save(selected_mash_id);
              }
            } else {
              var mash = {};
              if (selected_mash_id) {
                var m = __mash_for_id(selected_mash_id);
                if (m) {
                  $amm.selected_mash = m;
                  mash.label = m.label;
                  mash.id = selected_mash_id;
                  if (! __unsaved_mashes[selected_mash_id]) {
                    if (amm_resources.mash) amm_resources.mash.data({id: selected_mash_id}, function(mash_data_response){
                      // console.log('mash.data', mash_data_response);
                      if (mash_data_response.ok) {
                        if (selected_mash_id === $amm.mash_id){
                          __action_index = -1;
                          $amm.player.mash = mash_data_response.data;
                        }
                      } else $window.alert(mash_data_response.error || __php_parsed_error(mash_data_response));
                    });
                  }
                }
              } else {
                // make a new mash
                mash.label = 'New Untitled Mash';
                mash.id = $amm.player.uuid();
                __unsaved_mashes[mash.id] = true;
                $amm.mashes.push(mash);
                $amm.mash_id = mash.id;
                $amm.selected_mash = mash;
                // console.log('new mash', $amm.mash_id);
              }
              if ($amm.mash_id === mash.id) {
                __action_index = -1;
                $amm.player.mash = mash;
              }
            }
          };
          $amm.mashes = [];
          $amm.mash_id = 0;
          // console.log('controller amm-ui');
          $scope.$amm = $amm;
          $scope.log_mash = function(){
            // console.log('mash', $amm.player.mash);
          };
          $scope.amm_style_media_icon = function(media){
            var url, style = {};
            if (media.icon) url = media.icon;
            else {
              switch(media.type) {
                case 'image': {
                  url = (media.url || media.source);
                  break;
                }
                case 'audio': {
                  url = media.wave;
                  break;
                }
              }
            }
            if (media.type === 'audio') style['background-size'] = '100% 100%';
            if (url) style['background-image'] = 'url(' + url + ')';
            return style;
          };
          $scope.amm_media_icon_url = function(media){
            var url;
            if (media) {
              if (media.icon) url = media.icon;
              else {
                switch(media.type) {
                  case 'image': {
                    url = (media.url || media.source);
                    break;
                  }
                  case 'audio': {
                    url = media.wave;
                    break;
                  }
                }
              }
            } else {
              // console.error('amm_media_icon_url received invalid parameter', media);
            }
            return url;
          };
        }
      ],
      link: function(scope, element, attributes){
        var ob, i, z, bit, bits, key, prop, normalized, config;
        $amm.MovieMasher.configure({mash: { default: {quantize:10} } });

        for (key in attributes.$attr){
          switch(key){
            case 'class':
            case 'ammUi': break;
            default: {
              normalized = attributes.$attr[key];
              prop = attributes[key];
              bits = normalized.split('-');
              if ('amm' === bits.shift()){
                z = bits.length;
                if (! config) config = {};
                ob = config;
                for (i = 0; i < z; i++){
                  bit = bits[i];
                  if (! ob[bit]) {
                    if (i === z - 1) ob[bit] = prop;
                    else ob[bit] = {};
                  }
                  ob = ob[bit];
                }
              }
            }
          }
        }
        __init_rest(config, amm_resources, $resource);
        if (amm_resources.module && amm_resources.module.search) {
          amm_resources.module.search({group:'font'}, function(response) {
            $amm.MovieMasher.register('font', response);
            if (amm_resources.mash) amm_resources.mash.search({}, function(mash_search_response) {
              if (mash_search_response.length) {
                angular.forEach(mash_search_response, function(mash){
                  $amm.mashes.push(mash);
                });
                $amm.mash_id = $amm.mashes[0].id;
              }
              $amm.mash_id_change();
            });
          });
          amm_resources.module.search({group:'filter'}, function(response) {
            $amm.MovieMasher.register('filter', response);
          });
          amm_resources.module.search({group:'scaler'}, function(response) {
            $amm.MovieMasher.register('scaler', response);
          });
          amm_resources.module.search({group:'merger'}, function(response) {
            $amm.MovieMasher.register('merger', response);
          });
         }
      },
    };
  }]); // amm-ui
  module.directive('ammPlayer', [
    '$window', '$amm',
    function($window, $amm) {
      return {
        restrict: 'AEC',
        replace: false,
        template: '<canvas id="amm-canvas"></canvas>',
        link: function(scope, element) {
          // console.log('amm-player link');
          if (! $window.CanvasRenderingContext2D) return;
          var canvas = element.find('canvas');
          var rect = element[0].getBoundingClientRect();
          canvas.attr('width', rect.width);
          canvas.attr('height', rect.height);

          // console.log('canvas dimensions', canvas[0].width, canvas[0].height);
          $amm.player.canvas_context = canvas[0].getContext('2d');
          element.on('$destroy', function() {
            // console.log('amm-player destroy');
            $amm.player.destroy();
          });
        }
      };
    }
  ]); // ammPlayer
  module.directive('ammBrowser', [
    '$timeout', '$interval', '$amm', 'amm_resources',
    function($timeout, $interval, $amm, amm_resources) {
      return {
        restrict: 'AEC',
        replace: false,
        controller: [
          '$scope', 'FileUploader',
          function($scope, FileUploader) {
            var MAX_IMPORT_MONITOR_ERRORS = 5;
            var uploader = $scope.uploader = new FileUploader({scope: $scope});
            var __media = {};
            var __uploads = [];
            var __update_import_completed = function(){
              var upload, i, z, completed = 0, total = 0;
              z = __uploads.length;
              for (i = 0; i < z; i++){
                upload = __uploads[i];
                if (-1 === upload.completed){
                  $scope.amm_import_completed = -1;
                  $scope.amm_import_status = upload.status;
                  return;
                }
                total++;
                completed += upload.completed;
              }
              $scope.amm_import_completed = (total ? completed / total : 0);
              $scope.amm_import_status = '';
            };
            var __upload_for_item = function(item){
              var image, i, z = __uploads.length;
              for (i = 0; i < z; i++){
                image = __uploads[i];
                if (image.upload === item) return image;
              }
              return false;
            };
            var __monitor_import = function(upload){
              var requested = false;
              var error_count = 0;
              upload.interval_id = 0;
              upload.interval_id = $interval(function(){
                if (! requested) {
                  requested = true;
                  // console.log('Calling import.monitor', upload.monitor);
                  amm_resources.import.monitor(upload.monitor, function(import_monitor_response){
                    // console.log('import.monitor', import_monitor_response);
                    requested = false;
                    if (import_monitor_response.ok) {
                      if ((import_monitor_response.completed > 0) && (import_monitor_response.completed < 1)) {
                        upload.completed = 0.5 + import_monitor_response.completed / 2;
                      } else {
                        $interval.cancel(upload.interval_id);
                        if (import_monitor_response.type) {
                          var type = import_monitor_response.type;
                          if (('video' === type) && import_monitor_response.no_video && (import_monitor_response.no_video !== 'false')) {
                            type = 'audio';
                          }
                          $scope.amm_browser_group_change(type);
                        } else console.error('import_monitor_response did not include type key', import_monitor_response);
                        var index = __uploads.indexOf(upload);
                        if (-1 < index) __uploads.splice(index, 1);
                        else console.error('could not delete upload', upload);
                      }
                    } else {
                      upload.completed = -1;
                      upload.status = (import_monitor_response.error || __php_parsed_error(import_monitor_response));
                      $interval.cancel(upload.interval_id);
                    }
                    __update_import_completed();
                  }, function(import_err_response){
                    // console.error('import.monitor', import_err_response);
                    requested = false;

                    if (! import_err_response.status) error_count++;
                    if (error_count > MAX_IMPORT_MONITOR_ERRORS) {
                      upload.status = String(error_count) + ' error responses from import monitor cgi';
                      upload.completed = -1;
                      $interval.cancel(upload.interval_id);
                      __update_import_completed();
                    }
                  });
                }
              }, 5 * 1000);
            };
            if (amm_resources.import) {
              // REGISTER HANDLERS: afteraddingfile, afteraddingall, beforeupload, progress, success, cancel, error, complete
              uploader.onProgressItem = function (item, percent_done) {
                var upload = __upload_for_item(item);
                if (upload) {
                  upload.completed = Math.max(0.03, percent_done / 200);
                  __update_import_completed();
                } else console.error('could not find upload for item', item);
              };
              // selected file for upload
              uploader.onAfterAddingFile = function (item) {
                //console.info('After adding a file', item);
                var post_data = {};
                post_data.file = item.file.name;
                post_data.size = item.file.size;
                post_data.type = item.file.type;
                var upload = {};
                upload.uploading = true;
                upload.upload = item;
                upload.completed = 0.01;
                upload.name = post_data.name;
                __uploads.unshift(upload);
                __update_import_completed();
                // console.log('calling import.init', post_data);
                var __problem_upload = function(upload, status){
                  upload.completed = -1;
                  upload.status = status;
                  __update_import_completed();
                };
                amm_resources.import.init(post_data, function(import_init_response){
                  // console.log('import.init', import_init_response);
                  if (import_init_response.ok){
                    upload.completed = 0.02;
                    item.formData.push(import_init_response.data);
                    if (import_init_response.headers){
                      for (var k in import_init_response.headers){ item.headers[k] = import_init_response.headers[k]; }
                    }
                    upload.api = import_init_response.api;
                    item.url = import_init_response.endpoint;
                    // console.log(import_init_response.endpoint, import_init_response.data);
                    item.method = import_init_response.method;
                    item.upload();
                  } else __problem_upload(upload, (import_init_response.error || __php_parsed_error(import_init_response)));
                }, function() {
                  // console.error('import.init problem uploading', arguments);
                  __problem_upload(upload, 'error');
                });
              };

              // successfully uploaded actual file
              uploader.onSuccessItem = function (item, import_upload_response) {
                // console.info('onSuccessItem', arguments);
                var upload;
                upload = __upload_for_item(item);
                if (upload) {
                  if (import_upload_response.error) {
                    // we might get this is file is local on server
                    upload.status = import_upload_response.error;
                    upload.completed = -1;
                  } else { // assume everything was okay anyway
                    if (upload.api) {
                      upload.completed = 0.5;
                      if (amm_resources.import.api) $timeout(function(){
                        // console.log('Calling import.api', upload.api);
                        amm_resources.import.api(upload.api, function(api_response){
                          // console.log('import.api', api_response);

                          if (api_response.ok && amm_resources.import.monitor) {
                            upload.monitor = api_response.monitor;
                            upload.completed = 0.51;
                            __monitor_import(upload);
                          } else {
                            upload.status = (api_response.error || __php_parsed_error(api_response));
                            upload.completed = -1;
                          }
                          __update_import_completed();
                        });
                      }, 20);
                    } else console.error('upload had no api key?', upload);
                  }
                } else console.error('could not find upload for item?', item);
                __update_import_completed();
              };
              uploader.onErrorItem = function (item) {
                // console.error('onErrorItem', arguments);
                var upload = __upload_for_item(item);
                if (upload) {
                  upload.status = 'problem uploading ' + upload.file;
                  upload.completed = -1;
                  __update_import_completed();
                } else console.error('could not find upload for item', item);
               };
             }
            if (amm_resources.module && amm_resources.module.search) $scope.amm_browser_group = 'theme';
            else $scope.amm_browser_group = 'video';
            $scope.amm_import_displayed_status = function(){
              var upload, i, z;
              z = __uploads.length;
              for (i = 0; i < z; i++){
                upload = __uploads[i];
                if (-1 === upload.completed){
                  __uploads.splice(i, 1);
                  break;
                }
              }
              __update_import_completed();
            };
            $scope.amm_browser_group_change = function(new_group){
               if (new_group){
                 $scope.amm_browser_group = new_group;
                 if (__media[$scope.amm_browser_group]) delete __media[$scope.amm_browser_group];
               }
               if (! __media[$scope.amm_browser_group]) {
                 switch($scope.amm_browser_group){
                   case 'image':
                   case 'video':
                   case 'audio': {
                     if (amm_resources.media) __media[$scope.amm_browser_group] = amm_resources.media.search({group: $scope.amm_browser_group});
                     break;
                   }
                   default: {
                     if (amm_resources.module) __media[$scope.amm_browser_group] = amm_resources.module.search({group: $scope.amm_browser_group});
                   }
                 }
               }
               $scope.amm_media = __media[$scope.amm_browser_group];
             };
            $scope.amm_browser_group_change();
          }
        ]
      };
    }
  ]); // ammBrowser
  module.directive('ammDragMedia', ['$amm', '$parse', function($amm, $parse) {
    return {
      restrict: "AEC",
      templateUrl: 'views/browser/clip.html',
      link: function(scope, element, attributes) {
        element.bind("dragstart", function(eventObject) {
          var attribute = attributes.ammDragMedia;
          var media = $parse(attribute)(scope);
          var data = {media: media, x: eventObject.layerX, y: eventObject.layerY};
          var type = 'amm-' + media.type;
          // console.log('dragstart', type, data);
          eventObject.dataTransfer.effectAllowed = 'link';
          eventObject.dataTransfer.setData(type, angular.toJson(data));
          // console.log('dragstart', eventObject.dataTransfer);
        });
      }
    };
  }]); // ammDragMedia
  module.directive('ammInspector', ['$amm', function($amm) {
    // console.log('amm-inspector');
    return {
      restrict: 'AEC',
      replace: false,
      link: function(scope) {
        // console.log('amm-browser link');
        scope.amm_inspector_include = function(target){
          var include, media;
          if (target) {
            if (target === $amm.player.mash) include = 'views/inspector/mash.html';
            else {
              media = $amm.player.media(target);
               if (media){
                if (media.inspector) include = media.inspector;
                else {
                  switch(media.type){
                    case 'theme':
                    case 'transition': {
                      if (media.html) {
                        include = media.html;
                        break;
                      } // else intentional fall through to raw types
                    }
                    case 'video':
                    case 'audio':
                    case 'image':{
                      include = 'views/inspector/' + media.type + '.html';
                      break;
                    }
                    default:{
                      include = media.html;
                    }
                  }
                }
              }
            }
          }
          return include;
        };
      }
    };
  }]); // ammInspector
  module.directive('ammInspectorEffects', ['$amm', function ($amm) {
    return {
      restrict: "AEC",
      controller: ['$scope', '$element', '$amm', function($scope, $element){
        var controller = this;
        var __drop_type = function(types) {
          var type, i, z, acceptable_types = [
            "amm-effects", // selectedEffects
            "amm-effect", // media object of type...
          ];
          z = acceptable_types.length;
          for (i = 0; i < z; i++) {
            type = acceptable_types[i];
            if (-1 < __indexOf(types, type)) return type;
          }
          return false;
        };
        var __element_index = function(element){
          return Array.prototype.indexOf.call(element[0].parentNode.childNodes, element[0]);
        };
        controller.__effects_drag_data = function(eventObject, get_data){
          var found_directives, type, drag_data, over_effect_element, drop_ok, directive, target;

          if (eventObject) {
            type = __drop_type(eventObject.dataTransfer.types);
            if (type) {
              drop_ok = true;
              drag_data = {type: type, multiple: ('amm-effects' === type), over_index: -1 };
              if (get_data) {
                drag_data.data = eventObject.dataTransfer.getData(type);
                if (drag_data.data) drag_data.data = angular.fromJson(drag_data.data);
              }
              found_directives = {};
              target = angular.element(eventObject.target);
              while(target) {
                target = __next_directive_element(target);
                if (! target) break;
                directive = target.data('amm-directive');
                if ('ammInspectorEffects' === directive) break;
                found_directives[directive] = target;
                target = target.parent();
              }
              if (found_directives.ammInspectorEffect) {
                over_effect_element = found_directives.ammInspectorEffect;
                drag_data.effect = over_effect_element.data('amm-object');
              }
            }
          }
          if ((controller.__over_effect_element !== over_effect_element) || (controller.__drop_ok !== drop_ok)) {
            $scope.$apply(function(){
              if (controller.__over_effect_element !== over_effect_element) {
                if (controller.__over_effect_element) controller.__over_effect_element.toggleClass('amm-drop-dragover', false);
                controller.__over_effect_element = over_effect_element;
                if (controller.__over_effect_element) {
                  drag_data.over_index = __element_index(over_effect_element);
                  controller.__over_effect_element.toggleClass('amm-drop-dragover', true);
                }
              }
              if (controller.__drop_ok !== drop_ok) {
                controller.__drop_ok = drop_ok;
                $element.toggleClass('amm-drop-dragover', controller.__drop_ok);
              }
            });
          }
          if (drop_ok) {
            eventObject.preventDefault();
            eventObject.dataTransfer.dropEffect = (drag_data.multiple ? 'move' : 'all');
          }
          return drag_data;
        };
      }],
      link: function (scope, element, attributes, controller) {
        element.data('amm-directive', 'ammInspectorEffects');
        element.bind("dragenter", controller.__effects_drag_data);
        element.bind("dragover", controller.__effects_drag_data);
        element.bind("dragleave", function(){ controller.__effects_drag_data(); });
        element.bind("drop", function(eventObject) {
          var effects, index, data = controller.__effects_drag_data(eventObject, true);
          if (data) {
            effects = $amm.player.selectedClipOrMash.effects;
            index = data.over_index;
            if (-1 === index) index = effects.length;
            // console.log('effects.drop', eventObject.timeStamp);
            scope.$apply(function(){
              element.toggleClass('amm-drop-dragover', false);
              if (data.multiple) {
                // console.log('drop move', $amm.player.selectedEffects, 'effect', index);
                $amm.player.move($amm.player.selectedEffects, 'effect', index);
              } else {
                // console.log('effect drop add', data.data.media, 'effect', index);
                $amm.player.add(data.data.media, 'effect', index);
              }
            });
            if (eventObject.stopPropagation) eventObject.stopPropagation();
            eventObject.preventDefault();
          }
        });
        element.bind("dragend", function(eventObject) {
          //console.warn('dragend', eventObject.dataTransfer.dropEffect, eventObject.dataTransfer.effectAllowed);
          // TODO: create a better way to delete items!!
          if ('none' === eventObject.dataTransfer.dropEffect) scope.$apply(function(){ $amm.player.remove($amm.player.selectedEffects, 'effect'); });
        });

      }
    };
  }]); // ammInspectorEffects
  module.directive('ammInspectorEffect', ['$amm', '$parse', function($amm, $parse) {
    return {
      restrict: "A",
      //require: "^ammInspectorEffects",
      link: function(scope, element, attributes) {
        element.data('amm-directive', 'ammInspectorEffect');
        element.data('amm-object', scope.effect);
        var effect = $parse(attributes.ammInspectorEffect)(scope);
        element.attr("draggable", true);
        element.bind("dragstart", function(eventObject) {
          if ($amm.player.selected(effect)) {
            // console.log('dragstart', eventObject);
            var data = {effects: $amm.player.selectedEffects, x: eventObject.layerX, y: eventObject.layerY};
            // console.log('ammInspectorEffect setting effectAllowed!')
            eventObject.dataTransfer.effectAllowed = 'copyMove';
            eventObject.dataTransfer.setData('amm-effects', angular.toJson(data));
          }
          else {
            if (eventObject.stopPropagation) eventObject.stopPropagation();
            eventObject.preventDefault();
          }
        });

      }
    };
  }]); // ammInspectorEffect
  module.directive('ammTimeline', ['$amm', function() {
    // console.log('amm-timeline');
    return {
      restrict: 'AEC',
      controller: ['$scope', '$element', '$amm', function($scope, $element, $amm){
        var controller = this;
        var __scrollers = [];
        controller.scrollLeft = 0;
        controller.scrollTop = 0;
        var __reset_scroll_tracks = function() {
          var i, z;
          z = __scrollers.length;
          for (i = 0; i < z; i++){
            __scrollers[i]();
          }
        };
        var __zoom_changed = function(){
          var frame_pixels = controller.pixels_from_frame($amm.player.frame, 'round', $amm.player.fps, true);
          frame_pixels -= $scope.amm_timeline_width() / 2;
          controller.scrollLeft = Math.max(0, frame_pixels);
          __reset_scroll_tracks();
        };
        var __drop_type = function(types) {
          var type, i, z, acceptable_types = [
            "amm-clips-audio", // just audio clips
            "amm-clips-overlays", // no transitions (ok on video tracks too)
            "amm-clips-video", // at least one transition
            "amm-transition", // media object of type...
            "amm-image",
            "amm-frame",
            "amm-theme",
            "amm-video",
            "amm-audio",
          ];
          z = acceptable_types.length;
          for (i = 0; i < z; i++) {
            type = acceptable_types[i];
            if (-1 < __indexOf(types, type)) return type;
          }
          return false;
        };
        controller.pixels_from_frame = function(frame, rounding, quantize, dont_pad){
          if (! quantize) quantize = $amm.player.mash.quantize;
          var pps, pixels = 0;
          if (frame) {
            pps = controller.pixels_per_second(dont_pad);
            if (pps){
              pixels = (frame / quantize) * pps;
              if (rounding || (typeof rounding === "undefined")) pixels = Math[rounding || 'ceil'](pixels);
            }
          }
          return pixels;
        };
        controller.frame_from_pixels = function(pixels, rounding, quantize) {
          if (! quantize) quantize = $amm.player.mash.quantize;
          var pps, frame = 0;
          if (pixels) {
            pps = controller.pixels_per_second();
            // console.log('pixels_per_second', pps);
            if (pps){
              frame = (pixels / pps) * quantize;
              // console.log('frame', frame, $amm.player.mash.quantize);
              if (rounding || (typeof rounding === "undefined")) frame = Math[rounding || 'round'](frame);
            }
          }
          return frame;
        };
        controller.pixels_per_second = function(dont_pad){
          var mash_length, pixels = 0, pad = (dont_pad ? 0 : 20);
          mash_length = ($amm.player ? $amm.player.duration : 0);
          if (mash_length) pixels = ($scope.amm_timeline_width() - pad) / (mash_length * (1.01 - Math.max(0.01, Math.min(1.0, $scope.amm_zoom))));
          return pixels;
        };
        controller.register_scroller = function(scroller){
          __scrollers.push(scroller);
        };
        controller.timeline_drag_data = function(eventObject, get_data){
          var found_directives, type, drag_data, over_track_element, drop_ok, directive, target;
          if (eventObject) {
            type = __drop_type(eventObject.dataTransfer.types);
            if (type) {
              drag_data = {type: type, multiple: ('amm-clips-' === type.substr(0, 10)) };
              if (get_data) {
                drag_data.data = eventObject.dataTransfer.getData(type);
                if (drag_data.data) drag_data.data = angular.fromJson(drag_data.data);
              }
              found_directives = {};
              target = angular.element(eventObject.target);
              while(target) {
                target = __next_directive_element(target);
                if (! target) break;
                directive = target.data('amm-directive');
                if ('ammTimelineTracks' === directive) break;
                found_directives[directive] = target;
                target = target.parent();
              }
              if (found_directives.ammTimelineTrack) {
                drag_data.track = found_directives.ammTimelineTrack.data('amm-object');
                if (found_directives.ammTimelineClip) drag_data.clip = found_directives.ammTimelineClip.data('amm-object');
                if (drag_data.multiple){
                  drop_ok = (('amm-clips-' + drag_data.track.type) === drag_data.type);
                  drop_ok = (drop_ok || ((drag_data.track.type === 'video') && (drag_data.type === 'amm-clips-overlays')));
                } else {
                  drop_ok = (('amm-' + drag_data.track.type) === drag_data.type);
                  if ((! drop_ok) && ('video' === drag_data.track.type)) {
                    if ('amm-transition' === drag_data.type) drop_ok = ! drag_data.track.index;
                    else drop_ok = ('amm-audio' !== drag_data.type);
                  }
                }
                if (drop_ok) over_track_element = found_directives.ammTimelineTrack;
              }
            }
          }
          if ((controller.__over_track_element !== over_track_element) || (controller.__drop_ok !== drop_ok)) {
            $scope.$apply(function(){
              if (controller.__over_track_element !== over_track_element) {
                if (controller.__over_track_element) controller.__over_track_element.toggleClass('amm-drop-dragover', false);
                controller.__over_track_element = over_track_element;
                if (controller.__over_track_element) controller.__over_track_element.toggleClass('amm-drop-dragover', true);
              }
              if (controller.__drop_ok !== drop_ok) {
                controller.__drop_ok = drop_ok;
                $element.parent().toggleClass('amm-drop-dragover', controller.__drop_ok);
              }
            });
          }
          if (eventObject) eventObject.preventDefault();
          if (drop_ok) {
            eventObject.dataTransfer.dropEffect = (drag_data.multiple ? 'move' : 'all');
            // console.log('timeline_drag_data', drop_ok, eventObject.dataTransfer);
          }
          return drag_data;
        };
        controller.track_for_clips = function(clips){
          var type, i, z = clips.length;
          for (i = 0; i < z; i++){
            switch($amm.player.media(clips[i]).type){
              case 'audio':{
                type = 'audio';
                break;
              }
              case 'transition': {
                type = 'video';
                break;
              }
            }
            if (type) break;
          }
          if (! type) type = 'overlays';
          return type;
        };
        $scope.amm_timeline_scroll = function(event){
          controller.scrollLeft = event.target.scrollLeft;
          controller.scrollTop = event.target.scrollTop;
          __reset_scroll_tracks();
        };
        $scope.amm_selected_class = function(clip){
          return {'amm-selected':$amm.player.selected(clip)};
        };
        $scope.amm_style_clip = function(clip, track){
          var ob = {};
          var media = $amm.player.media(clip);
          ob.width = controller.pixels_from_frame(clip.frames, 'floor') + 'px';
          ob['z-index'] = ((('transition' === media.type) ? 10 : 1) * 100) + track.clips.indexOf(clip);
          ob.left = controller.pixels_from_frame(clip.frame, 'floor') + 'px';
          ob.position = 'absolute';
          return ob;
        };
        $scope.amm_timeline_view_width = function(){
          var pixels, mash_length = ($amm.player ? $amm.player.duration : 0);
          if (mash_length) pixels = mash_length * controller.pixels_per_second(true);
          else pixels = $scope.amm_timeline_width();
          return pixels;
        };
        $scope.amm_zoom = 0.0;
        $scope.$watch('amm_zoom', __zoom_changed);
      }],

    };
  }]); // ammTimeline
  module.directive('ammTimelineTrackControls', ['$amm', function() {
    return {
      restrict: 'AEC',
      require: '^ammTimeline',
      link: function(scope, element, attributes, controller) {
        controller.register_scroller(function(){
          element[0].scrollTop = controller.scrollTop;
        });
        scope.amm_track_controls_width = function(){
          return element[0].getBoundingClientRect().width;
        };
      }
    };
  }]); // ammTimelineTrackControls
  module.directive('ammTimelineRuler', ['$amm', '$document', function($amm, $document) {
    return {
      restrict: 'E',
      require: '^ammTimeline',
      link: function(scope, element, attributes, controller) {
        var body = angular.element($document[0].body);
        var __ruler_x = 0;
        var __set_frame = function(eventObject){
          scope.$apply(function(){
            var rx = eventObject.pageX - __ruler_x;
            var frame = controller.frame_from_pixels(rx, 'round', $amm.player.fps);
            // console.log('frame', frame, $amm.player.frames);
            $amm.player.frame = Math.max(0, Math.min($amm.player.frames, frame));
          } );

        };
        var __finish_frame = function(eventObject){
          __set_frame(eventObject);
          body.unbind('mousemove');
          body.unbind('mouseup');
          body.unbind('mouseleave');
        };
        element.bind('mousedown', function(eventObject) {
          __ruler_x = element[0].getBoundingClientRect().left + scope.amm_track_controls_width() - controller.scrollLeft;
          // console.log('mousedown', __ruler_x);
          __set_frame(eventObject);
          body.bind('mousemove', __set_frame);
          body.bind('mouseup', __finish_frame);
          body.bind('mouseleave', __finish_frame);
        });

      }
    };
  }]); // ammTimelineRuler
  module.directive('ammTimelineRule', ['$amm', function($amm) {
    return {
      restrict: 'E',
      require: '^ammTimeline',
      link: function(scope, element, attributes, controller) {
        controller.register_scroller(function(){
          if(!scope.$$phase) scope.$apply(function(){});
        });
        scope.amm_style_rule = function(){
          var ob = {};
          ob.left = controller.pixels_from_frame($amm.player.frame, 'round', $amm.player.fps);
          ob.left -= Math.round(element[0].getBoundingClientRect().width / 2);
          ob.left += scope.amm_track_controls_width() - controller.scrollLeft;
          ob.left = String(ob.left) + 'px';
          //console.log('amm_style_rule', ob.left);
          return ob;
        };
      }
    };
  }]); // ammTimelineRule
  module.directive('ammTimelineTracks', ['$timeout', '$amm', '$window', '$rootScope', function($timeout, $amm, $window, $rootScope) {
    $window.onresize = function(){$rootScope.$apply(function(){});};
    return {
      restrict: 'AEC',
      require: '^ammTimeline',
      link: function(scope, element, attributes, controller) {
        controller.register_scroller(function(){
          if (element[0].scrollLeft !== controller.scrollLeft) {
            $timeout(function(){
              element[0].scrollLeft = controller.scrollLeft;
            });
          }
        });
        scope.amm_timeline_width = function() {
          return element[0].getBoundingClientRect().width;
        };


        element.data('amm-directive', 'ammTimelineTracks');
        attributes.$set('class', "amm-timeline-tracks");
        element.bind('scroll', scope.amm_timeline_scroll);
        element.bind("dragenter", controller.timeline_drag_data);
        element.bind("dragover", controller.timeline_drag_data);
        element.bind("dragleave", function(){
          // console.log('dragleave');
          //controller.timeline_drag_data();
        });
        element.bind("drop", function(eventObject) {
          // console.log('drop', eventObject);
          var drop_effect, frame_or_index, track_index, container, data = controller.timeline_drag_data(eventObject, true);
          if (data) {
            drop_effect = eventObject.dataTransfer.dropEffect;
            // console.log('amm-timeline-tracks drop', drop_effect, eventObject);
            controller.timeline_drag_data(); // to clear highlights
            container = $amm.player.mash[data.track.type];
            track_index = data.track.index;
            if (('video' === data.track.type) && (! track_index)) {
              frame_or_index = (data.clip ? data.track.clips.indexOf(data.clip) : data.track.clips.length);
            } else { // get frame from pixel offset
              frame_or_index = Math.max(0, controller.frame_from_pixels(__globalx_to_local(eventObject.pageX, element) - data.data.x));
              // console.log('amm-timeline-tracks drop frame_or_index', frame_or_index);
            }
            scope.$apply(function(){
              if (data.multiple){
                // console.log('drop move', $amm.player.selectedClips, data.track.type, frame_or_index, track_index);
                $amm.player.move($amm.player.selectedClips, data.track.type, frame_or_index, track_index);
              } else {
                // console.log('clip drop add', data.data, data.track.type, frame_or_index, track_index);
                $amm.player.add(data.data.media, data.track.type, frame_or_index, track_index);
              }
            });
            if (eventObject.stopPropagation) eventObject.stopPropagation();
            if (eventObject.preventDefault) eventObject.preventDefault();
          }
        });
      }
    };
  }]); // ammTimelineTracks
  module.directive('ammTimelineTrack', ['$amm', function($amm) {
    return {
      restrict: 'AEC',
      link: function(scope, element) {
        element.data('amm-directive', 'ammTimelineTrack');
        element.data('amm-object', scope.track);
        element.bind('mousedown', function() {
          scope.$apply(function(){
            $amm.player.select(false);
          } );
        });
      }
    };
  }]);  // ammTimelineTrack
  module.directive('ammTimelineClip', ['$amm', '$parse', function($amm) { return {
    restrict: "E",
    require: '^ammTimeline',
    templateUrl: 'views/timeline/clip.html',
    replace: true,
    link: function(scope, element, attributes, controller) {
      element.data('amm-directive', 'ammTimelineClip');
      element.data('amm-object', scope.clip);
      element.bind('mousedown', function($event) {
        scope.$apply(function(){
          // console.log('mousedown', $event, scope.clip);
          $amm.player.select(scope.clip, $event.shiftKey);
          if ($event.stopPropagation) $event.stopPropagation();
        } );
      });
      element.bind("dragstart", function(eventObject) {
        if ($amm.player.selected(scope.clip)) {
          // console.log('ammTimelineClip setting effectAllowed!')
          eventObject.dataTransfer.effectAllowed = 'copyMove';
          var do_add, clip, i, z, media, media_by_id = {}, data = {clips: $amm.player.selectedClips, x: eventObject.layerX, y: eventObject.layerY, media: []};
          z = data.clips.length;
          do_add = ((z > 1) ? eventObject.dataTransfer.addElement : false);
          for (i = 0; i < z; i++){
            clip = data.clips[i];

            media = $amm.player.media(clip);
            if (! media_by_id[media.id]) {
              media_by_id[media.id] = true;
              data.media.push(media);
            }
          }
          eventObject.dataTransfer.setData('amm-clips-' + controller.track_for_clips(data.clips), angular.toJson(data));
        } else {
          if (eventObject.stopPropagation) eventObject.stopPropagation();
          eventObject.preventDefault();
        }
      });
      element.bind("dragend", function(eventObject) {
        //console.warn('dragend', eventObject.dataTransfer.dropEffect, eventObject.dataTransfer.effectAllowed);
        // TODO: create a better way to delete items!!
        if ('none' === eventObject.dataTransfer.dropEffect) scope.$apply(function(){ $amm.player.remove($amm.player.selectedClips, scope.track.type, scope.track.index); });
      });
      element.on('$destroy', function() {
        element.data('amm-object', null);
        element.data('amm-directive', null);
      });
    },
  };}]); // ammTimelineClip
  module.directive('ammSynced', ['$amm', '$interval', function($amm, $interval) { return {
    restrict: "AEC",
    link: function() {
      $interval(function(){
        // essentially just calling scope.$digest();
      }, 100);
    }
  };}]); // ammSynced
  module.filter('secondsFromFrames', function() {
    return function(input,fps) {
         return input / fps;
       };
    }); // secondsFromFrames
  module.filter('displaySeconds', function() {
    var __str_pad = function(n, width, z) {
      z = z || '0';
      n = n + '';
      return (n.length >= width ? n : new Array(width - n.length + 1).join(z) + n);
    };
    return function(n, fps, longest) {
      var time, pad, do_rest, s = '';
      if (! longest) longest = n;
      time = 60 * 60; // an hour
      pad = 2;
      if (longest >= time) {
        if (n >= time) {
          s += __str_pad(String(Math.floor(n / time)), pad);
          do_rest = true;
          n = n % time;
        } else s += '00:';
      }
      time = 60; // a minute
      if (do_rest || (longest >= time)) {
        if (do_rest) s += ':';
        if (n >= time) {
          s += __str_pad(String(Math.floor(n / time)), pad);
          do_rest = true;
          n = n % time;
        } else s += '00:';
      }
      time = 1; // a second
      if (do_rest || (longest >= time)) {
        if (do_rest) s += ':';
        if (n >= time) {
          s += __str_pad(String(Math.floor(n / time)), pad);
          do_rest = true;
          n = n % time;
        } else s += '00';
      } else s += '00';
      if (fps > 1) {
        if (fps === 10) pad = 1;
        s += '.';
        if (n) {
          if (pad === 1) n = Math.floor(n * 10) / 10;
          else n = Math.floor(100 * n) / 100;
          n = Number(String(n).substr(2, 2));
          s += __str_pad(String(n), pad);
        } else s += __str_pad('0', pad);
      }
      return s;
       };
    }); // displaySeconds
  // console.log('evaluated angular-moviemasher.js');
})();

