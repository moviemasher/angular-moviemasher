<?php 


include_once(dirname(dirname(__FILE__)) . '/include/loadutils.php');
load_utils('file','id','path','config');
function local_file_source($input, $config){
	return array(
		'name' => $config['import_original_basename'], 
		'extension' => $input['extension'],
		'type' => 'file',
		'method' => 'symlink',
		'directory' => $config['web_root_directory'],
		'path' => path_concat(path_concat($config['user_media_directory'], $input['uid']), $input['id']),
	);
}
function local_file_import_url($import, $config){
	return path_concat(substr($config['user_media_url'], strlen(path_add_slash_end($config['install_directory']))), $import['uid']);
}

function local_client_enqueue($data, $config = array()){
	$result = array();
	$err = '';
	$id = (empty($data['id']) ? id_unique() : $data['id']);
	$result['id'] = $data['id'] = $id;
	$job_path = path_concat($config['queue_directory'], $id . '.json');
	$json_str = @json_encode($data);
	if (! $json_str) $err = 'could not encode json';
	else if (! file_put($job_path, $json_str)) $err = 'could not write job to ' . $job_path;
	if ($err) $result['error'] = $err;
	return $result;
}
function local_client_import_complete_callback($payload, $config){
	return local_client_export_complete_callback($payload, $config);
}
function local_client_import_progress_callback($payload, $config){
	return local_client_export_progress_callback($payload, $config);
}
function local_client_export_complete_callback($payload, $config){
	return array(
		'type' => 'file', 
		'trigger' => 'complete',
		'method' => 'copy',
		'directory' => $config['temporary_directory'],
		'path' => '{job.id}.json',
		'data' => $payload,
	);
}
function local_client_export_progress_callback($payload, $config){
	return array(
		'type' => 'file', 
		'trigger' => 'progress',
		'method' => 'copy',
		'directory' => $config['temporary_directory'],
		'path' => '{job.id}.json',
		'data' => $payload,
	);
}
function local_file_export_module_source($config){
	return array(
		'directory' => $config['web_root_directory'],
		'path' => $config['module_directory'],
		'method' => 'symlink', 
		'type' => 'file',
	);
}
function local_file_export_base_source($config){
	$media_url = local_file_import_url(array(), $config); // returns user
	$media_dir = path_strip_slashes($config['user_media_directory']);
	$len = strlen($media_url);
	if ($media_url == substr($media_dir, -$len)) {
		$media_dir = substr($media_dir, 0, -$len);
	}
	return array(
		'directory' => $config['web_root_directory'],
		'path' => $media_dir,
		'method' => 'symlink', 
		'type' => 'file',
	);
}					
function local_client_config_error($config){
	$err = '';
	if ((! $err) && empty($config['queue_directory'])) $err = 'Configuration option queue_directory required';
	if ((! $err) && (! file_exists($config['queue_directory'])))  $err = 'Configuration option queue_directory must exist';
					
	return $err;
}

function local_file_config_error($config){
	$err = '';
	if ((! $err) && empty($config['queue_directory'])) $err = 'Configuration option queue_directory required';
	if ((! $err) && (! file_exists($config['queue_directory'])))  $err = 'Configuration option queue_directory must exist';
	return $err;	
}
function local_file_destination($output, $config){
	return array(
		'type' => 'file',
		'method' => 'move',
		'directory' => $config['web_root_directory'],
		'path' => path_concat(path_concat($config['user_media_directory'], $output['uid']), $output['id']),
	);
}
function local_file_import_init($import, $config){
	$response = array();
	$id = id_unique();
	$response['api'] = array();
	$response['data'] = array();
	$response['endpoint'] = path_concat($config['cgi_directory'], 'import_upload.php');
	$response['data']['id'] = $id;
	$response['data']['type'] = $import['type'];
	$response['data']['extension'] = $import['extension'];
	$response['api']['id'] = $id;
	$response['api']['type'] = $import['type'];
	$response['api']['extension'] = $import['extension'];
	$response['api']['label'] = $import['label'];
	return $response;
}
