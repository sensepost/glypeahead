<?php
/*
 * GlypeAhead
 * Junaid Loonat (junaid@sensepost.com)
 * Port scanning through glype proxies (which have not been heavily themed / modified)
 * 
 * 13 April 2010
 */


/*
 * GlypeAhead Controller Class
 */
class GlypeAhead
{
	const VERSION = '1.1';
	const RELEASEDATE = '13 April 2010';
	private $_config;
	private $_targets = array();
	private $_proxies = array();
	public static $debugEnabled = false;
	
	public function __construct()
	{
		$this->_config = array();
	}
	public function run($configfile)
	{
		if ($this->loadConfig($configfile))
		{
			if ($this->validateConfig($this->_config))
			{
				if (count($this->_proxies) > 0)
				{
					if (count($this->_targets) > 0)
					{
						$this->doPortScan();
					} else {
						GlypeAhead::displayError('Config-Validation completed but no targets were identified');
					}
				} else {
					GlypeAhead::displayError('Config-Validation completed but no usable proxies were identified');
				}
			} else {
				GlypeAhead::displayError('Issues were detected with the configuration');
			}
		} else {
			GlypeAhead::displayError('Failed to load the specified configuration file');
		}
	}
	private function loadConfig($configfile)
	{
		require_once $configfile;
		if (isset($config) && is_array($config))
		{
			$this->_config = $config;
			return true;
		} elseif (! isset($config)) {
			GlypeAhead::displayError('Failed to locate configuration array from in: ' . $configfile);
		} elseif (! is_array($config)) {
			GlypeAhead::displayError('Invalid configuration array in: ' . $configfile);
		}
		return false;
	}
	private function validateConfig($config)
	{
		$isValid = false;
		if (isset($config['debug']))
		{
			GlypeAhead::$debugEnabled = (bool) $config['debug'];
			if (GlypeAhead::$debugEnabled)
			{
				GlypeAhead::displayDebug('Debug has been enabled');
			}
		}
		foreach (array('targets', 'proxies') as $attrib)
		{
			if (isset($config[$attrib]))
			{
				if (is_array($config[$attrib]))
				{
					foreach ($config[$attrib] as $itemKey=>$itemValue)
					{
						switch ($attrib)
						{
							case 'targets':
								$newTarget = new GATarget();
								if (! $newTarget->load($itemKey, $itemValue))
								{
									GlypeAhead::displayDebug('Dropped target from scan: ' . $itemKey);
									return false;
								} else {
									$this->_targets[] = $newTarget;
								}
								break;
							case 'proxies':
								$newProxy = new GAProxy();
								if (! $newProxy->load($itemValue))
								{
									GlypeAhead::displayDebug('Dropped proxy from scan: ' . $itemValue);
									return false;
								} else {
									$this->_proxies[] = $newProxy;
								}
								break;
							default:
								GlypeAhead::displayError('Uncatered attribute type found: '. $attrib);
								break;
						}
					}
				} else {
					GlypeAhead::displayError('Configuration attribute, '. $attrib .', should be an array');
				}
			} else {
				GlypeAhead::displayError('Missing required configuration attribute: '. $attrib);
			}
		}
		if (count($this->_proxies) >= 1)
		{
			if (count($this->_targets) >= 1)
			{
				$isValid = true;
			} else {
				GlypeAhead::displayError('No targets loaded.');
			}
		} else {
			GlypeAhead::displayError('No proxies loaded.');
		}
		return $isValid;		
	}
	private function doPortScan()
	{
		$currProxy = 0;
		$proxyCount = count($this->_proxies);
		$targetCount = count($this->_targets);
		foreach ($this->_targets as $targetHost)
		{
			GlypeAhead::display('>> Scan report for '.$targetHost->getHost().' ('.$targetHost->getIP().')');
			GlypeAhead::display('   PORT     STATE     BANNER');
			$portCount = 0;
			$scanStart = microtime(true);
			while ($targetHost->unscannedPorts() > 0)
			{
				$scanResult = $targetHost->scanNextPort($this->_proxies[$currProxy++]);
				$currProxy %= $proxyCount;
				GlypeAhead::display('   '.str_pad($scanResult['port'], 5).'    '.str_pad($scanResult['status'], 6).'    '.$scanResult['service']);
				$portCount++;
			}
			$scanEnd = microtime(true);
			$proxyCount = count($this->_proxies);
			GlypeAhead::display('Scanned '.$portCount.' port'.(($portCount > 1)? 's':'').' in '.round(($scanEnd - $scanStart), 2).' seconds, using '.$proxyCount.' prox'.($proxyCount>1?'ies':'y'));
			GlypeAhead::display();
		}
	}
	public static function getHTMLPageTitle($pageBody)
	{
		if (preg_match('#<title>(.+?)</title>#msi', $pageBody, $regs)) {
			return $regs[1];
		}
		return 'Untitled';
	}
	public static function display($msg = '')
	{
		echo $msg . "\n";
	}
	public static function displayError($msg)
	{
		GlypeAhead::display('[!] ' . $msg);
	}
	public static function displayInfo($msg)
	{
		GlypeAhead::display('[i] ' . $msg);
	}
	public static function displayDebug($msg)
	{
		if (GlypeAhead::$debugEnabled)
		{
			GlypeAhead::display('[d] ' . $msg);
		}
	}
	public static function displayHeader()
	{
		GlypeAhead::display('SensePost GlypeAhead v'.GlypeAhead::VERSION.' - released on '.GlypeAhead::RELEASEDATE);
		GlypeAhead::display('Junaid Loonat (junaid@sensepost.com)');
	}
	public static function displayHelp()
	{
		GlypeAhead::display('glypeahead [config-file]');
	}
	public static function fatal($msg)
	{
		GlypeAhead::display('[F] ' . $msg);
		GlypeAhead::display();
		die ();
	}
	public static function stringEndsWith($haystack, $needle)
	{
		$needleLength = strlen($needle);
		if (($needleLength > 0) && (substr_compare($haystack, $needle, -1 * $needleLength, $needleLength) == 0))
		{
			return true;
		}
		return false;
	}
}
