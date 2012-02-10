<?php
/*
 * GlypeAhead
 * Junaid Loonat (junaid@sensepost.com)
 * Port scanning through glype proxies (which have not been heavily themed / modified)
 * 
 * 13 April 2010
 */


/* 
 * GlypeAhead Proxy Class
 */
class GAProxy
{
	private $_url;
	
	public function load($url)
	{
		$url = strtolower($url);
		if (GlypeAhead::stringEndsWith($url, 'index.php'))
		{
			$pageResp = GACurl::request(array('url' => $url));
			if ($pageResp['info']['http_code'] == 200)
			{
				$pageResp = GACurl::request(array('url' => $url . '?e='.urlencode('curl_error')));
				if ($pageResp['info']['http_code'] == 200)
				{
					$firstPlaceholder = strpos($pageResp['body'], '%s');
					$lastPlaceholder = strrpos($pageResp['body'], '%s');
					if ($firstPlaceholder != $lastPlaceholder)
					{
						GlypeAhead::displayError('More than one match for cURL error location: ' . $url . '?e=curl_error');
					} else {
						$this->_url = $url;
						if (! $this->isPortOpen('www.sensepost.com'))
						{
							// port is known to be open
							GlypeAhead::displayError('Portscan failed for known open port, www.sensepost.com:80');
						} elseif (! $this->isPortClosed('www.sensepost.com', 1)) {
							// port is known to be closed
							GlypeAhead::displayError('Portscan failed for known closed port, www.sensepost.com:1');
						} else {
							// proxy seems to be working correctly
							return true;
						}
					}
				} else {
					GlypeAhead::displayError('Response for cURL error test failed with a code of ('.$pageResp['info']['http_code'].') on: ' . $url . '?e=curl_error');
				}
			} else {
				GlypeAhead::displayError('Response for direct request to proxy failed with a code of ('.$pageResp['info']['http_code'].') on: ' . $url);
			}
		} else {
			GlypeAhead::displayError('Proxy URL needs to end with "index.php": ' . $url);
		}
		return false;		
	}
	public function isPortClosed($host, $port = 80)
	{
		return ! $this->isPortOpen($host, $port);
	}
	public function isPortOpen($host, $port = 80)
	{
		$pInfo = $this->getPortInfo($host, $port);
		return $pInfo['status'];
	}
	public function getPortInfo($host, $port)
	{
		$pInfo = array(
			'status'	=>	false,
			'service'	=>	''
		);
		$pageResp = $this->request($host, $port);
		$pageRespMsg = $this->parseForProxyMessage($pageResp['body']);
		$pageRespMsg = strtolower($pageRespMsg);
		switch ($pageRespMsg)
		{
			case "couldn't connect to host":
			case "connect() timed out!":
				// port closed or refusing connections
				break;
			case "couldn't resolve host":
				$pInfo['service'] = 'Proxy failed to resolve host';
				break;
			case "empty reply from server":
				$pInfo['status'] = true;
				$pInfo['service'] = '(Empty reply from server)';
				break;
			default:
				$pInfo['status'] = true;
				if (strlen($pInfo['service']) == 0)
				{
					$tmpBody = str_replace(array("\n", "\r"), ' ', $pageResp['body']);
					$tmpBody = trim($tmpBody);
					if ($tmpBody[0] != '<')
					{
						// assume response is not HTML
						$pInfo['service'] = $tmpBody;
					} elseif (strpos($pageResp['body'], 'glype.com') === false) {
						// webpage does not seem to be glype-related
						$pInfo['service'] = '(HTTP) ' . GlypeAhead::getHTMLPageTitle($pageResp['body']);
					} else {
						// may be a glype error page
						$pInfo['status'] = false;
						$pInfo['service'] = '(Glype Error?) ' . $pageRespMsg . ' - ' . GlypeAhead::getHTMLPageTitle($pageResp['body']);
					}
				}
				break;
		}
		if (strlen($pInfo['service']) > 70)
		{
			$pInfo['service'] = substr($pInfo['service'], 0, 70) . ' ...';
		}
		GlypeAhead::displayDebug('Port '.$port.' on host '.$host.' was '.($pInfo['status']?'open':'closed'));
		return $pInfo;
	}
	private function parseForProxyMessage($response)
	{
		$searchStrStart = 'The requested resource could not be loaded. libcurl returned the error:<br><b>';
		$searchStrEnd = '</b>';
		$respString = '';
		$searchStrStartPos = strpos($response, $searchStrStart);
		if ($searchStrStartPos !== false)
		{
			$searchStrStartPos += strlen($searchStrStart);
			$searchStrEndPos = strpos($response, $searchStrEnd, $searchStrStartPos);
			if ($searchStrEndPos !== false)
			{
				$respString = substr($response, $searchStrStartPos, $searchStrEndPos - $searchStrStartPos);
			}
		}
		return trim($respString);
	}
	public function request($host, $port = 80)
	{
		GlypeAhead::displayDebug('Proxied request to '.$host.':'.$port.' through '.$this->_url);
		$targetUrl = '://'.$host.':'.$port;
		if ($port == 443)
		{
			$targetUrl = 's' . $targetUrl;
		}
		$targetUrl = base64_encode($targetUrl);
		$targetUrl = rawurlencode($targetUrl);
		$url = str_replace('index.php', 'browse.php?u='.urlencode($targetUrl).'&b=13&f=', $this->_url);
		$requestOpts = array(
			'url'		=>	$url,
			'referer'	=>	$this->_url
		);
		return GACurl::request($requestOpts);
	}
}
