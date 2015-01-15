<?php

include_once(dirname(__FILE__) . '/loadutils.php');
load_utils('sig','http','log');

if (! function_exists('service_send_message')) {
	function service_send_message($data, $config = array()){
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
}

?>
