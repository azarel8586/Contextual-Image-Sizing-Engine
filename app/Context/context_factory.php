<?php
/**
 *	@author Stephen Hiller (stephen.webdev -[at]- gmail -[dot]- com )
 *	MIT license: http://www.opensource.org/licenses/mit-license.php
 *	@copyright Copyright (c) 2011, Stephen Hiller
 *	@package CSE
 *	@subpackage Context
 *
 *	So we're starting with a search patern:
 *		Cached -> depth first into different caches
 *		XML -> xpath our way to a particular device
 *		generic -> some basic assertions about browsers
 *	Cached items are stored on the hash of the user agent string (since the cache stores for specific
 *	browsers first and foremost).  Else, all other contextual descriptions are generated on the fly.
 *	This class is paterned on a non-static factory and is only used to return a description of the display.
 *	
 *	@return object returns an object that describes the dimensions of the browser and img limitations
 *	@todo Impement with memcache
*/


class Context_Factory
{
	protected $_ac; // HTTP Accept
	protected $_ua; // User Agent String
	
	protected $_xRes; // Total avalaible width
	protected $_yRes; // Total height
	protected $_scale; // Scale amount from calculated file size
	protected $_max; // largest allowable dimension
	protected $_min; // smallest allowable dimension
	
	protected $_displayX; // Determined display of the object
	protected $_displayY; // Determined display of the object
	protected $_deviceName;
	
	// Private constructor so the factory must be used
	public function __construct ( $pX, $pY, $pScale, $pMax, $pMin, $pOverride = null )
	{
		$this->_ac = ( isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : 'NONE' );
		$this->_ua = ( !empty( $pOverride ) ? $pOverride : $_SERVER['HTTP_USER_AGENT'] );
		
		$this->_xRes = $pX;
		$this->_yRes = $pY;
		$this->_scale = $pScale;
		$this->_max = $pMax;
		$this->_min = $pMin;
	}
	
	////****////****////****////****
	//** PROTECTED METHODS
	////****////****////****////****
	
	/**
	 *	This checks to see if the device is an older WAP style.
	 *
	 *	@access protected
	 *	@throws Exception
	 *	@param null
	 *	@return void
	*/
	protected function _check_rules ()
	{
		// Acording to a little birdy this is suposed to be faster than using a if statement
		switch(true)
		{
			case ( preg_match('/ipad/i', $this->_ua) ):
				$this->_displayX = 1024;
				$this->_displayY = 768;
				$this->_deviceName = "Apple iPad [DEFAULT]";
				break;
		
			case ( preg_match('/ipod/i', $this->_ua) || preg_match('/iphone/i', $this->_ua) ):
				$this->_displayX = 480;
				$this->_displayY = 320;
				$this->_deviceName = "iPhone / iPod [DEFAULT]";
				break;
		
			case ( preg_match('/android/i', $this->_ua) || preg_match('/opera mini/i', $this->_ua) ):
				$this->_displayX = 470;
				$this->_displayY = 320;
				$this->_deviceName = "Android / OperaMini [DEFAULT]";
				break;
		
			case ( preg_match('/blackberry/i', $this->_ua) ):
				$this->_displayX = 480;
				$this->_displayY = 360;
				$this->_deviceName = "Blackberry [DEFAULT]";
				break;
		
			case ( preg_match('/(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)/i', $this->_ua) ):
				$this->_displayX = 480;
				$this->_displayY = 360;
				$this->_deviceName = "Palm OS [DEFAULT]";
				break;
		
			case ( preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/i', $this->_ua) ):
				$this->_displayX = 480;
				$this->_displayY = 640;
				$this->_deviceName = "Palm OS [DEFAULT]";
				break;
		
			case (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320|vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i', $this->_ua)):
				$this->_displayX = 176;
				$this->_displayY = 220;
				$this->_deviceName = "Other Mobile Browser [DEFAULT]";
				break;
		
			case ( (strpos($this->_ac,'text/vnd.wap.wml')>0) || (strpos($this->_ac,'application/vnd.wap.xhtml+xml')>0) || strpos($this->_ua, 'wap1.') || strpos( $this->_ua, 'wap2.') || isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE']) ):
				$this->_displayX = 128;
				$this->_displayY = 128;
				$this->_deviceName = "WAP Device [DEFAULT]";
				break;
			
			default:
				throw new Exception ( "No applicable rule was located." );
				break;
		}
	}
	
	/**
	 *	Find the serialized display description
	 *	@access protected
	 *	@throws Exception
	 *	@param null
	 *	@return object
	 *	@todo Add in memcache
	*/
	protected function _fetch_file_cache ()
	{
		$porX = ( empty($this->_xRes) ? $this->_displayX : $this->_xRes );
		$porY = ( empty($this->_yRes) ? $this->_displayY : $this->_yRes );
		$size = ( $porX > $porY ? $porX : $porY );
		if ( !empty($this->_scale) ) {
			$size = $size*$this->_scale;
		}
		
		$key = md5( $this->_ua.$size );
		$filename = DISPLAY_CACHE_DIR . $key . ".dc";
		if ( !file_exists( $filename ) )
		{
			throw new Exception ( "No file with the cache key - $key - exists" );
		}
		$fp = fopen( $filename, 'r' );
		// the serialization is small so we should be able to read it in as a whole
		$Display = unserialize( fread($fp, filesize($filename)) );
		fclose($fp);
		return $Display;
	}
	
	/**
	 *	Looks for the user agent string in the modified WURFL database we created.  The
	 *	item is XML so we'll xpath the node, pull the attribues we need and pass them
	 *	to me for keeping.
	 *	@access protected
	 *	@param null
	 *	@return void
	 *	@todo Add in memcache
	*/
	protected function _lookup_user_agent ()
	{
		$xml = simplexml_load_file ( XML_LOOKUP );
		$item = $xml->xpath( '/context/devices/device[@user_agent="' . $this->_ua .'"]' );
		
		// Need to test both outcomes since different versions of XML Lib behave differently
		if ( empty($item) || $item === false ) { throw new Exception('Search returned 0 results.'); }
		
		while( list( , $node) = each($item) ) {
			$this->_displayX = (int)$node['x'];
			$this->_displayY = (int)$node['y'];
			$this->_deviceName = (string)$node['id'];
		}
	}
	
	/**
	 *	Simple reusable method that performs the user override / specifications on
	 *	the returned data to return a display object
	 *	@access protected
	 *	@param null
	 *	@return object
	 *	@todo Add in memcache
	*/
	protected function _populate_display ()
	{
		if ( include_once APP_DIR . 'Context/context_display.php' ) {
			$porX = ( empty($this->_xRes) ? $this->_displayX : $this->_xRes );
			$porY = ( empty($this->_yRes) ? $this->_displayY : $this->_yRes );
            
			$size = ( $porX >= $porY ? $porX : $porY );
			if ( !empty($this->_scale) ) {
				$size = $size*$this->_scale;
			}
			
			if ( !empty($this->_min) && $size < $this->_min ) { $size = $this->_min; }
			if ( !empty($this->_max) && $size > $this->_max ) { $size = $this->_max; }
			
            $Display = new Context_Display( $porX, $porY, $size );
			
			// Save it to the cache first
			$this->cache_display_data( md5($this->_ua.$size).'.dc', $Display );
			
			return $Display;
        }
	}
	
	////****////****////****////****
	//** PUBLIC METHODS
	////****////****////****////****
	
	/**
	 *	Creates the factory to branch out and make an object that describes the device.
	 *	The process at this point is very regimented and without memcache is very
	 *	disk intesive.
	*/
	public function create ()
	{
		// Step one - LOOK FOR SERIALIZED DESCRIPTION
		try {
			return $this->_fetch_file_cache();
		} catch ( Exception $e ) { /* move along */ }
		
		// Step two - LOOK UP FROM XML DB
		try {
			$this->_lookup_user_agent();
			// At this point we have our display information so now stub and return the object
			return $this->_populate_display();
		} catch ( Exception $e ) { /* move along */ }
		
		// Step three - APPLY SOME BASIC RULES TO WHAT WE KNOW ABOUT THE USER
		try {
			$this->_check_rules();
			return $this->_populate_display();
		} catch ( Exception $e ) { /* move along */ } 
		
		// Step four - PROVIDE A DISPLAY THAT LEAVES THE IMAGES AS IS
		return new Context_Display( 9001,9001,9001 ); // This should short circuit most images for the moment being
	}
	
	/**
	 *	Serializes and caches the file
	*/
	public function cache_display_data ( $pName, $pObj )
	{
		//$filename = DISPLAY_CACHE_DIR . $key . ".fc";
		if ( is_writable( DISPLAY_CACHE_DIR ) ) {
			$fp = fopen( DISPLAY_CACHE_DIR . $pName, 'w+' );
			fwrite( $fp, serialize($pObj) );
			fclose($fp);
		}
	}
	
	////****////****////****////****
	//** SETTERS AND GETTERS
	////****////****////****////****
}