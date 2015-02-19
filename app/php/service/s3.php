<?php 

include_once(dirname(dirname(__FILE__)) . '/include/loadutils.php');
load_utils('id','date','path','log');

function s3_file_source($input, $config){
	return array(
		'name' => $config['import_original_basename'], 
		'extension' => $input['extension'],
		'type' => 'http',
		'host' => $config['user_media_host'],
		'path' => path_concat(path_concat($config['user_media_url'], $input['uid']), $input['id']),
	);
}
function s3_file_import_url($import, $config){
	return path_concat('http://' . $config['user_media_host'], path_concat($config['user_media_url'], $import['uid']));
}
function s3_file_config_defaults($config){
	if (empty($config['s3_acl'])) $config['s3_acl'] = 'public-read';
	if (empty($config['s3_expires'])) $config['s3_expires'] = '+ 1 hour';
	if (empty($config['s3_region'])) $config['s3_region'] = '';
	return $config;
}
function s3_file_config_error($config = array()){
	$err = '';
	if ((! $err) && empty($config['s3_bucket'])) $err = 'Configuration option s3_bucket required';
	if ((! $err) && empty($config['aws_access_key_id'])) $err = 'Configuration option aws_access_key_id required';
	if ((! $err) && empty($config['aws_secret_access_key'])) $err = 'Configuration option aws_secret_access_key required';
	if ((! $err) && empty($config['user_media_host'])) $err = 'Configuration option user_media_host required';
	if (! $err){
		if (substr($config['user_media_host'], 0, strlen($config['s3_bucket'])) != $config['s3_bucket']) {
			if (empty($config['user_media_directory'])) $err = 'Configuration option user_media_directory required if user_media_host does not begin with s3_bucket';
			else if (substr($config['user_media_directory'], 0, strlen($config['s3_bucket'])) != $config['s3_bucket']) {
				$err = 'Either user_media_host or user_media_directory must begin with s3_bucket';
			}
		}	
	}
	return $err;
}
function s3_file_export_module_source($config){
	return array(
		'host' => $config['module_host'], 
		'directory' => $config['module_directory'], 
		'method' => 'get', 
		'type' => 'http'
	);
}
function s3_file_destination($output, $config, $prefix = 's3'){
	return array(
		'type' => $prefix,
		'bucket' => $config[$prefix . '_bucket'],
		'region' => $config[$prefix . '_region'],
		'path' => path_concat(path_concat($config['user_media_url'], $output['uid']), $output['id']),
	);
}
function s3_file_import_init($import, $config) {
	$response = array();
	$err = '';
	if (! $err){
		$response['api'] = array();
		$response['data'] = array();
		$id = id_unique();
		$key = __s3_key($id, $import, $config);
		$policy = __s3_policy($id, $import, $config);
		$response['data']['key'] = $key;
		$response['data']['acl'] = $config['s3_acl'];
		$response['data']['policy'] = $policy;
		$response['data']['Content-Type'] = $import['mime'];
		$response['data']['signature'] = __s3_file_access_key($config['aws_secret_access_key'], $policy);
		$response['data']['AWSAccessKeyId'] = $config['aws_access_key_id'];
		$response['endpoint'] = 'https://s3' . ($config['s3_region'] ? '-' . $config['s3_region'] : '') . '.amazonaws.com/'. $config['s3_bucket'];
		$response['api']['id'] = $id;
		$response['api']['type'] = $import['type'];
		$response['api']['extension'] = $import['extension'];
		$response['api']['label'] = $import['label'];
	}
	if ($err) $response['error'] = $err;
	return $response;
}
function __s3_key($id, $import, $config, $prefix = 's3'){
	$bucket = $config[$prefix . '_bucket'];
	$key = path_strip_slash_start($config['user_media_directory']);
	// remove bucket name from url if it's there
	if (substr($key, 0, strlen($bucket)) == $bucket) $key = substr($key, strlen(path_add_slash_end($bucket)));
	$key = path_concat(path_concat(path_concat($key, $import['uid']), $id), $config['import_original_basename'] . '.' . $import['extension']);
	return $key;
}
function __s3_policy($id, $import, $config, $prefix = 's3', $format = DATE_FORMAT_TIMESTAMP){
	$key = __s3_key($id, $import, $config, $prefix);
	$expires = $config[$prefix . '_expires'];
	$bucket = $config[$prefix . '_bucket'];
	$policy = array();
	$policy['expiration'] = gmdate($format, strtotime($expires));
	$policy['conditions'] = array();
	$policy['conditions'][] = array('eq', '$bucket', $bucket);
	$policy['conditions'][] = array('eq', '$key', $key);
	if (! empty($config[$prefix . '_acl'])) $policy['conditions'][] = array('eq', '$acl', $config[$prefix . '_acl']);
	$policy['conditions'][] = array('eq', '$Content-Type', $import['mime']);
	$policy['conditions'][] = array('content-length-range', $import['size'], $import['size']);
	log_file('POLICY ' . print_r($policy, 1), $config);
	$policy = base64_encode(stripslashes(json_encode($policy)));
	return $policy;
}

function __s3_file_access_key($secret_access_key, $data) {
	return __s3_file_post_base64(__s3_file_post_hasher($secret_access_key, $data));
}
function __s3_file_post_base64($str) {
	$ret = "";
	for($i = 0; $i < strlen($str); $i += 2) $ret .= chr(hexdec(substr($str, $i, 2)));
	return base64_encode($ret);
}
function __s3_file_post_hasher($key, $data) {
	// Algorithm adapted (stolen) from http://pear.php.net/package/Crypt_HMAC/)
	if(strlen($key) > 64) $key = pack("H40", sha1($key));
	if(strlen($key) < 64) $key = str_pad($key, 64, chr(0));
	$ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
	$opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
	return sha1($opad . pack("H40", sha1($ipad . $data)));
}
