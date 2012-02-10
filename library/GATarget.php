<?php
/*
 * GlypeAhead
 * Junaid Loonat (junaid@sensepost.com)
 * Port scanning through glype proxies (which have not been heavily themed / modified)
 * 
 * 13 April 2010
 */


/* 
 * GlypeAhead Target Class
 */
class GATarget
{
	private $_host;
	private $_ip;
	private $_ports = array();
	public function load($host, $ports)
	{
		$isValid = false;
		if ($this->addHost($host))
		{
			if (is_array($ports))
			{
				$isValid = true;
				foreach ($ports as $aPort)
				{
					$isValid = $isValid && $this->addPort($aPort);
				}
				if ($isValid)
				{
					rsort($this->_ports);
				}
			} elseif (is_string($ports)) {
				$isValid = $this->addPort($aPort);
			}
		}
		return $isValid;
	}
	private function addHost($host)
	{
		if (is_string($host))
		{
			$host = trim($host);
			$hostIP = gethostbyname($host);
			if (! ip2long($hostIP))
			{
				GlypeAhead::displayError('Failed to resolve target: ' . $host);
			} else {
				$this->_host = $host;
				$this->_ip = $hostIP;
				return true;
			}
		} else {
			GlypeAhead::displayError('Target identifier does not seem to be a valid string');
		}
		return false;
	}
	private function addPort($port)
	{
		$port = trim($port);
		$portN = intval($port);
		if (($portN > 0) && ($portN <= 65535))
		{
			$this->_ports[] = $portN;
			return true;
		} else {
			GlypeAhead::displayError('Invalid port specified for target, ' . $this->_host .': ' . $port);
		}
		return false;
	}
	
	public function unscannedPorts()
	{
		return count($this->_ports);
	}
	public function scanNextPort($proxy)
	{
		$currPortPos = count($this->_ports) - 1;
		$scanResult = array(
			'host'		=>	$this->_host,
			'port'		=>	$this->_ports[$currPortPos]
		);
		$scanResult = array_merge($scanResult, $proxy->getPortInfo($scanResult['host'], $scanResult['port']));
		$scanResult['status'] = $scanResult['status']? 'open' : 'closed';
		array_splice($this->_ports, $currPortPos);
		return $scanResult;
	}
	public function getHost()
	{
		return $this->_host;
	}
	public function getIP()
	{
		return $this->_ip;
	}
	
	
}
