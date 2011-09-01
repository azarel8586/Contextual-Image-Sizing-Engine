<?php
/**
 *	@author Stephen Hiller (stephen.webdev -[at]- gmail -[dot]- com )
 *	MIT license: http://www.opensource.org/licenses/mit-license.php
 *	@copyright Copyright (c) 2011, Stephen Hiller
 *	@package CSE
 *
 *	REQUIRES PHP IMAGICK EXTENSION
 *
 *	For useage examples go to:
 *
 *	The contextual sizing engine is an application for those of us who are too lazy to
 *	size their own images.  Mobile browsers are often attached to devices that have
 *	oppressive limitations such as bandwidth or the amount of data that can be downloaded.
 *	Too often designers and programers alike change the presentation of the site without
 *	changing the content to match the new dimensions.  The result is often a score of
 *	images that are more at home in a 1000px container than on a 300px screen. That exta
 *	data (which is just thrown away for the most part) only serves the burden an already
 *	slow connection and lighten a wallet that is already victim to corporate America.
 *	
 *	This file serves up those tastey images that you so disire.  As the core controller
 *	it parses the URL and manages the coarsest of functionality.  Since this is designed
 *	to be a standalone system, there will be no implementation of an autoloader. Also
 *	preferences and paths will be stored in a deffinition file that users can change when
 *	needed.
*/

// First, load in the Global Definitions
require_once( "config.ini" );

// Includes :-P
include ( APP_DIR . "Context/context_engine.php" );

// Now gather and sanitize our inputs
$file = addslashes( urldecode( $_GET['f'] ) ); // This is the only REQUIRED input

$enabled = ( isset($_GET['enable']) ? addslashes( urldecode( $_GET['enable'] ) ) : 1 ); // should I parse the file into a new size or transmit the orginal

$maxSize = ( isset($_GET['t']) ? addslashes( urldecode( $_GET['t'] ) ) : null ); // {top} Max size to allow regardless of the context
$minSize = ( isset($_GET['b']) ? addslashes( urldecode( $_GET['b'] ) ) : null ); // {bottom} Min size to allow
$scaleSize = ( isset($_GET['sc']) ? addslashes( urldecode( $_GET['sc'] ) ) : null ); // {scale} dec percent larger/smaller than the determined context

$xRes = ( isset($_GET['x']) ? addslashes( urldecode( $_GET['x'] ) ) : null ); // x resolution of the screen
$yRes = ( isset($_GET['y']) ? addslashes( urldecode( $_GET['y'] ) ) : null ); // y resolution of the screen
$overrideCntx = ( isset($_GET['o']) ? addslashes( urldecode( $_GET['o'] ) ) : null ); // Override context (IP or UserAgent)

// First step is to build our contextual limits from the UA / IP
$ContextFactory = Context_Factory( $xRes, $yRes, $scaleSize, $maxSize, $minSize, $overrideCntx );  // returns an object that describes the bounds of imagery
$Display = $ContextFactory->create();

// Now that Display model should be passed into the Image Resizing engine

?>