<?php
/*
 * GlypeAhead
 * Junaid Loonat (junaid@sensepost.com)
 * Port scanning through glype proxies (which have not been heavily themed / modified)
 * 
 * 13 April 2010
 */


/* 
 * GlypeAhead cURL Wrapper
 */
class GACurl
{
	public static function request($opts)
	{
		$ch = curl_init();
		GlypeAhead::displayDebug('cURL request to '.$opts['url']);
		curl_setopt($ch, CURLOPT_URL, $opts['url']);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; GlypeAhead 1.1)');
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		if (isset($opts['post']))
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
		}
		if (isset($opts['referer']))
		{
			curl_setopt($ch, CURLOPT_REFERER, $opts['referer']);
		}
		$response = array(
			'body'		=>	curl_exec($ch),
			'error'		=>	curl_error($ch),
			'info'		=>	curl_getinfo($ch)
		);
		if (! empty($response['error']))
		{
			GlypeAhead::displayDebug('cURL response generated an error: '.$response['error']);
		}
		GlypeAhead::displayDebug('cURL response HTTP code was '.$response['info']['http_code']);
		curl_close($ch);
		return $response;
	}
}
