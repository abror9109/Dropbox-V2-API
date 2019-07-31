<?php
function dropbox_settings($form, &$form_state) {
	$form = array();
	if($account = _dropbox_current_account()) {
		$fullname = '';
		$img = '';
		if(isset($account->name->surname)) {
			$fullname .= $account->name->surname . " ";
		}
		if(isset($account->name->given_name)) {
			$fullname .= $account->name->given_name;
		}
		if(isset($account->profile_photo_url)) {
			$img = '<img src="' . $account->profile_photo_url . '" width="100px" />';
		}
		$form['account_info'] = array(
			'#type' => 'markup',
			'#markup' => '
				<div class="account-image">' . $img . '<div>
				<div class="account-info"><strong>' . $fullname . '</strong></div>
			',
		);
		$form['disconnect'] = array(
			'#type' => 'submit',
			'#value' => t('Disconnect'),
			);
	} else {
		drupal_set_message("Dropbox is not connected.", "error");
		$form['app_key'] = array(
			'#type' => 'textfield',
			'#title' => t('Enter App key'),
			'#default_value' => variable_get('dropbox_app_key', ''),
			);
		$form['app_secet'] = array(
			'#type' => 'textfield',
			'#title' => t('Enter App secret'),
			'#default_value' => variable_get('dropbox_app_secret', ''),
			);
		$form['save'] = array(
			'#type' => 'submit',
			'#value' => t('Save and authorize'),
			);
	}
	return $form;
}

function dropbox_settings_submit($form, &$form_state) {
	global $base_url;
	if($form_state['values']['op'] != 'Disconnect') {
		variable_set('dropbox_app_key', $form_state['values']['app_key']);
		variable_set('dropbox_app_secret', $form_state['values']['app_secet']);
		drupal_set_message("Settings have been saved.");
		if(!empty(variable_get('dropbox_app_secret', '')) && !empty(variable_get('dropbox_app_key', ''))) {
			$form_state['redirect'] = array(
			  'https://www.dropbox.com/oauth2/authorize',
			  array(
			    'query' => array(
			      'response_type' => 'code',
			      'client_id' => variable_get('dropbox_app_key', ''),
			      'redirect_uri' => $base_url . '/dropbox/authorize',
			    ),
			  ),
			);
		}
	} else {
		variable_set('dropbox_access_token', "");
		variable_set('dropbox_token_type', "");
		variable_set('dropbox_account_id', "");
		variable_set('dropbox_uid', "");
	}
}