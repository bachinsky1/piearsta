<?php

	/**
	 * Automatically generates robots.txt file
	 * 
	 */
	
	/* List of disallowed directories */
	$disallow = array('/admin', '/cron', '/system', '/tools');

	header("Content-Type: text/plain");

	echo "User-agent: *\r\n";
	foreach($disallow as $val) {
		echo "Disallow: $val\r\n";
	}
	echo "Sitemap: http://" . @$_SERVER['HTTP_HOST'] . "/sitemap.php";
	
?>