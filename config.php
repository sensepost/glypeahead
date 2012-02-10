<?php
/*
 * GlypeAhead
 * Junaid Loonat (junaid@sensepost.com)
 * Port scanning through glype proxies (which have not been heavily themed / modified)
 * 
 * 13 April 2010
 * 
 * Configuration
 */

$config = array(
	'debug'		=>	false,			//	change to true, of course, to enable debug
	'targets'	=>	array(
		'www.sensepost.com'			=>	array(		//	each targetted port needs to be listed in the array for the target host
			21,
			80,
			443
		),
		'www.hackrack.com'		=>	array(			//	multiple target arrays can be specified
			80,
			8080
		)
	),
	
	/*
	 * Note:
	 * Proxy link should be to Glype's primary index file, index.php
	 * 
	 * Additionally, error messages need to be displayed on resultant page.
	 * This can be checked by appending 'e=curl_error' onto the index url ...
	 * 		http://cooltoday.info/index.php?e=curl_error
	 * ... and looking for the "libcurl returned the error" error message.
	 * 
	 * Currently, the glype proxy cannot be used if it's active theme does not show error messages, or if the proxy's error messages have been customised.
	 */
	'proxies'	=>	array(
		'http://GLYPE.PROXY/index.php',
		'http.//ANOTHER.GLYPE.PROXY/index.php'
	)
);
