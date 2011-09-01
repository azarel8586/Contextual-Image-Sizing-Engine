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

class Context_Display
{
	public $displayX;
	public $displayY;
	
	public $imageSize = null;
	
	public function __construct ( $pX, $pY, $pSize )
	{
		if ( empty($pX) || empty($pY) || empty($pSize) ) { throw new Exception( "Both X and Y dimensions are required as a well as the image size." ); }
		
		$this->set_display_x( $pX );
		$this->set_display_y( $pY );
		$this->set_image_size( $pSize );
	}
	
	////****////****////****////****
	//** SETTERS AND GETTERS
	////****////****////****////****
	public function get_display_x () { return $this->displayX; }
	public function set_display_x ( $pX ) { $this->displayX = $pX; }
	
	public function get_display_y () { return $this->displayY; }
	public function set_display_y ( $pY ) { $this->displayY = $pY; }
	
	public function get_image_size () { return $this->imageSize; }
	public function set_image_size ( $pSize ) { $this->imageSize = $pSize; }
}