<?php

// Load in the old
$oldXML = simplexml_load_file ( "./wurfl.xml" );

// Ready the new
$newXml = new SimpleXMLElement('<context></context>');
$devices = $newXml->addChild( "devices" ); // Root element

// So itterate through the devices
foreach ( $oldXML->devices->device as $item )
{
	// We only care about items we can match back.
	if ( !empty( $item['user_agent'] ) && strpos( $item['user_agent'], 'DO_NOT_MATCH' ) === false )
	{	
		// Now we'll xpath for the data we want
		$yRes = $item->xpath('group[@id="display"]/capability[@name="resolution_height"]');
		$xRes = $item->xpath('group[@id="display"]/capability[@name="resolution_width"]');
		
		// Check to make sure there's data worthy of moving on
		if ( empty( $xRes ) || empty( $yRes ) ) { continue; }
		
		$device = $devices->addChild( "device" ); // well we're already in a valid device, create parrent node
		$device->addAttribute("user_agent", $item['user_agent']);
		$device->addAttribute("id", $item['id']);
		//$name = $device->addChild("name");
		//$name = $item->xpath('group[@id="product_info"]/capability[@name="mobile_browser"]');
		
		while ( list( , $node) = each($xRes) )
		{
			$device->addAttribute("x", $node['value']);
		}
		
		while ( list( , $node) = each($yRes) )
		{
			$device->addAttribute("y", $node['value']);
		}
		
	}
}
//$newXml->asXML( "./mod_list.xml" );
$dom = dom_import_simplexml($newXml)->ownerDocument;
$dom->formatOutput = true;
$fp = fopen( './mod_list.xml', 'w+' );
fwrite( $fp, $dom->saveXML() );

?>