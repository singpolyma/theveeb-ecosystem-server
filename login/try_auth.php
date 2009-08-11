<?php

function json_out($str) {
	header('Content-Type: text/javascript');
	if($_GET['callback']) echo $_GET['callback'].'(';
	echo $str;
	if($_GET['callback']) echo ')';
	die;
}

require_once dirname(__FILE__).'/common.php';
session_start();

if($_GET['openid_identifier']) $_GET['openid_url'] = $_GET['openid_identifier'];

if($_GET['return_to']) $_SESSION['return_to'] = $_GET['return_to'];
if($_GET['action']) $_SESSION['action'] = $_GET['action'];

// Render a default page if we got a submission without an openid
// value.
if (empty($_GET['openid_url'])) {
	$error = "Expected an OpenID identifier.";
	if(isset($_GET['json'])) json_out('{"error":"'.addslashes($error).'"}');
	include 'index.php';
	exit;
}

$scheme = 'http';
if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
    $scheme .= 's';
}

$openid = $_GET['openid_url'];

$process_url = sprintf("$scheme://%s%s/finish_auth.php",
                       $_SERVER['HTTP_HOST'],
                       dirname($_SERVER['PHP_SELF']));

if(strstr($openid,'@')) {
	$process_url .= '?email='.$openid;
}

if(preg_match('/@gmail\.com$/',$openid)
	|| preg_match('/@googlemail\.com$/',$openid)) {
	$openid = 'https://www.google.com/accounts/o8/id';
}

if(preg_match('/^([^@]+)@aol\.com$/', $openid, $matches)
	 || preg_match('/^([^@]+)@aim\.com$/', $openid, $matches)) {
	$openid = 'http://openid.aol.com/'.urlencode($matches[1]);
}


$trust_root = sprintf("$scheme://%s%s",
                      $_SERVER['SERVER_NAME'],
                      dirname(dirname($_SERVER['PHP_SELF'])));

// Begin the OpenID authentication process.
$auth_request = $consumer->begin($openid);

// Handle failure status return values.
if (!$auth_request) {
	if(strstr($openid,'@')) { // if identifier is an email address
		$auth_request = $consumer->begin('http://email-verify.appspot.com/id/'.urlencode($openid));
		if (!$auth_request) {
			$error = "Fatal: Please enter a valid email address or OpenID.";
			if(isset($_GET['json'])) json_out('{"error":"'.addslashes($error).'"}');
			include 'index.php';
			exit;
		}
	} else {
	   	$error = "Please enter a valid email address or OpenID.";
		if(isset($_GET['json'])) json_out('{"error":"'.addslashes($error).'"}');
		include 'index.php';
		exit;
	}
}

$sreg = Auth_OpenID_SRegRequest::build('', array('nickname','fullname','email'), '');
$auth_request->addExtension($sreg);

if ($auth_request->endpoint->usesExtension(Auth_OpenID_AX_NS_URI)) {
	$ax_request = new Auth_OpenID_AX_FetchRequest();
	$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/friendly', 1, true));
	$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/email', 1, true));
	$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson', 1, true));

	$auth_request->addExtension($ax_request);
}

    // Redirect the user to the OpenID server for authentication.
    // Store the token for this authentication so we can verify the
    // response.

    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
    // form to send a POST request to the server.
    if ($auth_request->shouldSendRedirect()) {
        $redirect_url = $auth_request->redirectURL($trust_root,
                                                  $process_url);
        
        // If the redirect URL can't be built, display an error
        // message.
        if (Auth_OpenID::isFailure($redirect_url)) {
            $error = "Could not redirect to server: " . $redirect_url->message;
				if(isset($_GET['json'])) json_out('{"error":"'.addslashes($error).'"}');
				include 'index.php';
				exit;
        } else {
            // Send redirect.
				if(isset($_GET['json'])) json_out('{"redirect":"'.addslashes($redirect_url).'"}');
            header("Location: ".$redirect_url);
        }
    } else {
        // Generate form markup and render it.
        $form_id = 'openid_message';
		  if(isset($_GET['json']))
           $form_html = $auth_request->formJSON($trust_root, $process_url, false);
		  else
           $form_html = $auth_request->formMarkup($trust_root, $process_url,
                                               false, array('id' => $form_id));
        
        // Display an error if the form markup couldn't be generated;
        // otherwise, render the HTML.
        if (Auth_OpenID::isFailure($form_html)) {
            $error = "Could not redirect to server: " . $form_html->message;
				if(isset($_GET['json'])) json_out('{"error":"'.addslashes($error).'"}');
				include 'index.php';
				exit;
        } else {
            $page_contents = array(
               "<html><head><title>",
               "OpenID transaction in progress...",
               "</title></head>",
               "<body onload='document.getElementById(\"".$form_id."\").submit()'>",
               $form_html,
               "</body></html>");
	
				if(isset($_GET['json'])) json_out($form_html);
            
            print implode("\n", $page_contents);
        }
    }

	 if(isset($_GET['json'])) json_out('{}'); // This should never happen

?>
