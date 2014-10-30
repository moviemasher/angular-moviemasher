<?php

include_once(dirname(__FILE__) . '/authutils.php');
include_once(dirname(__FILE__) . '/dateutils.php');
include_once(dirname(__FILE__) . '/idutils.php');
include_once(dirname(__FILE__) . '/floatutils.php');
include_once(dirname(__FILE__) . '/logutils.php');
include_once(dirname(__FILE__) . '/mimeutils.php');
include_once(dirname(__FILE__) . '/sigutils.php');

function api_export($input = array(), $output = array(), $config = array()) {
	$result = array();
	if (! $config) $config = config_get();
	$result['error'] = config_error($config);
	// create export job
	if (empty($result['error'])) $result = api_job_export($input, $output, $config);
	// queue job
	if (empty($result['error'])) $result = api_queue_job($result, $config);
	return $result;
}
function api_import($input = array(), $output = array(), $config = array()) {
	$result = array();
	if (! $config) $config = config_get();
	$result['error'] = config_error($config);
	// create import job
	if (empty($result['error'])) $result = api_job_import($input, $output, $config);
	// queue job
	if (empty($result['error'])) $result = api_queue_job($result, $config);
	return $result;
}
function api_import_data($response = array(), $config = array()) {
	$media_data = array();
	if (! $config) $config = config_get();
	$err = config_error($config);
	if (! $err) {
		$id = (empty($response['id']) ? '' : $response['id']);
		$uid = (empty($response['uid']) ? '' : $response['uid']);
		if (! ($id && $uid)) $err = 'Parameters id, uid required';
	}
	if (! $err) { // pull in other configuration and check for required input
		$import_video_rate = $config['import_video_rate'];
		$import_extension = $config['import_extension'];
		$media_url = '/' . $config['user_media_url'] . $uid . '/';
		if ($config['file'] != 'local') $media_url = 'http://' . $config['user_media_host'] . $media_url;
	}
	if (! $err) {
		$media_extension = (empty($response['extension']) ? '' : $response['extension']);
		$type = (empty($response['type']) ? '' : $response['type']);
		switch ($type) {
			case 'image': {
				$import_video_rate = '1';
				$import_extension = $media_extension;
				break;
			}
			case 'video':
			case 'audio': break;
			default: {
				$err = 'could not determine type of preprocessed upload';
			}
		}
		if (! ($import_extension && $type)) $err = 'Could not determine type and extension';
	}
	if (! $err) {
		$duration = (empty($response['duration']) ? '' : $response['duration']);
		$label = (empty($response['label']) ? '' : $response['label']);
		$media_data['id'] = $id;
		$media_data['label'] = $label;
		// add source with original for rendering
		$media_data['source'] = $media_url . $id . '/' . $config['import_original_basename'] . '.' . $media_extension;
		$frame_path = $media_url . $id . '/' . $config['import_dimensions'] . 'x' . $import_video_rate . '/';
		$audio = 1;
		$did_icon = 0;
		$did_url = 0;
		switch($type) {
			case 'image': {
				$frame_path .= '0.' . $import_extension;
				$media_data['url'] = $frame_path;
				$media_data['icon'] = $frame_path;
				break;
			}
			case 'video': {
				$audio = ! ((empty($response['no_audio']) || ('false' == $response['no_audio'])) ? '' : $response['no_audio']);
				$video = ! ((empty($response['no_video']) || ('false' == $response['no_video'])) ? '' : $response['no_video']);
				if ($video) {
					$frames = floor($duration * $import_video_rate);
					$zero_padding = strlen($frames);
					$media_data['url'] = $frame_path;
					$media_data['video_rate'] = $import_video_rate;
					$media_data['pattern'] = '%.' . $import_extension;
					$media_data['icon'] = $frame_path . str_pad(ceil($frames / 2), $zero_padding, '0', STR_PAD_LEFT) . '.' . $import_extension;
					$did_icon = 1;
				} else {
					$type = 'audio';
					$did_url = 1;
					$media_data['url'] = $media_url . $id . '/' . $config['import_audio_basename'] . '.' . $config['import_audio_extension'];		
				}
				// intentional fall through to audio
			}
			case 'audio': {
				if (! $duration) $err = 'Could not determine duration';
				else {
					$media_data['duration'] = $duration;
					if ($audio) {
						if (! $did_url) $media_data['audio'] = $media_url . $id . '/' . $config['import_audio_basename'] . '.' . $config['import_audio_extension'];
						$media_data['wave'] = $media_url . $id . '/' . $config['import_waveform_basename'] . '.' . $config['import_waveform_extension'];
						if (! $did_icon) $media_data['icon'] = $media_data['wave'];
					} else $media_data['audio'] = '0'; // put in a zero to indicate that there is no audio
				}
				break;
			}
		}
		$media_data['group'] = $media_data['type'] = $type;
	}
	if ($err) $media_data['error'] = $err;
	return $media_data;
}
function api_export_data($response = array(), $config = array()) {
	$export_data = array();
	if (! $config) $config = config_get();
	$err = config_error($config);
	if (! $err) {
		$id = (empty($response['id']) ? '' : $response['id']);
		$job = (empty($response['job']) ? '' : $response['job']);
		$uid = (empty($response['uid']) ? '' : $response['uid']);
		$type = (empty($response['type']) ? '' : $response['type']);
		$extension = (empty($response['extension']) ? '' : $response['extension']);
		if (! ($id && $extension && $type && $job && $uid)) $err = 'Parameters uid, job, id, extension, type required';
	}
	if (! $err) { // pull in other configuration and check for required input
		$media_url = '/' . $config['user_media_url'] . $uid . '/';
		if ($config['file'] != 'local') $media_url = 'http://' . $config['user_media_host'] . $media_url;
		$export_data['id'] = $id;
		$export_data['source'] = $media_url . $id . '/' . ($config["export_{$type}_basename"] ? $config["export_{$type}_basename"] : $job) . '.' . $extension;
	}
	if ($err) $export_data['error'] = $err;
	return $export_data;
}

function api_job_export($input = array(), $output = array(), $config = array()) {
	$result = array();
	if (! $config) $config = config_get();
	$err = config_error($config);
	if (! $err) { // pull in other configuration and check for required input
		// make sure required input parameters have been set
		$id = (empty($input['id']) ? '' : $input['id']);
		$input_mash = (empty($input['mash']) ? array() : $input['mash']);
		if (! ($id && $input_mash)) $err = 'Required input parameters omitted';
	}
	if (! $err) { // try to analyze mash 
		$about_mash = __api_about_mash($input_mash);
		if (! empty($about_mash['error'])) {
			log_file(print_r($about_mash, 1), $config);
			$err = $about_mash['error'];
		} else if (! $about_mash['duration']) $err = 'Mash has no duration';
	}
	if (! $err) { // build inputs and job request
		$inputs = array();
		$type = ($about_mash['has_video'] ? 'video' : 'audio');
		$input_mash = array('type' => 'mash', 'source' => $input_mash);
		$inputs[] = $input_mash;
		$output['type'] = $type;
		$output['has_audio'] = $about_mash['has_audio'];
		$output['has_video'] = $about_mash['has_video'];
		$output['id'] = $id;
		$output['title'] = $about_mash['label'];
		$result = api_job_render($inputs, $output, $config);
	}
	if ($err) $result['error'] = $err;
	return $result;
}
function api_job_render($inputs, $output, $config) {
	$result = array('inputs' => $inputs, 'outputs' => array(), 'destination' => array());
	if (! $config) $config = config_get();
	$err = config_error($config);
	if (! $err) { // pull in other configuration and check for required input
		// make sure required output parameters have been set
		$id = (empty($output['id']) ? '' : $output['id']);
		if (! ($id)) $err = 'Required output parameters omitted';
	}
	if (! $err) {
		$type = (empty($output['type']) ? 'video' : $output['type']);
		$title = (empty($output['title']) ? '' : $output['title']);
		if ($type == 'video') {
			$export_extension = $config['export_extension'];
			$export_audio_codec = $config['export_audio_codec'];
		} else {
			$type = 'audio';
			$export_extension = $config['export_audio_extension'];
			$export_audio_codec = $config['export_audio_codec_audio'];
		}
		$found_audio = (empty($output['has_audio']) ? true : $output['has_audio']);
		$found_video = (empty($output['has_video']) ? ($type == 'video') : $output['has_video']);
	
		$path_media = $config['user_media_directory'];
		$path_media_url = $config['user_media_url'];
		$user_id = (isset($output['UserID']) ? $output['UserID'] : auth_userid());
		if ($user_id) {
			$path_media .= $user_id . '/';
			$path_media_url .= $user_id . '/';
		}
		$progress_callback_payload = array(
			'job' => '{job.id}',
			'progress' => '{job.progress}',
		);
		$complete_callback_payload = array(
			'job' => '{job.id}',
			'id' => $id,
			'uid' => $user_id,
			'type' => $type,
			'extension' => $export_extension,
			'error' => '{job.error}',
			'log' => '{job.log}',
			'commands' => '{job.commands}',
		);
		$destination = array();
		// DESTINATION
		if ($config['file'] == 's3') { 
			 $destination = array(
				'type' => 's3',
				'bucket' => $config['s3_bucket'],
				'region' => $config['s3_region'],
				'path' => $path_media_url . $id,
			);
		}
		else { // file is local
			log_file('file is not s3 ' . $config['file'], $config);
			if ($config['client'] == 'local') { 
				$destination = array(
					'type' => 'file',
					'method' => 'move',
					'directory' => $config['web_root_directory'],
					'path' => $path_media . $id,
				);
			}
			else {
				$destination = auth_data(array(
					'type' => 'http',
					'host' => $config['callback_host'],
					'key' => $config['callback_directory'] . 'export_transfer.php',
					'parameters' => array(
						'id' => $id,
						'type' => $type,
						'extension' => $export_extension,
						'uid' => $user_id,
						'job' => '{job.id}'
					),
				), $config);
			}
		}
		if ($config['client'] == 'local') { 
			if ($config['file'] == 'local') { 
				// media source is absolute url if file is s3
				$base_path = '';
				$len = strlen($config['user_media_url']);
				if ($config['user_media_url'] == substr($config['user_media_directory'], -$len)) {
					$base_path = substr($config['user_media_directory'], 0, -$len);
				}
				$result['base_source'] = array(
					'directory' => $config['web_root_directory'],
					'path' => $base_path,
					'method' => 'symlink', 
					'type' => 'file',
				);
			}
			$result['module_source'] = array(
				'directory' => $config['web_root_directory'],
				'path' => $config['module_directory'],
				'method' => 'symlink', 
				'type' => 'file',
			);
			
			if (! empty($output['include_progress'])) $result['callbacks'][] = array(
				'type' => 'file', 
				'trigger' => 'progress',
				'method' => 'copy',
				'directory' => $config['temporary_directory'],
				'path' => '{job.id}.json',
				'data' => $progress_callback_payload,
			);
			$result['callbacks'][] = array(
				'type' => 'file', 
				'trigger' => 'complete',
				'method' => 'copy',
				'directory' => $config['temporary_directory'],
				'path' => '{job.id}.json',
				'data' => $complete_callback_payload,
			);
		} 
		else { // client is s3
			// raw media has absolute url, so modules can just grab from base_source
			$result['base_source'] = array(
				'host' => $config['module_host'], 
				'directory' => $config['module_directory'], 
				'method' => 'get', 
				'type' => 'http'
			);
			if (! empty($output['include_progress'])) $result['callbacks'][] = auth_data(array(
				'host' => $config['callback_host'], 
				'type' => 'http', 
				'trigger' => 'progress',
				'path' => $config['callback_directory'] . 'export_progress.php',
				'data' => $progress_callback_payload,
			), $config);
			$result['callbacks'][] = auth_data(array(
				'host' => $config['callback_host'], 
				'type' => 'http', 
				'trigger' => 'complete',
				'path' => $config['callback_directory'] . 'export_complete.php',
				'data' => $complete_callback_payload,
			), $config);
		}
		$result['destination'] = $destination;
		// add Output for rendered video or audio file, with no transfer tag of its own
		$job_output = array('type' => $type);
		$job_output['extension'] = $export_extension;
		$job_output['name'] = (empty($config["export_{$type}_basename"]) ? '{job.id}' : $config["export_{$type}_basename"]);
		if ($config['export_meta_title'] && $title) $job_output['metadata'] = $config['export_meta_title'] . '="' . $title . '"';
		if ($type == 'video') {
			$job_output['video_codec'] = $config['export_video_codec'];
			$job_output['video_bitrate'] = $config['export_video_bitrate'];
			$job_output['video_rate'] = $config['export_video_rate'];
			$job_output['dimensions'] = $config['export_dimensions'];
		}
		else $job_output['no_video'] = '1';
		if ($found_audio) {
			$job_output['audio_codec'] = $export_audio_codec;
			$job_output['audio_bitrate'] = $config['export_audio_bitrate'];
			$job_output['audio_rate'] = $config['export_audio_rate'];
		}
		else $job_output['no_audio'] = '1';
		$result['outputs'][] = $job_output;
	}
	if ($err) $result['error'] = $err;
	return $result;
}
function api_job_import($input = array(), $output = array(), $config = array()) {
	$result = array('callbacks' => array(), 'inputs' => array(), 'outputs' => array(), 'destination' => array());
	if (! $config) $config = config_get();
	$err = config_error($config);
	
	if (! $err) { // check for required input
		$path_media = $config['user_media_directory'];
		$path_media_url = $config['user_media_url'];
		$user_id = (isset($output['UserID']) ? $output['UserID'] : auth_userid());
		if ($user_id) {
			$path_media .= $user_id . '/';
			$path_media_url .= $user_id . '/';
		}
		// make sure required input parameters have been set
		$id = (empty($input['id']) ? '' : $input['id']);
		$extension = (empty($input['extension']) ? '' : $input['extension']);
		$type = (empty($input['type']) ? mime_type_from_extension($extension) : $input['type']);
		$label = (empty($input['label']) ? $id : $input['label']);
		if (! ($id && $extension && $type)) $err = 'Required parameter omitted';
	}
	if (! $err) { // create job for transcoder
		$progress_callback_payload = array(
			'job' => '{job.id}',
			'progress' => '{job.progress}',
		);
		$complete_callback_payload = array(
			'job' => '{job.id}',
			'id' => $id,
			'uid' => $user_id,
			'extension' => $extension,
			'type' => $type,
			'label' => $label,
			'error' => '{job.error}',
			'log' => '{job.log}',
			'commands' => '{job.commands}',
			'no_audio' => '{job.inputs.0.no_audio}',
			'no_video' => '{job.inputs.0.no_video}',
		);
		if ('image' != $type) $complete_callback_payload['duration'] = '{job.duration}';
		$destination = array();
		$source = array('name' => $config['import_original_basename'], 'extension' => $extension);
		$input = array('type' => $type);
		if ($type != 'audio') $input['fill'] = 'none';
		// DESTINATION
		if ($config['file'] == 's3') { 
			 $destination = array(
				'type' => 's3',
				'bucket' => $config['s3_bucket'],
				'region' => $config['s3_region'],
				'path' => $path_media_url . $id,
			);
		} 
		else { // file is local
			if ($config['client'] == 'local') {
				 $destination = array(
					'type' => 'file',
					'method' => 'move',
					'directory' => $config['web_root_directory'],
					'path' => $path_media . $id,
				);
			} 
			else {
				$destination = auth_data(array(
					'type' => 'http',
					'host' => $config['callback_host'],
					'path' => $config['callback_directory'] . 'import_transfer.php',
					'archive' => 'tgz', 
					'parameters' => array(
						'id' => $id,
						'type' => $type,
						'extension' => $extension,
						'uid' => $user_id,
					),
				), $config);
			}
		}
		// CALLBACKS
		if ($config['client'] == 'local') { 
			if (! empty($output['include_progress'])) $result['callbacks'][] = array(
				'type' => 'file', 
				'trigger' => 'progress',
				'method' => 'copy',
				'directory' => $config['temporary_directory'],
				'path' => '{job.id}.json',
				'data' => $progress_callback_payload,
			);
			$result['callbacks'][] = array(
				'type' => 'file', 
				'trigger' => 'complete',
				'method' => 'copy',
				'directory' => $config['temporary_directory'],
				'path' => '{job.id}.json',
				'data' => $complete_callback_payload,
			);
		} 
		else {
			if (! empty($output['include_progress'])) $result['callbacks'][] = auth_data(array(
				'host' => $config['callback_host'], 
				'type' => 'http', 
				'trigger' => 'progress',
				'path' => $config['callback_directory'] . 'import_progress.php',
				'data' => $progress_callback_payload,
			), $config);
			$result['callbacks'][] = auth_data(array(
				'host' => $config['callback_host'], 
				'type' => 'http', 
				'trigger' => 'complete',
				'path' => $config['callback_directory'] . 'import_complete.php',
				'data' => $complete_callback_payload,
			), $config);
		}
		// SOURCE
		if (($config['file'] == 'local') && ($config['client'] == 'local')) {
			$source['type'] = 'file';
			$source['method'] = 'symlink';
			$source['directory'] = $config['web_root_directory'];
			$source['path'] = $path_media . $id;
		} 
		else { 
			$source['host'] = $config['user_media_host'];
			$source['path'] = $path_media_url . $id;
		}
		$input['source'] = $source;
		$result['inputs'][] = $input;
		$result['destination'] = $destination;
		// OUTPUTS
		if ($type == 'image') {
			// add output for image file
			$result['outputs'][] = array(
				'type' => 'image', 
				'name' => $config['import_dimensions'] . 'x1/0', 
				'dimensions' => $config['import_dimensions'], 
				'extension' => $extension, 
				'quality' => $config['import_image_quality'], 
			);
		} 
		else {
			// add output for audio/video file
			$result['outputs'][] = array(
				'type' => 'audio', 
				'precision' => 0,
				'audio_bitrate' => $config['import_audio_bitrate'], 
				'name' => $config['import_audio_basename'], 
				'extension' => $config['import_audio_extension'], 
				'frequency' => $config['import_audio_rate'],
			);
			
			
			// add output for waveform file
			$result['outputs'][] = array(
				'type' => 'waveform', 
				'forecolor' => $config['import_waveform_forecolor'], 
				'backcolor' => $config['import_waveform_backcolor'], 
				'name' => $config['import_waveform_basename'], 
				'dimensions' => $config['import_waveform_dimensions'], 
				'extension' => $config['import_waveform_extension'],
			);
		}
		if ($type == 'video') {
			// add output for sequence files
			$result['outputs'][] = array(
				'type' => 'sequence', 
				'video_rate' => $config['import_video_rate'], 
				'quality' => $config['import_image_quality'], 
				'extension' => $config['import_extension'], 
				'dimensions' => $config['import_dimensions'],
				'path' => '{output.dimensions}x{output.fps}',
			);
		}
	}
	if ($err) $result['error'] = $err;
	return $result;
}
function api_progress_completed($progress = array()){
	$completed = 0;
	$completings = 0;
	if (is_array($progress)) {
		foreach($progress as $k => $v){
			if ('ing' == substr($k, -3)) $completings += $v;
			else $completed += $v;
		}
	}
	return ($completings ? $completed / $completings : 0);
}
function api_send_job_sqs($data, $config = array()){
	$result = array();
	if (! $config) $config = config_get();
	$err = config_error($config);
	if (! $err) { 
		$variables = array();
		$variables['Action'] = 'SendMessage';
		$variables['MessageBody'] = json_encode($data);
		$variables['Version'] = '2012-11-05';
		// the following headers are required for non-public queues
		$parsed_url = parse_url($config['sqs_queue_url']);
		$headers = array();
		$headers['Host'] = strtolower($parsed_url['host']);
		$headers['X-Amz-Date'] = gmdate(DATE_FORMAT_ISO8601_BASIC);
		$signature = sig_version_four($config['aws_secret_access_key'], $config['sqs_queue_url'], $variables, $headers, $config['s3_region'], 'post');
		$region = (empty($config['s3_region']) ? 'us-east-1' : $config['s3_region']); // assuming queue is in same region as bucket!
		$date_str = substr($headers['X-Amz-Date'], 0, 8);
		$headers['Authorization'] = "AWS4-HMAC-SHA256 Credential={$config['aws_access_key_id']}/$date_str/$region/sqs/aws4_request, SignedHeaders=host;x-amz-date, Signature=$signature";
		$post_result = http_send($config['sqs_queue_url'], $variables, $headers);
		$data = $post_result['result'];
		if ($data && $config['log_api_response']) log_file("sqs response:\n" . $data, $config);
		if ($post_result['error']) $err = 'Could not make sqs request ' . $config['sqs_queue_url'] . ' ' . $post_result['error'];
		else if (! $data) $err = 'Got no response from sqs request';
		else {
			$xml = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
			if (! is_object($xml)) $err = 'Could not parse sqs response';
			else if (sizeof($xml->Error)) {
				if (! $config['log_api_response']) log_file("sqs response:\n" . $data, $config);
				$err = 'Got error in sqs response';
			}
			else if (! (is_object($xml->SendMessageResult) && is_object($xml->SendMessageResult->MessageId))) $err = 'Got no MessageId in sqs response';
			else $result['job_id'] = (string) $xml->SendMessageResult->MessageId;
		}		
	}
	if ($err) $result['error'] = $err;
	return $result;

}
function api_queue_job($data, $config = array()) {
	$result = array();
	if (! $config) $config = config_get();
	$result['error'] = config_error($config);

	// queue job
	if (empty($result['error'])) {
		$result['id'] = '';
		// post job to the Transcoder
		if ($config['log_api_request']) log_file("{$config['client']} request:\n" . json_encode($data), $config);
		
		if ($config['client'] == 'sqs') {
			$queue_response = api_send_job_sqs($data, $config);
			if (! empty($queue_response['error'])) $result['error'] = $queue_response['error'];
			else $result['id'] = $queue_response['job_id'];
		} 
		else { // local
			$result['id'] = id_unique();
			$job_path = $config['queue_directory'] . $result['id'] . '.json';
			$data['id'] = $result['id'];
			$json_str = @json_encode($data);
			if (! $json_str) $result['error'] = 'could not encode json';
			else if (! file_put($job_path, $json_str)) {
				$result['error'] = 'could not write job to ' . $job_path;
			}
		}
		if ((! $result['error']) && empty($result['id'])) $result['error'] = 'Got no Job ID';
		
	}
	
	return $result;
}
function __api_about_mash($mash){
	$result = array('error' => '', 'duration' => 0, 'has_audio' => 0, 'has_video' => 0, 'label' => '');
	if (empty($mash)) $result['error'] = 'mash is empty';
	if (! $result['error']) {
		$types = array('audio', 'video');
		$media = (empty($mash['media']) ? array() : $mash['media']);
		$medias = array();
		foreach($media as $m) $medias[$m['id']] = $m;
		$quantize = (empty($mash['quantize']) ? 10 : $mash['quantize']);
		if (! empty($mash['label'])) $result['label'] = $mash['label'];
		foreach($types as $type){
			$tracks = (empty($mash[$type]) ? array() : $mash[$type]);
			$y = sizeof($tracks);
			for ($j = 0; $j < $y; $j++) {
				$track = $tracks[$j];
				$z = sizeof($track['clips']);
				for ($i = 0; $i < $z; $i++){
					$clip = $track['clips'][$i];
					$media = (empty($medias[$clip['id']]) ? $clip : $medias[$clip['id']]);
					$media_type = (empty($media['type']) ? '' : $media['type']);
					switch($media_type){
						case 'audio':
							if (__api_clip_has_audio($clip, $media)) $result['has_audio'] = true;
							else continue; // completely ignore muted audio
							break;
						case 'video':
							if (__api_clip_has_audio($clip, $media)) $result['has_audio'] = true;
							// fall through to default, for visuals
						default:
							$result['has_video'] = true;
							break;
					}	
					$result['duration'] = max($result['duration'], ($clip['frame'] + $clip['frames']) / $quantize);
				}
			}
		}
	}
	return $result;
}
function __api_clip_has_audio($clip, $media){
	$has = FALSE;
	$url = '';
	$type = $media['type'];
	switch($type){
		case 'audio': {
			$url = (empty($media['url']) ? (empty($media['source']) ? '' : $media['source']) : $media['url']);
			break;
		}
		case 'video': {
			$url = (isset($media['audio']) ? ((0 === $media['audio']) ? '' : $media['audio']) : (empty($media['url']) ? (empty($media['source']) ? '' : $media['source']) : $media['url']));
			break;
		}
	}
	if ($url) {
		$has = ! isset($clip['gain']);
		if (! $has) $has = ! __api_gain_mutes($clip['gain']);
	}
	return $has;
}
function __api_gain_mutes($gain){
	if (strpos($gain, ',') !== FALSE) {
		$does_mute = TRUE;
		$gains = explode(',', $gain);
		$z = sizeof($gains) / 2;
		for ($i = 0; $i < $z; $i++){
			$does_mute = float_cmp(floatval($gains[1 + $i * 2]), FLOAT_ZERO);
			if (! $does_mute) break;
		}
	} else $does_mute = float_cmp(floatval($gain), FLOAT_ZERO);
	return $does_mute;
}
?>