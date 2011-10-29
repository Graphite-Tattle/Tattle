<?php
/**
 * Represents a date and time as a value object
 * 
 * @copyright  Copyright (c) 2008-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fTimestamp
 * 
 * @version    1.0.0b13
 * @changes    1.0.0b13  Fixed a method signature [wb, 2011-08-24]
 * @changes    1.0.0b12  Fixed a bug with the constructor not properly handling unix timestamps that are negative integers [wb, 2011-06-02]
 * @changes    1.0.0b11  Changed the `$timestamp` and `$timezone` attributes to be protected [wb, 2011-03-20]
 * @changes    1.0.0b10  Fixed a bug in ::__construct() with specifying a timezone other than the default for a relative time string such as "now" or "+2 hours" [wb, 2010-07-05]
 * @changes    1.0.0b9   Added the `$simple` parameter to ::getFuzzyDifference() [wb, 2010-03-15]
 * @changes    1.0.0b8   Fixed a bug with ::fixISOWeek() not properly parsing some ISO week dates [wb, 2009-10-06]
 * @changes    1.0.0b7   Fixed a translation bug with ::getFuzzyDifference() [wb, 2009-07-11]
 * @changes    1.0.0b6   Added ::registerUnformatCallback() and ::callUnformatCallback() to allow for localization of date/time parsing [wb, 2009-06-01]
 * @changes    1.0.0b5   Backwards compatibility break - Removed ::getSecondsDifference() and ::getSeconds(), added ::eq(), ::gt(), ::gte(), ::lt(), ::lte() [wb, 2009-03-05]
 * @changes    1.0.0b4   Updated for new fCore API [wb, 2009-02-16]
 * @changes    1.0.0b3   Removed a useless double check of the strtotime() return value in ::__construct() [wb, 2009-01-21]
 * @changes    1.0.0b2   Added support for CURRENT_TIMESTAMP, CURRENT_DATE and CURRENT_TIME SQL keywords [wb, 2009-01-11]
 * @changes    1.0.0b    The initial implementation [wb, 2008-02-12]
 */
class fTimestamp
{
	// The following constants allow for nice looking callbacks to static methods
	const callFormatCallback       = 'fTimestamp::callFormatCallback';
	const callUnformatCallback     = 'fTimestamp::callUnformatCallback';
	const combine                  = 'fTimestamp::combine';
	const defineFormat             = 'fTimestamp::defineFormat';
	const fixISOWeek               = 'fTimestamp::fixISOWeek';
	const getDefaultTimezone       = 'fTimestamp::getDefaultTimezone';
	const isValidTimezone          = 'fTimestamp::isValidTimezone';
	const registerFormatCallback   = 'fTimestamp::registerFormatCallback';
	const registerUnformatCallback = 'fTimestamp::registerUnformatCallback';
	const reset                    = 'fTimestamp::reset';
	const setDefaultTimezone       = 'fTimestamp::setDefaultTimezone';
	const translateFormat          = 'fTimestamp::translateFormat';
	
	
	/**
	 * Pre-defined formatting styles
	 * 
	 * @var array
	 */
	static private $formats = array();
	
	/**
	 * A callback to process all formatting strings through
	 * 
	 * @var callback
	 */
	static private $format_callback = NULL;
	
	/**
	 * A callback to parse all date string to allow for locale-specific parsing
	 * 
	 * @var callback
	 */
	static private $unformat_callback = NULL;
	
	
	/**
	 * If a format callback is defined, call it
	 * 
	 * @internal
	 * 
	 * @param  string $formatted_string  The formatted date/time/timestamp string to be (possibly) modified
	 * @return string  The (possibly) modified formatted string
	 */
	static public function callFormatCallback($formatted_string)
	{
		if (self::$format_callback) {
			return call_user_func(self::$format_callback, $formatted_string);
		}
		return $formatted_string;
	}
	
	
	/**
	 * If an unformat callback is defined, call it
	 * 
	 * @internal
	 * 
	 * @param  string $date_time_string  A raw date/time/timestamp string to be (possibly) parsed/modified
	 * @return string  The (possibly) parsed or modified date/time/timestamp
	 */
	static public function callUnformatCallback($date_time_string)
	{
		if (self::$unformat_callback) {
			return call_user_func(self::$unformat_callback, $date_time_string);
		}
		return $date_time_string;
	}
	
	
	/**
	 * Checks to make sure the current version of PHP is high enough to support timezone features
	 * 
	 * @return void
	 */
	static private function checkPHPVersion()
	{
		if (!fCore::checkVersion('5.1')) {
			throw new fEnvironmentException(
				'The %s class takes advantage of the timezone features in PHP 5.1.0 and newer. Unfortunately it appears you are running an older version of PHP.',
				__CLASS__
			);
		}
	}
	
	
	/**
	 * Composes text using fText if loaded
	 * 
	 * @param  string  $message    The message to compose
	 * @param  mixed   $component  A string or number to insert into the message
	 * @param  mixed   ...
	 * @return string  The composed and possible translated message
	 */
	static protected function compose($message)
	{
		$args = array_slice(func_get_args(), 1);
		
		if (class_exists('fText', FALSE)) {
			return call_user_func_array(
				array('fText', 'compose'),
				array($message, $args)
			);
		} else {
			return vsprintf($message, $args);
		}
	}
	
	
	/**
	 * Creates a reusable format for formatting fDate, fTime, and fTimestamp objects
	 * 
	 * @param  string $name               The name of the format
	 * @param  string $formatting_string  The format string compatible with the [http://php.net/date date()] function
	 * @return void
	 */
	static public function defineFormat($name, $formatting_string)
	{
		self::$formats[$name] = $formatting_string;
	}
	
	
	/**
	 * Fixes an ISO week format into `'Y-m-d'` so [http://php.net/strtotime strtotime()] will accept it
	 * 
	 * @internal
	 * 
	 * @param  string $date  The date to fix
	 * @return string  The fixed date
	 */
	static public function fixISOWeek($date)
	{
		if (preg_match('#^(.*)(\d{4})-W(5[0-3]|[1-4][0-9]|0?[1-9])-([1-7])(.*)$#D', $date, $matches)) {
			$before = $matches[1];
			$year   = $matches[2];
			$week   = $matches[3];
			$day    = $matches[4];
			$after  = $matches[5];
			
			$first_of_year  = strtotime($year . '-01-01');
			$first_thursday = strtotime('thursday', $first_of_year);
			$iso_year_start = strtotime('last monday', $first_thursday);
			
			$ymd = date('Y-m-d', strtotime('+' . ($week-1) . ' weeks +' . ($day-1) . ' days', $iso_year_start));	
			
			$date = $before . $ymd . $after;
		}
		return $date;
	}
	
	
	/**
	 * Provides a consistent interface to getting the default timezone. Wraps the [http://php.net/date_default_timezone_get date_default_timezone_get()] function.
	 * 
	 * @return string  The default timezone used for all date/time calculations
	 */
	static public function getDefaultTimezone()
	{
		self::checkPHPVersion();
		
		return date_default_timezone_get();
	}
	
	
	/**
	 * Checks to see if a timezone is valid
	 * 
	 * @internal
	 * 
	 * @param  string  $timezone   The timezone to check
	 * @return boolean  If the timezone is valid
	 */
	static public function isValidTimezone($timezone)
	{
		static $valid_timezones = array(
			'UTC'                                   => TRUE, 
			'Africa/Abidjan'                        => TRUE, 
			'Africa/Accra'                          => TRUE, 
			'Africa/Addis_Ababa'                    => TRUE, 
			'Africa/Algiers'                        => TRUE, 
			'Africa/Asmara'                         => TRUE, 
			'Africa/Asmera'                         => TRUE, 
			'Africa/Bamako'                         => TRUE, 
			'Africa/Bangui'                         => TRUE, 
			'Africa/Banjul'                         => TRUE, 
			'Africa/Bissau'                         => TRUE, 
			'Africa/Blantyre'                       => TRUE, 
			'Africa/Brazzaville'                    => TRUE, 
			'Africa/Bujumbura'                      => TRUE, 
			'Africa/Cairo'                          => TRUE, 
			'Africa/Casablanca'                     => TRUE, 
			'Africa/Ceuta'                          => TRUE, 
			'Africa/Conakry'                        => TRUE, 
			'Africa/Dakar'                          => TRUE, 
			'Africa/Dar_es_Salaam'                  => TRUE, 
			'Africa/Djibouti'                       => TRUE, 
			'Africa/Douala'                         => TRUE, 
			'Africa/El_Aaiun'                       => TRUE, 
			'Africa/Freetown'                       => TRUE, 
			'Africa/Gaborone'                       => TRUE, 
			'Africa/Harare'                         => TRUE, 
			'Africa/Johannesburg'                   => TRUE, 
			'Africa/Kampala'                        => TRUE, 
			'Africa/Khartoum'                       => TRUE, 
			'Africa/Kigali'                         => TRUE, 
			'Africa/Kinshasa'                       => TRUE, 
			'Africa/Lagos'                          => TRUE, 
			'Africa/Libreville'                     => TRUE, 
			'Africa/Lome'                           => TRUE, 
			'Africa/Luanda'                         => TRUE, 
			'Africa/Lubumbashi'                     => TRUE, 
			'Africa/Lusaka'                         => TRUE, 
			'Africa/Malabo'                         => TRUE, 
			'Africa/Maputo'                         => TRUE, 
			'Africa/Maseru'                         => TRUE, 
			'Africa/Mbabane'                        => TRUE, 
			'Africa/Mogadishu'                      => TRUE, 
			'Africa/Monrovia'                       => TRUE, 
			'Africa/Nairobi'                        => TRUE, 
			'Africa/Ndjamena'                       => TRUE, 
			'Africa/Niamey'                         => TRUE, 
			'Africa/Nouakchott'                     => TRUE, 
			'Africa/Ouagadougou'                    => TRUE, 
			'Africa/Porto-Novo'                     => TRUE, 
			'Africa/Sao_Tome'                       => TRUE, 
			'Africa/Timbuktu'                       => TRUE, 
			'Africa/Tripoli'                        => TRUE, 
			'Africa/Tunis'                          => TRUE, 
			'Africa/Windhoek'                       => TRUE, 
			'America/Adak'                          => TRUE, 
			'America/Anchorage'                     => TRUE, 
			'America/Anguilla'                      => TRUE, 
			'America/Antigua'                       => TRUE, 
			'America/Araguaina'                     => TRUE, 
			'America/Argentina/Buenos_Aires'        => TRUE, 
			'America/Argentina/Catamarca'           => TRUE, 
			'America/Argentina/ComodRivadavia'      => TRUE, 
			'America/Argentina/Cordoba'             => TRUE, 
			'America/Argentina/Jujuy'               => TRUE, 
			'America/Argentina/La_Rioja'            => TRUE, 
			'America/Argentina/Mendoza'             => TRUE, 
			'America/Argentina/Rio_Gallegos'        => TRUE, 
			'America/Argentina/San_Juan'            => TRUE, 
			'America/Argentina/San_Luis'            => TRUE, 
			'America/Argentina/Tucuman'             => TRUE, 
			'America/Argentina/Ushuaia'             => TRUE, 
			'America/Aruba'                         => TRUE, 
			'America/Asuncion'                      => TRUE, 
			'America/Atikokan'                      => TRUE, 
			'America/Atka'                          => TRUE, 
			'America/Bahia'                         => TRUE, 
			'America/Barbados'                      => TRUE, 
			'America/Belem'                         => TRUE, 
			'America/Belize'                        => TRUE, 
			'America/Blanc-Sablon'                  => TRUE, 
			'America/Boa_Vista'                     => TRUE, 
			'America/Bogota'                        => TRUE, 
			'America/Boise'                         => TRUE, 
			'America/Buenos_Aires'                  => TRUE, 
			'America/Cambridge_Bay'                 => TRUE, 
			'America/Campo_Grande'                  => TRUE, 
			'America/Cancun'                        => TRUE, 
			'America/Caracas'                       => TRUE, 
			'America/Catamarca'                     => TRUE, 
			'America/Cayenne'                       => TRUE, 
			'America/Cayman'                        => TRUE, 
			'America/Chicago'                       => TRUE, 
			'America/Chihuahua'                     => TRUE, 
			'America/Coral_Harbour'                 => TRUE, 
			'America/Cordoba'                       => TRUE, 
			'America/Costa_Rica'                    => TRUE, 
			'America/Cuiaba'                        => TRUE, 
			'America/Curacao'                       => TRUE, 
			'America/Danmarkshavn'                  => TRUE, 
			'America/Dawson'                        => TRUE, 
			'America/Dawson_Creek'                  => TRUE, 
			'America/Denver'                        => TRUE, 
			'America/Detroit'                       => TRUE, 
			'America/Dominica'                      => TRUE, 
			'America/Edmonton'                      => TRUE, 
			'America/Eirunepe'                      => TRUE, 
			'America/El_Salvador'                   => TRUE, 
			'America/Ensenada'                      => TRUE, 
			'America/Fort_Wayne'                    => TRUE, 
			'America/Fortaleza'                     => TRUE, 
			'America/Glace_Bay'                     => TRUE, 
			'America/Godthab'                       => TRUE, 
			'America/Goose_Bay'                     => TRUE, 
			'America/Grand_Turk'                    => TRUE, 
			'America/Grenada'                       => TRUE, 
			'America/Guadeloupe'                    => TRUE, 
			'America/Guatemala'                     => TRUE, 
			'America/Guayaquil'                     => TRUE, 
			'America/Guyana'                        => TRUE, 
			'America/Halifax'                       => TRUE, 
			'America/Havana'                        => TRUE, 
			'America/Hermosillo'                    => TRUE, 
			'America/Indiana/Indianapolis'          => TRUE, 
			'America/Indiana/Knox'                  => TRUE, 
			'America/Indiana/Marengo'               => TRUE, 
			'America/Indiana/Petersburg'            => TRUE, 
			'America/Indiana/Tell_City'             => TRUE, 
			'America/Indiana/Vevay'                 => TRUE, 
			'America/Indiana/Vincennes'             => TRUE, 
			'America/Indiana/Winamac'               => TRUE, 
			'America/Indianapolis'                  => TRUE, 
			'America/Inuvik'                        => TRUE, 
			'America/Iqaluit'                       => TRUE, 
			'America/Jamaica'                       => TRUE, 
			'America/Jujuy'                         => TRUE, 
			'America/Juneau'                        => TRUE, 
			'America/Kentucky/Louisville'           => TRUE, 
			'America/Kentucky/Monticello'           => TRUE, 
			'America/Knox_IN'                       => TRUE, 
			'America/La_Paz'                        => TRUE, 
			'America/Lima'                          => TRUE, 
			'America/Los_Angeles'                   => TRUE, 
			'America/Louisville'                    => TRUE, 
			'America/Maceio'                        => TRUE, 
			'America/Managua'                       => TRUE, 
			'America/Manaus'                        => TRUE, 
			'America/Marigot'                       => TRUE, 
			'America/Martinique'                    => TRUE, 
			'America/Mazatlan'                      => TRUE, 
			'America/Mendoza'                       => TRUE, 
			'America/Menominee'                     => TRUE, 
			'America/Merida'                        => TRUE, 
			'America/Mexico_City'                   => TRUE, 
			'America/Miquelon'                      => TRUE, 
			'America/Moncton'                       => TRUE, 
			'America/Monterrey'                     => TRUE, 
			'America/Montevideo'                    => TRUE, 
			'America/Montreal'                      => TRUE, 
			'America/Montserrat'                    => TRUE, 
			'America/Nassau'                        => TRUE, 
			'America/New_York'                      => TRUE, 
			'America/Nipigon'                       => TRUE, 
			'America/Nome'                          => TRUE, 
			'America/Noronha'                       => TRUE, 
			'America/North_Dakota/Center'           => TRUE, 
			'America/North_Dakota/New_Salem'        => TRUE, 
			'America/Panama'                        => TRUE, 
			'America/Pangnirtung'                   => TRUE, 
			'America/Paramaribo'                    => TRUE, 
			'America/Phoenix'                       => TRUE, 
			'America/Port-au-Prince'                => TRUE, 
			'America/Port_of_Spain'                 => TRUE, 
			'America/Porto_Acre'                    => TRUE, 
			'America/Porto_Velho'                   => TRUE, 
			'America/Puerto_Rico'                   => TRUE, 
			'America/Rainy_River'                   => TRUE, 
			'America/Rankin_Inlet'                  => TRUE, 
			'America/Recife'                        => TRUE, 
			'America/Regina'                        => TRUE, 
			'America/Resolute'                      => TRUE, 
			'America/Rio_Branco'                    => TRUE, 
			'America/Rosario'                       => TRUE, 
			'America/Santiago'                      => TRUE, 
			'America/Santo_Domingo'                 => TRUE, 
			'America/Sao_Paulo'                     => TRUE, 
			'America/Scoresbysund'                  => TRUE, 
			'America/Shiprock'                      => TRUE, 
			'America/St_Barthelemy'                 => TRUE, 
			'America/St_Johns'                      => TRUE, 
			'America/St_Kitts'                      => TRUE, 
			'America/St_Lucia'                      => TRUE, 
			'America/St_Thomas'                     => TRUE, 
			'America/St_Vincent'                    => TRUE, 
			'America/Swift_Current'                 => TRUE, 
			'America/Tegucigalpa'                   => TRUE, 
			'America/Thule'                         => TRUE, 
			'America/Thunder_Bay'                   => TRUE, 
			'America/Tijuana'                       => TRUE, 
			'America/Toronto'                       => TRUE, 
			'America/Tortola'                       => TRUE, 
			'America/Vancouver'                     => TRUE, 
			'America/Virgin'                        => TRUE, 
			'America/Whitehorse'                    => TRUE, 
			'America/Winnipeg'                      => TRUE, 
			'America/Yakutat'                       => TRUE, 
			'America/Yellowknife'                   => TRUE, 
			'Antarctica/Casey'                      => TRUE, 
			'Antarctica/Davis'                      => TRUE, 
			'Antarctica/DumontDUrville'             => TRUE, 
			'Antarctica/Mawson'                     => TRUE, 
			'Antarctica/McMurdo'                    => TRUE, 
			'Antarctica/Palmer'                     => TRUE, 
			'Antarctica/Rothera'                    => TRUE, 
			'Antarctica/South_Pole'                 => TRUE, 
			'Antarctica/Syowa'                      => TRUE, 
			'Antarctica/Vostok'                     => TRUE, 
			'Arctic/Longyearbyen'                   => TRUE, 
			'Asia/Aden'                             => TRUE, 
			'Asia/Almaty'                           => TRUE, 
			'Asia/Amman'                            => TRUE, 
			'Asia/Anadyr'                           => TRUE, 
			'Asia/Aqtau'                            => TRUE, 
			'Asia/Aqtobe'                           => TRUE, 
			'Asia/Ashgabat'                         => TRUE, 
			'Asia/Ashkhabad'                        => TRUE, 
			'Asia/Baghdad'                          => TRUE, 
			'Asia/Bahrain'                          => TRUE, 
			'Asia/Baku'                             => TRUE, 
			'Asia/Bangkok'                          => TRUE, 
			'Asia/Beirut'                           => TRUE, 
			'Asia/Bishkek'                          => TRUE, 
			'Asia/Brunei'                           => TRUE, 
			'Asia/Calcutta'                         => TRUE, 
			'Asia/Choibalsan'                       => TRUE, 
			'Asia/Chongqing'                        => TRUE, 
			'Asia/Chungking'                        => TRUE, 
			'Asia/Colombo'                          => TRUE, 
			'Asia/Dacca'                            => TRUE, 
			'Asia/Damascus'                         => TRUE, 
			'Asia/Dhaka'                            => TRUE, 
			'Asia/Dili'                             => TRUE, 
			'Asia/Dubai'                            => TRUE, 
			'Asia/Dushanbe'                         => TRUE, 
			'Asia/Gaza'                             => TRUE, 
			'Asia/Harbin'                           => TRUE, 
			'Asia/Ho_Chi_Minh'                      => TRUE, 
			'Asia/Hong_Kong'                        => TRUE, 
			'Asia/Hovd'                             => TRUE, 
			'Asia/Irkutsk'                          => TRUE, 
			'Asia/Istanbul'                         => TRUE, 
			'Asia/Jakarta'                          => TRUE, 
			'Asia/Jayapura'                         => TRUE, 
			'Asia/Jerusalem'                        => TRUE, 
			'Asia/Kabul'                            => TRUE, 
			'Asia/Kamchatka'                        => TRUE, 
			'Asia/Karachi'                          => TRUE, 
			'Asia/Kashgar'                          => TRUE, 
			'Asia/Katmandu'                         => TRUE, 
			'Asia/Kolkata'                          => TRUE, 
			'Asia/Krasnoyarsk'                      => TRUE, 
			'Asia/Kuala_Lumpur'                     => TRUE, 
			'Asia/Kuching'                          => TRUE, 
			'Asia/Kuwait'                           => TRUE, 
			'Asia/Macao'                            => TRUE, 
			'Asia/Macau'                            => TRUE, 
			'Asia/Magadan'                          => TRUE, 
			'Asia/Makassar'                         => TRUE, 
			'Asia/Manila'                           => TRUE, 
			'Asia/Muscat'                           => TRUE, 
			'Asia/Nicosia'                          => TRUE, 
			'Asia/Novosibirsk'                      => TRUE, 
			'Asia/Omsk'                             => TRUE, 
			'Asia/Oral'                             => TRUE, 
			'Asia/Phnom_Penh'                       => TRUE, 
			'Asia/Pontianak'                        => TRUE, 
			'Asia/Pyongyang'                        => TRUE, 
			'Asia/Qatar'                            => TRUE, 
			'Asia/Qyzylorda'                        => TRUE, 
			'Asia/Rangoon'                          => TRUE, 
			'Asia/Riyadh'                           => TRUE, 
			'Asia/Saigon'                           => TRUE, 
			'Asia/Sakhalin'                         => TRUE, 
			'Asia/Samarkand'                        => TRUE, 
			'Asia/Seoul'                            => TRUE, 
			'Asia/Shanghai'                         => TRUE, 
			'Asia/Singapore'                        => TRUE, 
			'Asia/Taipei'                           => TRUE, 
			'Asia/Tashkent'                         => TRUE, 
			'Asia/Tbilisi'                          => TRUE, 
			'Asia/Tehran'                           => TRUE, 
			'Asia/Tel_Aviv'                         => TRUE, 
			'Asia/Thimbu'                           => TRUE, 
			'Asia/Thimphu'                          => TRUE, 
			'Asia/Tokyo'                            => TRUE, 
			'Asia/Ujung_Pandang'                    => TRUE, 
			'Asia/Ulaanbaatar'                      => TRUE, 
			'Asia/Ulan_Bator'                       => TRUE, 
			'Asia/Urumqi'                           => TRUE, 
			'Asia/Vientiane'                        => TRUE, 
			'Asia/Vladivostok'                      => TRUE, 
			'Asia/Yakutsk'                          => TRUE, 
			'Asia/Yekaterinburg'                    => TRUE, 
			'Asia/Yerevan'                          => TRUE, 
			'Atlantic/Azores'                       => TRUE, 
			'Atlantic/Bermuda'                      => TRUE, 
			'Atlantic/Canary'                       => TRUE, 
			'Atlantic/Cape_Verde'                   => TRUE, 
			'Atlantic/Faeroe'                       => TRUE, 
			'Atlantic/Faroe'                        => TRUE, 
			'Atlantic/Jan_Mayen'                    => TRUE, 
			'Atlantic/Madeira'                      => TRUE, 
			'Atlantic/Reykjavik'                    => TRUE, 
			'Atlantic/South_Georgia'                => TRUE, 
			'Atlantic/St_Helena'                    => TRUE, 
			'Atlantic/Stanley'                      => TRUE, 
			'Australia/ACT'                         => TRUE, 
			'Australia/Adelaide'                    => TRUE, 
			'Australia/Brisbane'                    => TRUE, 
			'Australia/Broken_Hill'                 => TRUE, 
			'Australia/Canberra'                    => TRUE, 
			'Australia/Currie'                      => TRUE, 
			'Australia/Darwin'                      => TRUE, 
			'Australia/Eucla'                       => TRUE, 
			'Australia/Hobart'                      => TRUE, 
			'Australia/LHI'                         => TRUE, 
			'Australia/Lindeman'                    => TRUE, 
			'Australia/Lord_Howe'                   => TRUE, 
			'Australia/Melbourne'                   => TRUE, 
			'Australia/North'                       => TRUE, 
			'Australia/NSW'                         => TRUE, 
			'Australia/Perth'                       => TRUE, 
			'Australia/Queensland'                  => TRUE, 
			'Australia/South'                       => TRUE, 
			'Australia/Sydney'                      => TRUE, 
			'Australia/Tasmania'                    => TRUE, 
			'Australia/Victoria'                    => TRUE, 
			'Australia/West'                        => TRUE, 
			'Australia/Yancowinna'                  => TRUE, 
			'Europe/Amsterdam'                      => TRUE, 
			'Europe/Andorra'                        => TRUE, 
			'Europe/Athens'                         => TRUE, 
			'Europe/Belfast'                        => TRUE, 
			'Europe/Belgrade'                       => TRUE, 
			'Europe/Berlin'                         => TRUE, 
			'Europe/Bratislava'                     => TRUE, 
			'Europe/Brussels'                       => TRUE, 
			'Europe/Bucharest'                      => TRUE, 
			'Europe/Budapest'                       => TRUE, 
			'Europe/Chisinau'                       => TRUE, 
			'Europe/Copenhagen'                     => TRUE, 
			'Europe/Dublin'                         => TRUE, 
			'Europe/Gibraltar'                      => TRUE, 
			'Europe/Guernsey'                       => TRUE, 
			'Europe/Helsinki'                       => TRUE, 
			'Europe/Isle_of_Man'                    => TRUE, 
			'Europe/Istanbul'                       => TRUE, 
			'Europe/Jersey'                         => TRUE, 
			'Europe/Kaliningrad'                    => TRUE, 
			'Europe/Kiev'                           => TRUE, 
			'Europe/Lisbon'                         => TRUE, 
			'Europe/Ljubljana'                      => TRUE, 
			'Europe/London'                         => TRUE, 
			'Europe/Luxembourg'                     => TRUE, 
			'Europe/Madrid'                         => TRUE, 
			'Europe/Malta'                          => TRUE, 
			'Europe/Mariehamn'                      => TRUE, 
			'Europe/Minsk'                          => TRUE, 
			'Europe/Monaco'                         => TRUE, 
			'Europe/Moscow'                         => TRUE, 
			'Europe/Nicosia'                        => TRUE, 
			'Europe/Oslo'                           => TRUE, 
			'Europe/Paris'                          => TRUE, 
			'Europe/Podgorica'                      => TRUE, 
			'Europe/Prague'                         => TRUE, 
			'Europe/Riga'                           => TRUE, 
			'Europe/Rome'                           => TRUE, 
			'Europe/Samara'                         => TRUE, 
			'Europe/San_Marino'                     => TRUE, 
			'Europe/Sarajevo'                       => TRUE, 
			'Europe/Simferopol'                     => TRUE, 
			'Europe/Skopje'                         => TRUE, 
			'Europe/Sofia'                          => TRUE, 
			'Europe/Stockholm'                      => TRUE, 
			'Europe/Tallinn'                        => TRUE, 
			'Europe/Tirane'                         => TRUE, 
			'Europe/Tiraspol'                       => TRUE, 
			'Europe/Uzhgorod'                       => TRUE, 
			'Europe/Vaduz'                          => TRUE, 
			'Europe/Vatican'                        => TRUE, 
			'Europe/Vienna'                         => TRUE, 
			'Europe/Vilnius'                        => TRUE, 
			'Europe/Volgograd'                      => TRUE, 
			'Europe/Warsaw'                         => TRUE, 
			'Europe/Zagreb'                         => TRUE, 
			'Europe/Zaporozhye'                     => TRUE, 
			'Europe/Zurich'                         => TRUE, 
			'Indian/Antananarivo'                   => TRUE, 
			'Indian/Chagos'                         => TRUE, 
			'Indian/Christmas'                      => TRUE, 
			'Indian/Cocos'                          => TRUE, 
			'Indian/Comoro'                         => TRUE, 
			'Indian/Kerguelen'                      => TRUE, 
			'Indian/Mahe'                           => TRUE, 
			'Indian/Maldives'                       => TRUE, 
			'Indian/Mauritius'                      => TRUE, 
			'Indian/Mayotte'                        => TRUE, 
			'Indian/Reunion'                        => TRUE, 
			'Pacific/Apia'                          => TRUE, 
			'Pacific/Auckland'                      => TRUE, 
			'Pacific/Chatham'                       => TRUE, 
			'Pacific/Easter'                        => TRUE, 
			'Pacific/Efate'                         => TRUE, 
			'Pacific/Enderbury'                     => TRUE, 
			'Pacific/Fakaofo'                       => TRUE, 
			'Pacific/Fiji'                          => TRUE, 
			'Pacific/Funafuti'                      => TRUE, 
			'Pacific/Galapagos'                     => TRUE, 
			'Pacific/Gambier'                       => TRUE, 
			'Pacific/Guadalcanal'                   => TRUE, 
			'Pacific/Guam'                          => TRUE, 
			'Pacific/Honolulu'                      => TRUE, 
			'Pacific/Johnston'                      => TRUE, 
			'Pacific/Kiritimati'                    => TRUE, 
			'Pacific/Kosrae'                        => TRUE, 
			'Pacific/Kwajalein'                     => TRUE, 
			'Pacific/Majuro'                        => TRUE, 
			'Pacific/Marquesas'                     => TRUE, 
			'Pacific/Midway'                        => TRUE, 
			'Pacific/Nauru'                         => TRUE, 
			'Pacific/Niue'                          => TRUE, 
			'Pacific/Norfolk'                       => TRUE, 
			'Pacific/Noumea'                        => TRUE, 
			'Pacific/Pago_Pago'                     => TRUE, 
			'Pacific/Palau'                         => TRUE, 
			'Pacific/Pitcairn'                      => TRUE, 
			'Pacific/Ponape'                        => TRUE, 
			'Pacific/Port_Moresby'                  => TRUE, 
			'Pacific/Rarotonga'                     => TRUE, 
			'Pacific/Saipan'                        => TRUE, 
			'Pacific/Samoa'                         => TRUE, 
			'Pacific/Tahiti'                        => TRUE, 
			'Pacific/Tarawa'                        => TRUE, 
			'Pacific/Tongatapu'                     => TRUE, 
			'Pacific/Truk'                          => TRUE, 
			'Pacific/Wake'                          => TRUE, 
			'Pacific/Wallis'                        => TRUE
		);
		
		return isset($valid_timezones[$timezone]);
	}
	
	
	/**
	 * Allows setting a callback to translate or modify any return values from ::format(), fDate::format() and fTime::format()
	 * 
	 * @param  callback $callback  The callback to pass all formatted dates/times/timestamps through. Should accept a single string and return a single string.
	 * @return void
	 */
	static public function registerFormatCallback($callback)
	{
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		self::$format_callback = $callback;
	}
	
	
	/**
	 * Allows setting a callback to parse any date strings passed into ::__construct(), fDate::__construct() and fTime::__construct()
	 * 
	 * @param  callback $callback  The callback to pass all date/time/timestamp strings through. Should accept a single string and return a single string that is parsable by [http://php.net/strtotime `strtotime()`].
	 * @return void
	 */
	static public function registerUnformatCallback($callback)
	{
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		self::$unformat_callback = $callback;
	}
	
	
	/**
	 * Resets the configuration of the class
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function reset()
	{
		self::$formats         = array();
		self::$format_callback = NULL;
	}
	
	
	/**
	 * Provides a consistent interface to setting the default timezone. Wraps the [http://php.net/date_default_timezone_set date_default_timezone_set()] function.
	 * 
	 * @param  string $timezone  The default timezone to use for all date/time calculations
	 * @return void
	 */
	static public function setDefaultTimezone($timezone)
	{
		self::checkPHPVersion();
		
		$result = date_default_timezone_set($timezone);
		if (!$result) {
			throw new fProgrammerException(
				'The timezone specified, %s, is not a valid timezone',
				$timezone
			);
		}
	}
	
	
	/**
	 * Takes a format name set via ::defineFormat() and returns the [http://php.net/date date()] function formatting string
	 * 
	 * @internal
	 * 
	 * @param  string $format  The format to translate
	 * @return string  The formatting string. If no matching format was found, this will be the same as the `$format` parameter.
	 */
	static public function translateFormat($format)
	{
		if (isset(self::$formats[$format])) {
			$format = self::$formats[$format];
		}
		return $format;
	}
	
	
	/**
	 * The date/time
	 * 
	 * @var integer
	 */
	protected $timestamp;
	
	/**
	 * The timezone for this date/time
	 * 
	 * @var string
	 */
	protected $timezone;
	
	
	/**
	 * Creates the date/time to represent
	 * 
	 * @throws fValidationException  When `$datetime` is not a valid date/time, date or time value
	 * 
	 * @param  fTimestamp|object|string|integer $datetime  The date/time to represent, `NULL` is interpreted as now
	 * @param  string $timezone  The timezone for the date/time. This causes the date/time to be interpretted as being in the specified timezone. If not specified, will default to timezone set by ::setDefaultTimezone().
	 * @return fTimestamp
	 */
	public function __construct($datetime=NULL, $timezone=NULL)
	{
		self::checkPHPVersion();
		
		$default_tz = date_default_timezone_get();
		
		if ($timezone) {
			if (!self::isValidTimezone($timezone)) {
				throw new fValidationException(
					'The timezone specified, %s, is not a valid timezone',
					$timezone
				);
			}
			
		} elseif ($datetime instanceof fTimestamp) {
			$timezone = $datetime->timezone;
			
		} else {
			$timezone = $default_tz;
		}
		
		$this->timezone = $timezone;
		
		if ($datetime === NULL) {
			$timestamp = time();
		} elseif (is_numeric($datetime) && preg_match('#^-?\d+$#D', $datetime)) {
			$timestamp = (int) $datetime;
		} elseif (is_string($datetime) && in_array(strtoupper($datetime), array('CURRENT_TIMESTAMP', 'CURRENT_TIME'))) {
			$timestamp = time();
		} elseif (is_string($datetime) && strtoupper($datetime) == 'CURRENT_DATE') {
			$timestamp = strtotime(date('Y-m-d'));
		} else {
			if (is_object($datetime) && is_callable(array($datetime, '__toString'))) {
				$datetime = $datetime->__toString();	
			} elseif (is_numeric($datetime) || is_object($datetime)) {
				$datetime = (string) $datetime;	
			}
			
			$datetime = self::callUnformatCallback($datetime);
			
			if ($timezone != $default_tz) {
				date_default_timezone_set($timezone);
			}
			$timestamp = strtotime(self::fixISOWeek($datetime));
			if ($timezone != $default_tz) {
				date_default_timezone_set($default_tz);
			}
		}
		
		if ($timestamp === FALSE) {
			throw new fValidationException(
				'The date/time specified, %s, does not appear to be a valid date/time',
				$datetime
			);
		}
		
		$this->timestamp = $timestamp;
	}
	
	
	/**
	 * All requests that hit this method should be requests for callbacks
	 * 
	 * @internal
	 * 
	 * @param  string $method  The method to create a callback for
	 * @return callback  The callback for the method requested
	 */
	public function __get($method)
	{
		return array($this, $method);		
	}
	
	
	/**
	 * Returns this date/time
	 * 
	 * @return string  The `'Y-m-d H:i:s'` format of this date/time
	 */
	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}
	
	
	/**
	 * Changes the date/time by the adjustment specified
	 * 
	 * @throws fValidationException  When `$adjustment` is not a valid relative date/time measurement or timezone
	 * 
	 * @param  string $adjustment  The adjustment to make - may be a relative adjustment or a different timezone
	 * @return fTimestamp  The adjusted date/time
	 */
	public function adjust($adjustment)
	{
		if (self::isValidTimezone($adjustment)) {
			$timezone  = $adjustment;
			$timestamp = $this->timestamp;
		
		} else {
			$timezone  = $this->timezone;
			$timestamp = strtotime($adjustment, $this->timestamp);
			
			if ($timestamp === FALSE || $timestamp === -1) {
				throw new fValidationException(
					'The adjustment specified, %s, does not appear to be a valid relative date/time measurement',
					$adjustment
				);
			}
		}
		
		return new fTimestamp($timestamp, $timezone);
	}
	
	
	/**
	 * If this timestamp is equal to the timestamp passed
	 * 
	 * @param  fTimestamp|object|string|integer $other_timestamp  The timestamp to compare with, `NULL` is interpreted as today
	 * @return boolean  If this timestamp is equal to the one passed
	 */
	public function eq($other_timestamp=NULL)
	{
		$other_timestamp = new fTimestamp($other_timestamp);
		return $this->timestamp == $other_timestamp->timestamp;
	}
	
	
	/**
	 * Formats the date/time
	 * 
	 * @param  string $format  The [http://php.net/date date()] function compatible formatting string, or a format name from ::defineFormat()
	 * @return string  The formatted date/time
	 */
	public function format($format)
	{
		$format = self::translateFormat($format);
		
		$default_tz = date_default_timezone_get();
		date_default_timezone_set($this->timezone);
		
		$formatted = date($format, $this->timestamp);
		
		date_default_timezone_set($default_tz);
		
		return self::callFormatCallback($formatted);
	}
	
	
	/**
	 * Returns the approximate difference in time, discarding any unit of measure but the least specific.
	 * 
	 * The output will read like:
	 * 
	 *  - "This timestamp is `{return value}` the provided one" when a timestamp it passed
	 *  - "This timestamp is `{return value}`" when no timestamp is passed and comparing with the current timestamp
	 * 
	 * Examples of output for a timestamp passed might be:
	 * 
	 *  - `'5 minutes after'`
	 *  - `'2 hours before'`
	 *  - `'2 days after'`
	 *  - `'at the same time'`
	 * 
	 * Examples of output for no timestamp passed might be:
	 * 
	 *  - `'5 minutes ago'`
	 *  - `'2 hours ago'`
	 *  - `'2 days from now'`
	 *  - `'1 year ago'`
	 *  - `'right now'`
	 * 
	 * You would never get the following output since it includes more than one unit of time measurement:
	 * 
	 *  - `'5 minutes and 28 seconds'`
	 *  - `'3 weeks, 1 day and 4 hours'`
	 * 
	 * Values that are close to the next largest unit of measure will be rounded up:
	 * 
	 *  - `'55 minutes'` would be represented as `'1 hour'`, however `'45 minutes'` would not
	 *  - `'29 days'` would be represented as `'1 month'`, but `'21 days'` would be shown as `'3 weeks'`
	 * 
	 * @param  fTimestamp|object|string|integer $other_timestamp  The timestamp to create the difference with, `NULL` is interpreted as now
	 * @param  boolean                          $simple           When `TRUE`, the returned value will only include the difference in the two timestamps, but not `from now`, `ago`, `after` or `before`
	 * @param  boolean                          |$simple
	 * @return string  The fuzzy difference in time between the this timestamp and the one provided
	 */
	public function getFuzzyDifference($other_timestamp=NULL, $simple=FALSE)
	{
		if (is_bool($other_timestamp)) {
			$simple          = $other_timestamp;
			$other_timestamp = NULL;
		}
		
		$relative_to_now = FALSE;
		if ($other_timestamp === NULL) {
			$relative_to_now = TRUE;
		}
		$other_timestamp = new fTimestamp($other_timestamp);
		
		$diff = $this->timestamp - $other_timestamp->timestamp;
		
		if (abs($diff) < 10) {
			if ($relative_to_now) {
				return self::compose('right now');
			}
			return self::compose('at the same time');
		}
		
		$break_points = array(
			/* 45 seconds  */
			45         => array(1,        self::compose('second'), self::compose('seconds')),
			/* 45 minutes  */
			2700       => array(60,       self::compose('minute'), self::compose('minutes')),
			/* 18 hours    */
			64800      => array(3600,     self::compose('hour'),   self::compose('hours')),
			/* 5 days      */
			432000     => array(86400,    self::compose('day'),    self::compose('days')),
			/* 3 weeks     */
			1814400    => array(604800,   self::compose('week'),   self::compose('weeks')),
			/* 9 months    */
			23328000   => array(2592000,  self::compose('month'),  self::compose('months')),
			/* largest int */
			2147483647 => array(31536000, self::compose('year'),   self::compose('years'))
		);
		
		foreach ($break_points as $break_point => $unit_info) {
			if (abs($diff) > $break_point) { continue; }
			
			$unit_diff = round(abs($diff)/$unit_info[0]);
			$units     = fGrammar::inflectOnQuantity($unit_diff, $unit_info[1], $unit_info[2]);
			break;
		}
		
		if ($simple) {
			return self::compose('%1$s %2$s', $unit_diff, $units);
		}
		
		if ($relative_to_now) {
			if ($diff > 0) {
				return self::compose('%1$s %2$s from now', $unit_diff, $units);
			}
		
			return self::compose('%1$s %2$s ago', $unit_diff, $units);
		}
		
		if ($diff > 0) {
			return self::compose('%1$s %2$s after', $unit_diff, $units);
		}
		
		return self::compose('%1$s %2$s before', $unit_diff, $units);
	}
	
	
	/**
	 * If this timestamp is greater than the timestamp passed
	 * 
	 * @param  fTimestamp|object|string|integer $other_timestamp  The timestamp to compare with, `NULL` is interpreted as now
	 * @return boolean  If this timestamp is greater than the one passed
	 */
	public function gt($other_timestamp=NULL)
	{
		$other_timestamp = new fTimestamp($other_timestamp);
		return $this->timestamp > $other_timestamp->timestamp;
	}
	
	
	/**
	 * If this timestamp is greater than or equal to the timestamp passed
	 * 
	 * @param  fTimestamp|object|string|integer $other_timestamp  The timestamp to compare with, `NULL` is interpreted as now
	 * @return boolean  If this timestamp is greater than or equal to the one passed
	 */
	public function gte($other_timestamp=NULL)
	{
		$other_timestamp = new fTimestamp($other_timestamp);
		return $this->timestamp >= $other_timestamp->timestamp;
	}
	
	
	/**
	 * If this timestamp is less than the timestamp passed
	 * 
	 * @param  fTimestamp|object|string|integer $other_timestamp  The timestamp to compare with, `NULL` is interpreted as today
	 * @return boolean  If this timestamp is less than the one passed
	 */
	public function lt($other_timestamp=NULL)
	{
		$other_timestamp = new fTimestamp($other_timestamp);
		return $this->timestamp < $other_timestamp->timestamp;
	}
	
	
	/**
	 * If this timestamp is less than or equal to the timestamp passed
	 * 
	 * @param  fTimestamp|object|string|integer $other_timestamp  The timestamp to compare with, `NULL` is interpreted as today
	 * @return boolean  If this timestamp is less than or equal to the one passed
	 */
	public function lte($other_timestamp=NULL)
	{
		$other_timestamp = new fTimestamp($other_timestamp);
		return $this->timestamp <= $other_timestamp->timestamp;
	}
	
	
	/**
	 * Modifies the current timestamp, creating a new fTimestamp object
	 * 
	 * The purpose of this method is to allow for easy creation of a timestamp
	 * based on this timestamp. Below are some examples of formats to
	 * modify the current timestamp:
	 * 
	 *  - `'Y-m-01 H:i:s'` to change the date of the timestamp to the first of the month:
	 *  - `'Y-m-t H:i:s'` to change the date of the timestamp to the last of the month:
	 *  - `'Y-m-d 17:i:s'` to set the hour of the timestamp to 5 PM:
	 * 
	 * @param  string $format    The current timestamp will be formatted with this string, and the output used to create a new object. The format should **not** include the timezone (character `e`).
	 * @param  string $timezone  The timezone for the new object if different from the current timezone
	 * @return fTimestamp  The new timestamp
	 */
	public function modify($format, $timezone=NULL)
	{
		$timezone = ($timezone !== NULL) ? $timezone : $this->timezone;
		return new fTimestamp($this->format($format), $timezone);
	}
}



/**
 * Copyright (c) 2008-2011 Will Bond <will@flourishlib.com>
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