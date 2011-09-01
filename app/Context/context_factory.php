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
		$this->_ac = ( isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : null );
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
	 *	@static
	 *	@param null
	 *	@return boolean If the device is WAP style
	*/
	protected function _check_wap ()
	{
		
		if (
			strpos( $ac, 'application/vnd.wap.xhtml+xml' ) !== false ||
			strpos( $ua, 'wap1.' ) !== false ||
			strpos( $ua, 'wap2.' ) !== false
		){
			return true;
		} elseif ( strpos( $ua, 'wap1.') !== false || strpos( $ua, 'wap2.') !== false ) {
			return true;
		}
		return false;
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
            
			$size = ( $porX > $porY ? $porX : $porY );
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
		//try {
			$this->_lookup_user_agent();
			// At this point we have our display information so now stub and return the object
			return $this->_populate_display();
		//} catch ( Exception $e ) { /* move along */ }
		
		// Step three - APPLY SOME BASIC RULES TO WHAT WE KNOW ABOUT THE USER
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