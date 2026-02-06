<?php

	$RAW_POST_DATA = file_get_contents("php://input");
	
	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
		$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
		$args = json_decode(json_encode($_REQUEST), FALSE);
	}
	
	// bootstrap freepbx
	$bootstrap_settings['freepbx_auth'] = false;
	if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) { 
		include_once('/etc/asterisk/freepbx.conf'); 
	}
	
	error_log(sprintf('api.php [%s]', print_r($args, true)));

	
//	$args->config = FreePBX::Arsauth()->getConfig();
		
	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST		= null;
	$JSON_API_RESULT->JSON_RESULT		= new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE		= 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE	= "OK";
//	$JSON_API_RESULT->JSON_RESULT->amp_conf		= $amp_conf;;
//	$JSON_API_RESULT->JSON_RESULT->_SERVER		= $_SERVER;;
	
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

error_log(sprintf('api.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		
		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			
			$CLASS	= "Arsauth";
			$METHOD = "ECHO_ARGS";
			$PARAMS = null;

			if (isset($JSON_API_RESULT->JSON_REQUEST->CONTENTS_ONLY))	$CONTENTS_ONLY	= $JSON_API_RESULT->JSON_REQUEST->CONTENTS_ONLY;
			if (isset($JSON_API_RESULT->JSON_REQUEST->CLASS))			{
				$CLASS			= $JSON_API_RESULT->JSON_REQUEST->CLASS;
			} else {
				$JSON_API_RESULT->JSON_REQUEST->CLASS = $CLASS;
			}
			if (isset($JSON_API_RESULT->JSON_REQUEST->METHOD))			{
				$METHOD			= $JSON_API_RESULT->JSON_REQUEST->METHOD;
			} else {
				$JSON_API_RESULT->JSON_REQUEST->METHOD = $METHOD;
			}
			if (isset($JSON_API_RESULT->JSON_REQUEST->PARAMS))			$PARAMS			= $JSON_API_RESULT->JSON_REQUEST->PARAMS;

//			$JSON_API_RESULT->JSON_RESULT->CLASS	= $CLASS;
//			$JSON_API_RESULT->JSON_RESULT->METHOD	= $METHOD;
//			$JSON_API_RESULT->JSON_RESULT->PARAMS	= $PARAMS;

			if (FreePBX::$CLASS() !== null) {
				if (method_exists(FreePBX::$CLASS(), $METHOD)) {
					$JSON_API_RESULT = FreePBX::$CLASS()->$METHOD($JSON_API_RESULT);
					if (isset($JSON_API_RESULT->JSON_RESULT->CONTENTS)) {
						if (is_object($JSON_API_RESULT->JSON_RESULT->CONTENTS)) {
							echo json_encode($JSON_API_RESULT->JSON_RESULT->CONTENTS);
						} else {
							echo $JSON_API_RESULT->JSON_RESULT->CONTENTS;
						}
						exit();
					}
				} else {
					$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
					$JSON_API_RESULT->JSON_RESULT->MESSAGE		= sprintf("METHOD %s->%s() IS NOT DEFINED", $CLASS, $METHOD);
				}
			} else {
				$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
				$JSON_API_RESULT->JSON_RESULT->MESSAGE		= sprintf("METHOD %s->%s() IS NOT DEFINED", $CLASS, $METHOD);
			}
			
		} else {
			$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
			$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ARGUMENT JSON_REQUEST IS NOT VALID STRING OF JSON OBJECT";
		}
	} else {
		$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
		$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ARGUMENT JSON_REQUEST NOT DEFINED";
	}

//	error_log(sprintf('api.php RESULT : [%s]', print_r($JSON_API_RESULT, true)));

	if (isset($JSON_API_RESULT->OUT_TYPE) && ($JSON_API_RESULT->OUT_TYPE == 'FILE')) {
		if (isset($JSON_API_RESULT->FILE_PATH)) {
			$filepath	= $JSON_API_RESULT->FILE_PATH;
			$filesize	= filesize($filepath);
			$path_parts	= pathinfo($filepath);
			$filename	= $path_parts['basename'];
			$extension	= $path_parts['extension'];
			 
			header("Pragma: public");
			header("Expires: 0");
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"$filename\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: $filesize");
			 
			ob_clean();
			flush();
			readfile($filepath);
		} else {
			echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT);
		}
	} else {
		echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT);
	}
	

?>