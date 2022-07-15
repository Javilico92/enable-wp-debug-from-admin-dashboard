<?php
function set_cookie__ewdfad($name, $val, $time_length = 86400, $path=false, $domain=false, $httponly=false){
	$site_urls = parse_url( (function_exists('home_url') ? home_url() : $_SERVER['SERVER_NAME']) );
	$real_domain = $site_urls["host"];
	$path = $path ? $path : '/';
	$domain = ($domain ? $domain : (substr($real_domain, 0, 4) == "www.")) ? substr($real_domain, 4) : $real_domain;
	setcookie ( $name , $val , time()+$time_length, $path = $path, $domain = $domain,  $only_on_secure_https = FALSE,  $httponly  );
}

function js_redirect__ewdfad($url=false, $echo=true){
	$str = '<script>document.body.style.opacity=0; window.location = "'. ( $url ?: $_SERVER['REQUEST_URI'] ) .'";</script>';
	if($echo) { exit($str); }  else { return $str; }
}
function php_redirect__ewdfad($url=false, $code=302){
	header("location: ". ( $url ?: $_SERVER['REQUEST_URI'] ), true, $code); exit;
}


function main_check__ewdfad()
{
	$status_is_allowed = false;
	
	// this should only work when not the change-action happens (  no need - page is refreshed after that is set)
	if( !isset($_GET['ewdfad_STATE']) )
	{
		// Check if file_exists. If so, then it means the owner has not turned off WP_DEBUG check yet, so we need to check
		$permission_file= __DIR__.'/_wp_debug_ip_permission.php';
		if(file_exists($permission_file))
		{
			// Get variables
			$opts= json_decode( str_replace('<?php //','', file_get_contents($permission_file) ), true);

			// Check if IP was set to 0 (means for all) or to specific IP
			if( $opts['ip']==$_SERVER['REMOTE_ADDR'] || ($opts['ip']=="all" && time() <= $opts['expires_at']) ) 
			{
				// Set the number
				$status_is_allowed=$opts['type'];

				/*
				// If still empty from last load (so [admin|wp]_footer was not executed due to a problem), then exit DEBUG_MODE
				if(file_get_contents(__DIR__.'/_pageload_success')==0){ 
					$GLOBALS['ewdfad_pageBreak_happened']	= true;
					$status_is_allowed = false;
				}
				else{
					file_put_contents(__DIR__.'/_pageload_success', 0);
				} 
				*/

				
			}
		}
	}
	return $status_is_allowed;
}


$vars = main_check__ewdfad();

//define variables to pass to wp-config
define("ewdfad_included_on",		$vars); 
define("ewdfad_WP_DEBUG",			($vars==1 || $vars==2 || $vars==3)); 
define("ewdfad_WP_DEBUG_DISPLAY",	($vars==2 || $vars==3) ); 
define("ewdfad_WP_DEBUG_LOG",		($vars==3) ); 
if ($vars==1 || $vars==2)
	@ini_set("display_errors", 1 );
?>