<?php
//check internet connection with dropbox
function _dropbox_is_connected() {
    $connected = @fsockopen("www.google.com", 80);
    if ($connected){
        $is_conn = true;
        fclose($connected);
    }else{
        $is_conn = false;
    }
    return $is_conn;
}

//check connection with dropbox
function _dropbox_has_connection() {
	$account = _dropbox_current_account();
	if($account) {
		return true;
	} else {
		return false;
	}
}

//check
function _dropbox_is_authorized() {
	if(variable_get("dropbox_access_token", "") != "") {
		return true;
	} else {
		return false;
	}
}

function _dropbox_auth_url() {
	global $base_url;
	return url('https://www.dropbox.com/oauth2/authorize',
		array(
	    	'query' => array(
	      		'response_type' => 'code',
	      		'client_id' => variable_get('dropbox_app_key', ''),
	      		'redirect_uri' => $base_url . '/dropbox/authorize',
	    	),
	  	)
	);
}

function _dropbox_disconnect() {
	return url('dropbox/disconnect');
}

//get current account info
function _dropbox_current_account() {
	$access_token = variable_get("dropbox_access_token", "");
	$url = 'https://api.dropboxapi.com/2/users/get_current_account';
	$data = array();
	//Process can't continue without access token
	if(empty($access_token)) {
		watchdog('dropbox', "Please authorize your dropbox APP.");
		return false;
	}
	//Upload operation headers
	$headers = array(
		'Authorization: Bearer ' . $access_token,
	);

	$resp = _dropbox_curl($url, $data, 'current_account', $headers);
	$resp_object = json_decode($resp);

	if(isset($resp_object->name) && isset($resp_object->account_id)) {
		return $resp_object;
	} else {
		watchdog('dropbox', "Dropbox account info getting failed.");
	}
	return false;
}

//get space usage info
function _dropbox_space_usage() {
	$access_token = variable_get("dropbox_access_token", "");
	$url = 'https://api.dropboxapi.com/2/users/get_space_usage';
	$data = array();
	//Process can't continue without access token
	if(empty($access_token)) {
		drupal_set_message("Please authorize your dropbox APP.", 'error');
		watchdog('dropbox', "Please authorize your dropbox APP.");
		return false;
	}
	//Upload operation headers
	$headers = array(
		'Authorization: Bearer ' . $access_token,
	);

	$resp = _dropbox_curl($url, $data, 'current_account', $headers);
	$resp_object = json_decode($resp);

	if(isset($resp_object->used)) {
		return $resp_object;
	} else {
		watchdog('dropbox', "Dropbox space usage info getting failed.");
	}
	return false;
}

// Example: _dropbox_upload(drupal_get_path("module", "dropbox") . "/test.pdf", "/test.pdf");
// @parameter mode: add | overwrite | update
function _dropbox_upload($filepath, $uploadpath, $mode = '') {
	if(!empty($mode)) {
		$mode = 'add';
	}
	//prepare vars
	$access_token = variable_get("dropbox_access_token", "");
	$url = 'https://content.dropboxapi.com/2/files/upload';
	$data = realpath($filepath);
	$filesize = filesize($data);

	//Process can't upload files larger than 150 MB
	if($filesize > 150 * 1024 * 1024) {
		drupal_set_message("Do not upload a file larger than 150 MB.", 'error');
		watchdog('dropbox', "Do not upload a file larger than 150 MB.");
		return false;
	}
	//Process can't continue without access token
	if(empty($access_token)) {
		drupal_set_message("Please authorize your dropbox APP.", 'error');
		watchdog('dropbox', "Please authorize your dropbox APP.");
		return false;
	}
	//Upload operation headers
	$headers = array(
		'Authorization: Bearer ' . $access_token,
		'Content-Type: application/octet-stream',
		'Dropbox-API-Arg: ' .
		json_encode(
			array(
				"path" => $uploadpath,
				"mode" => "add",
				"autorename" => true,
				"mute" => false
			)
		)
	);

	$resp = _dropbox_curl($url, $data, 'upload', $headers);
	$resp_object = json_decode($resp);

	if(isset($resp_object->name) && isset($resp_object->path_lower) && isset($resp_object->size) && $resp_object->size > 0) {
		drupal_set_message($resp_object->name . " has been uploaded.");
		return $resp_object;
	} else {
		if(isset($resp_object->error_summary)) {
			watchdog('dropbox', $resp_object->error_summary);
		} else {
			watchdog('dropbox', "Dropbox upload failed.");
		}
	}
	return false;
}

//Example: _dropbox_create_folder('/test5');
function _dropbox_create_folder($foldername = '') {
	if(!empty($foldername)) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/create_folder';
		$data = array(
			'path' => $foldername,
			'autorename' => false,
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'create_folder', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->name) && isset($resp_object->path_lower)) {
			drupal_set_message("Folder " . $resp_object->name . " has been created.");
			return $resp_object;
		} else {
			watchdog('dropbox', "Dropbox folder creation failed.");
		}
	}
	return false;
}

//_dropbox_delete('/test2');
function _dropbox_delete($path = '') {
	if(!empty($path)) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/delete';
		$data = array(
			'path' => $path,
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'delete', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->name) && $resp_object->path_lower) {
			drupal_set_message($resp_object->name . " has been deleted.");
			return $resp_object;
		} else if(isset($resp_object->error_summary)) {
			watchdog('dropbox', $resp_object->error_summary);
		} else {
			watchdog('dropbox', "Dropbox delete failed.");
		}
		return false;
	}
}

//_dropbox_delete_multiple(array('/logotip (2).JPG', '/test4'));
function _dropbox_delete_multiple($path_array = false) {
	$entries = array();
	if($path_array) {
		foreach ($path_array as $key => $path) {
			$entries['entries'][]['path'] = $path;
		}

		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/delete_batch';
		$data = $entries;

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'delete_multiple', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->async_job_id)) {
			drupal_set_message("Multiple delete operation.");
			_dropbox_delete_status($resp_object->async_job_id);
			return true;
		} else {
			watchdog('dropbox', "Dropbox delete multiple failed.");
		}
	} else {
		watchdog('dropbox', "Dropbox delete multiple: path array is missing.");
	}
}

//_dropbox_delete_status("dbjid:AACWDU1x5XLDpFFw5ATRUDmxYtAD29J6M3-N_75OrrkQA005tSb8wM35y5CVerOrIJfYseQKiOi4vtPn3DAVTzRv");
function _dropbox_delete_status($job_id = false) {
	if($job_id) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/delete_batch/check';
		$data = array(
			'async_job_id' => $job_id,
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'delete_status', $headers);
		$resp_array = (array)json_decode($resp);

		if(isset($resp_array['entries']) && isset($resp_array['.tag'])) {
			drupal_set_message("Delete operations status: " . $resp_array['.tag']);
			return true;
		} else if(isset($resp_array['error_summary'])) {
			drupal_set_message($resp_array['error_summary'], "error");
			watchdog("dropbox", $resp_array['error_summary']);
		} else {
			watchdog('dropbox', "Dropbox delete status display failed.");
		}
	} else {
		watchdog('dropbox', "Dropbox delete status: Job_id is missing.");
	}
	return false;
}

//Folder: _dropbox_list_folder("/videos"); or
//Root: _dropbox_list_folder("");
function _dropbox_list_folder($path = false, $is_recursive = false, $include_media_info = false) {
	//prepare vars
	$access_token = variable_get("dropbox_access_token", "");
	$url = 'https://api.dropboxapi.com/2/files/list_folder';
	$data = array(
		"path" => $path,
    "recursive" => $is_recursive,
    "include_media_info" => $include_media_info,
    "include_deleted" => false,
    "include_has_explicit_shared_members" => false
	);

	//Process can't continue without access token
	if(empty($access_token)) {
		drupal_set_message("Please authorize your dropbox APP.", 'error');
		watchdog('dropbox', "Please authorize your dropbox APP.");
		return false;
	}

	//Folder create operation headers
	$headers = array(
		'Authorization: Bearer ' . $access_token,
		'Content-Type: application/json',
	);

	$resp = _dropbox_curl($url, $data, 'list_folder', $headers);
	$resp_object = json_decode($resp);

	if(isset($resp_object->entries)) {
		return $resp_object;
	} else if (isset($resp_object->error_summary)) {
		watchdog('dropbox', $resp_object->error_summary);
		return $resp_object;
	} else {
		watchdog('dropbox', 'Dropbox list directory failed.');
	}
}

//Folder: _dropbox_list_folder("/videos"); or
//Root: _dropbox_list_folder("");
function _dropbox_get_last_page_cursor($path = false, $is_recursive = false, $include_media_info = false) {
	//prepare vars
	$access_token = variable_get("dropbox_access_token", "");
	$url = 'https://api.dropboxapi.com/2/files/list_folder';
	$data = array(
		"path" => $path,
    "recursive" => $is_recursive,
    "include_media_info" => $include_media_info,
    "include_deleted" => false,
    "include_has_explicit_shared_members" => false
	);

	//Process can't continue without access token
	if(empty($access_token)) {
		drupal_set_message("Please authorize your dropbox APP.", 'error');
		watchdog('dropbox', "Please authorize your dropbox APP.");
		return false;
	}

	//Folder create operation headers
	$headers = array(
		'Authorization: Bearer ' . $access_token,
		'Content-Type: application/json',
	);

	$resp = _dropbox_curl($url, $data, 'get_last_page', $headers);
	$resp_object = json_decode($resp);

	if(isset($resp_object->cursor)) {
		return $resp_object;
	} else if (isset($resp_object->error_summary)) {
		watchdog('dropbox', $resp_object->error_summary);
	} else {
		watchdog('dropbox', 'Dropbox last page cursor getting failed.');
	}
}

//_dropbox_list_folder_next("AAGgqN55M6o3nP5ZUV5z303a3pwZFW2TD94Jm17_7oulEBQgevS9JzXrAU4wfsAu2g1HatvO9A2XWsWZKvWNoyUtxrfpZ_zU3EhNoGeDl4Q9sBd5MWiYeOpTvRfVr5CHyU8P_69Bs0QIuhNqH7JOoJMbNLe0dL96NE7dZyQfdVFvlVRkoGG8sFlc4iEd6Hd1394"));
function _dropbox_list_folder_next($cursor = false) {
	if($cursor) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/list_folder/continue';
		$data = array(
			"cursor" => $cursor,
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'list_folder_next', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->entries)) {
			return $resp_object;
		} else if (isset($resp_object->error_summary)) {
			watchdog('dropbox', $resp_object->error_summary);
		} else {
			watchdog('dropbox', 'Dropbox list directory continue failed.');
		}
	}
}

/*//Example: _dropbox_create_folder('/test5');
function _dropbox_create_folder($foldername = '') {
	if(!empty($foldername)) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/create_folder';
		$data = array(
			'path' => $foldername,
			'autorename' => false,
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'create_folder', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->name) && isset($resp_object->path_lower)) {
			drupal_set_message("Folder " . $resp_object->name . " has been created.");
			return $resp_object;
		} else {
			watchdog('dropbox', "Dropbox folder creation failed.");
		}
	}
	return false;
}
*/

//_dropbox_copy("/mov_bbb.mp4", "/test5/mov_bbb.mp4")
function _dropbox_copy($frompath = false, $topath = false, $allowshared = false) {
	if($frompath && $topath) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/copy';
		$data = array(
			"from_path" => $frompath,
	    "to_path" => $topath,
	    "allow_shared_folder" => $allowshared,
	    "autorename" => false
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'copy', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->id) && $resp_object->name) {
			return $resp_object;
		} else if(isset($resp_object->error_summary)) {
			watchdog('dropbox', $resp_object->error_summary);
		} else {
			watchdog('dropbox', "Dropbox copy failed.");
		}
		return false;
	}
}

//_dropbox_move("/test2.txt", "/test5/test2.txt")
function _dropbox_move($frompath = false, $topath = false, $allowshared = false) {
	if($frompath && $topath) {
		//prepare vars
		$access_token = variable_get("dropbox_access_token", "");
		$url = 'https://api.dropboxapi.com/2/files/move';
		$data = array(
			"from_path" => $frompath,
		    "to_path" => $topath,
		    "allow_shared_folder" => $allowshared,
		    "autorename" => false
		);

		//Process can't continue without access token
		if(empty($access_token)) {
			drupal_set_message("Please authorize your dropbox APP.", 'error');
			watchdog('dropbox', "Please authorize your dropbox APP.");
			return false;
		}

		//Folder create operation headers
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		$resp = _dropbox_curl($url, $data, 'move', $headers);
		$resp_object = json_decode($resp);

		if(isset($resp_object->id) && $resp_object->name) {
			return $resp_object;
		} else if(isset($resp_object->error_summary)) {
			watchdog('dropbox', $resp_object->error_summary);
		} else {
			watchdog('dropbox', "Dropbox move failed.");
		}
		return false;
	}
}

function _dropbox_download($dropbox_path, $local_path) {
    $out_fp = fopen($local_path, 'w+');
    $url = 'https://content.dropboxapi.com/2/files/download';
    $access_token = variable_get("dropbox_access_token", "");

    //Process can't continue without access token
	if(empty($access_token)) {
		drupal_set_message("Please authorize your dropbox APP.", 'error');
		watchdog('dropbox', "Please authorize your dropbox APP.");
		return false;
	}

    $header_array = array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type:',
        'Dropbox-API-Arg: {"path":"' . $dropbox_path . '"}'
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
    curl_setopt($ch, CURLOPT_FILE, $out_fp);

    $metadata = null;
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$metadata) {
      	$prefix = 'dropbox-api-result:';
	    if (strtolower(substr($header, 0, strlen($prefix))) === $prefix) {
	        $metadata = json_decode(substr($header, strlen($prefix)), true);
	    }
       	return strlen($header);
   	});

    $output = curl_exec($ch);

    curl_close($ch);
    fclose($out_fp);

    return($metadata);
}

//makes curl request to Dropbox
function _dropbox_curl($url, $data = false, $command = '', $headers = false) {
	//convert data array to query params
	if(is_array($data)) {
		$post_data = http_build_query($data);
	} else {
		$post_data = $data;
	}
	if( $curl = curl_init() ){
		if(count($data) == 0) {$post_data = array();}
		curl_setopt($curl, CURLOPT_URL, $url);
		//add headers if exists
		if($headers) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		//allow post method
		if($data) {
			curl_setopt($curl, CURLOPT_POST, true);
		}
		if(is_array($data)) {
			switch ($command) {
				case 'upload':
					curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($data));
					break;
				case 'create_folder':
				case 'delete_multiple':
				case 'delete_status':
				case 'delete':
				case 'copy':
				case 'move':
				case 'list_folder':
				case 'list_folder_next':
				case 'get_last_page':
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
					break;
				default:
					curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
					break;
			}
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec($curl);
		curl_close($curl);

		return $out;
	}
}