<?php
/**
 * A class that loads Flourish
 * 
 * @copyright  Copyright (c) 2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     netcarver [n] <fContrib@netcarving.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fLoader
 * 
 * @version    1.0.0b3
 * @changes    1.0.0b3  Added fEmail() constructor function [n, 2011-09-12]
 * @changes    1.0.0b2  Added fPagination [wb, 2011-09-06]
 * @changes    1.0.0b   The initial implementation [wb, 2011-08-26]
 */
class fLoader
{
	// The following constants allow for nice looking callbacks to static methods
	const autoload       = 'fLoader::autoload';
	const best           = 'fLoader::best';
	const eager          = 'fLoader::eager';
	const hasOpcodeCache = 'fLoader::hasOpcodeCache';
	const lazy           = 'fLoader::lazy';
	
	
	/**
	 * The Flourish classes in dependency order
	 * 
	 * @var array
	 */
	static private $classes = array(
		'fException',
		'fExpectedException',
		'fEmptySetException',
		'fNoRemainingException',
		'fNoRowsException',
		'fNotFoundException',
		'fValidationException',
		'fUnexpectedException',
		'fConnectivityException',
		'fEnvironmentException',
		'fProgrammerException',
		'fSQLException',
		'fActiveRecord',
		'fAuthorization',
		'fAuthorizationException',
		'fBuffer',
		'fCRUD',
		'fCache',
		'fCookie',
		'fCore',
		'fCryptography',
		'fDatabase',
		'fDate',
		'fDirectory',
		'fEmail',
		'fFile',
		'fFilesystem',
		'fGrammar',
		'fHTML',
		'fImage',
		'fJSON',
		'fMailbox',
		'fMessaging',
		'fMoney',
		'fNumber',
		'fORM',
		'fORMColumn',
		'fORMDatabase',
		'fORMDate',
		'fORMFile',
		'fORMJSON',
		'fORMMoney',
		'fORMOrdering',
		'fORMRelated',
		'fORMSchema',
		'fORMValidation',
		'fPagination',
		'fRecordSet',
		'fRequest',
		'fResult',
		'fSMTP',
		'fSQLSchemaTranslation',
		'fSQLTranslation',
		'fSchema',
		'fSession',
		'fStatement',
		'fTemplating',
		'fText',
		'fTime',
		'fTimestamp',
		'fURL',
		'fUTF8',
		'fUnbufferedResult',
		'fUpload',
		'fValidation',
		'fXML'
	);

	/**
	 * The path Flourish is installed into
	 * 
	 * @var string
	 */
	static private $path = NULL;
	
	
	/**
	 * Tries to load a Flourish class
	 * 
	 * @internal
	 *
	 * @param  string $class  The class to load
	 * @return void
	 */
	static public function autoload($class)
	{
		if ($class[0] != 'f' || ord($class[1]) < 65 || ord($class[1]) > 90) {
			return;
		}

		if (!in_array($class, self::$classes)) {
			return;
		}

		include self::$path . $class . '.php';
	}


	/**
	 * Performs eager loading if an op-code cache is present, otherwise lazy
	 * 
	 * @return void
	 */
	static public function best()
	{
		if (self::hasOpcodeCache()) {
			return self::eager();
		}
		self::lazy();
	}


	/**
	 * Creates functions that act as chainable constructors
	 *
	 * @return void
	 */
	static private function createConstructorFunctions()
	{
		if (function_exists('fDate')) {
			return;
		}

		function fDate($date=NULL)
		{
			return new fDate($date);    
		}
		 
		function fDirectory($directory)
		{
			return new fDirectory($directory);    
		}

		function fEmail()
		{
			return new fEmail();
		}
		 
		function fFile($file)
		{
			return new fFile($file);    
		}
		 
		function fImage($file_path)
		{
			return new fImage($file_path);    
		}
		 
		function fMoney($amount, $currency=NULL)
		{
			return new fMoney($amount, $currency);    
		}
		 
		function fNumber($value, $scale=NULL)
		{
			return new fNumber($value, $scale);
		}
		 
		function fTime($time=NULL)
		{
			return new fTime($time);    
		}
		 
		function fTimestamp($datetime=NULL, $timezone=NULL)
		{
			return new fTimestamp($datetime, $timezone);    
		}
	}


	/**
	 * Loads all Flourish classes when called
	 * 
	 * @return void
	 */
	static public function eager()
	{
		self::setPath();
		self::createConstructorFunctions();
		foreach (self::$classes as $class) {
			include self::$path . $class . '.php';
		}
	}


	/**
	 * Check if a PHP opcode cache is installed
	 * 
	 * The following opcode caches are currently detected:
	 * 
	 *  - [http://pecl.php.net/package/APC APC]
	 *  - [http://eaccelerator.net eAccelerator]
	 *  - [http://www.nusphere.com/products/phpexpress.htm Nusphere PhpExpress]
	 *  - [http://turck-mmcache.sourceforge.net/index_old.html Turck MMCache]
	 *  - [http://xcache.lighttpd.net XCache]
	 *  - [http://www.zend.com/en/products/server/ Zend Server (Optimizer+)]
	 *  - [http://www.zend.com/en/products/platform/ Zend Platform (Code Acceleration)]
	 * 
	 * @return boolean  If a PHP opcode cache is loaded
	 */
	static public function hasOpcodeCache()
	{		
		$apc              = ini_get('apc.enabled');
		$eaccelerator     = ini_get('eaccelerator.enable');
		$mmcache          = ini_get('mmcache.enable');
		$phpexpress       = function_exists('phpexpress');
		$xcache           = ini_get('xcache.size') > 0 && ini_get('xcache.cacher');
		$zend_accelerator = ini_get('zend_accelerator.enabled');
		$zend_plus        = ini_get('zend_optimizerplus.enable');
		
		return $apc || $eaccelerator || $mmcache || $phpexpress || $xcache || $zend_accelerator || $zend_plus;
	}


	/**
	 * Registers an autoloader for Flourish via [http://php.net/spl_autoload_register `spl_autoload_register()`]
	 * 
	 * @return void
	 */
	static public function lazy()
	{
		self::setPath();
		self::createConstructorFunctions();

		if (function_exists('__autoload') && !spl_autoload_functions()) {
			throw new Exception(
				'fLoader::lazy() was called, which adds an autoload function ' .
				'via spl_autoload_register(). It appears an __autoload ' . 
				'function has already been defined, but not registered via ' .
				'spl_autoload_register(). Please call ' . 
				'spl_autoload_register("__autoload") after fLoader::lazy() ' .
				'to ensure your autoloader continues to function.'
			);
		}
		
		spl_autoload_register(array('fLoader', 'autoload'));
	}


	/**
	 * Determines where Flourish is installed
	 * 
	 * @return void
	 */
	static private function setPath()
	{
		self::$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fLoader
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2011 Will Bond <will@flourishlib.com>, others
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
