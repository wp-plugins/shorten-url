<?php
/*
Load scripts for sedlex plugins
	adapted from tre load-scripts.php file in wordpress

*/ 

error_reporting(0);

define( 'WP_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/../' );

function get_file($path) {
	if ( function_exists('realpath') )
		$path = realpath($path);
	if ( ! $path || ! @is_file($path) )
		return '';
	return @file_get_contents($path);
}

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $_GET['load'] );
$load = explode(',', $load);

if ( empty($load) )
	exit;

$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );

$expires_offset = 31536000;
$out = '';

foreach( $load as $handle ) {
	$path = WP_PLUGIN_DIR . str_replace('__',"/",$handle) . ".js";
	

	if (is_file($path)) {
		$out .=  "\n/*====================================================*/\n";
		$out .=  "/* FICHIER ".str_replace('__',"/",$handle) . ".js"."*/\n";
		$out .=  "/*====================================================*/\n";
		$out .= get_file($path) . "\n";
	} else {
		$md5 = preg_replace( '/[^a-z0-9]+/i', '', $handle);
		$out .=  "\n/*====================================================*/\n";
		$out .=  "/* INLINE ".$md5. ".css"."*/\n";
		$out .=  "/*====================================================*/\n";

		$path = WP_PLUGIN_DIR ."../sedlex/inline_scripts/". $md5 . '.js' ; 
		if (is_file($path)) {
			$out .=  get_file($path) . "\n";
		}
	}
}

header('Content-Type: application/x-javascript; charset=UTF-8');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");

if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) {
	header('Vary: Accept-Encoding'); // Handle proxies
	if ( false !== strpos( strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
		header('Content-Encoding: deflate');
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== strpos( strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'gzip') && function_exists('gzencode') ) {
		header('Content-Encoding: gzip');
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;


?>