<?php
/**
 *	@author Stephen Hiller (stephen.webdev -[at]- gmail -[dot]- com )
 *	MIT license: http://www.opensource.org/licenses/mit-license.php
 *	@copyright Copyright (c) 2011, Stephen Hiller
 *	@package CSE
 *	@subpackage Context
 *
 *	Simple class describes the shape of the display.
*/

class Context_Resize
{
	protected $_Display = null;
	protected $_Image = null;
	
	protected $_path;
	
	public function __construct ( $pDisplayObj, $pPath )
	{
		// First lets make sure our display description exists and supplies us with all of our getters
		if (	!is_object($pDisplayObj)
				|| empty($pDisplayObj)
				|| !is_callable(array($pDisplayObj, 'get_display_x'))
				|| !is_callable(array($pDisplayObj, 'get_display_y'))
				|| !is_callable(array($pDisplayObj, 'get_image_size'))
		) {
			throw new Exception( "Constructor expects a Display Model that supplies a callable get_display_x / get_display_y / get_image_size method(s) " );
		}
		
		// Also make sure we have an image path
		if ( empty($pPath) || !is_readable( ORIGINAL_IMG_DIR . $pPath) ) {
			throw new Exception( "Path specified is empty or the file does not exist." );
		}
		
		$this->set_display( $pDisplayObj );
		$this->set_path( $pPath );
	}
	
	////****////****////****////****
	//** PUBLIC METHODS
	////****////****////****////****
	
	/**
	 *	This will search for a cached version of the image first and if not found it will
	 *	create it.  The method returns the string to be served up after the file is saved
	 *	(if need be)
	 *	@access public
	 *	@throws Exception
	 *	@param null
	 *	@return string Image data
	 *	@todo Search for image in memcache
	*/
	public function fetch_image ()
	{
		if ( !$this->_fetch_file_cache() ) {
			if ( !$this->_fetch_original() ) {
				throw new Exception( "There was an error while retriving the image - $this->_path" );
			}
		}
		return $this->_Image;
	}
	
	/**
	 *	examines the file type and determines the content type header to return to the platter.
	 *	The return from this method is only the string for the content-type and can be used
	 *	outside the context of the header.
	 *	@access public
	 *	@param null
	 *	@return string The content type declaration
	*/
	public function get_content_header ()
	{
		return "image/" . $this->_Image->getFormat();
	}
	
	////****////****////****////****
	//** PROTECTED METHODS
	////****////****////****////****
	
	/**
	 *	See if the image exists
	*/
	protected function _fetch_file_cache ()
	{
		list( $name, $ext ) = explode( '.', $this->_path );
		$cachePath = IMAGE_CACHE_DIR . $name . $this->_Display->get_image_size() . "." . $ext;
		if ( file_exists( $cachePath ) )
		{
			$this->_Image = new Imagick( $cachePath );
			return true;
		}
		return false;
	}
	
	/**
	 *	See if there's the orginal image
	*/
	protected function _fetch_original ()
	{
		$origPath = ORIGINAL_IMG_DIR . $this->_path;
		if ( file_exists( $origPath ) ) {
			$this->_Image = new Imagick( $origPath );
			$this->_size_image();
		
			list( $name, $ext ) = explode( '.', $this->_path );
			$cachePath = IMAGE_CACHE_DIR . $name . $this->_Display->get_image_size() . "." . $ext;
			
			$this->_Image->writeImage($cachePath);
			return true;
		}
		return false;
	}
	
	/**
	 *	Size the image on down
	*/
	protected function _size_image ()
	{
		if ( empty($this->_Image) ) { throw new Exception(); }
		$x = $y = 0;
		
		if ( $this->_Image->getImageWidth() < $this->_Display->get_image_size() && $this->_Image->getImageHeight() < $this->_Display->get_image_size() ) {
			
		} else {
			if ( $this->_Image->getImageWidth() > $this->_Image->getImageHeight() ) {
				$x = $this->_Display->get_image_size();
			} else {
				$y = $this->_Display->get_image_size();
			}
			$this->_Image->scaleImage ($x,$y);
			//$this->_Image->thumbnailImage($x, $y);	
		}
		
		$this->_Image->setImageResolution(72,72);
		$this->_Image->stripImage();
		
		if ( $this->_Image->getFormat == 'jpg' ) {
			$this->_Image->setImageCompression( Imagick::COMPRESSION_JPEG ); 
			$this->_Image->setImageCompressionQuality( (int)JPEG_COMPRESSION );
		}
	}
	
	////****////****////****////****
	//** SETTERS AND GETTERS
	////****////****////****////****
	public function set_display ( $pDisplay ) { $this->_Display = ( empty($pDisplay) ? null : $pDisplay ); }
	public function get_display () { return $this->_Display; }
	
	public function set_path ( $pPath ) { $this->_path = ( empty($pPath) ? null : $pPath ); }
	public function get_path () { return $this->_path; }
}