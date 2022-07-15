<?php
/*
 * Plugin Name:		Debug Bar - Enable WP_DEBUG from admin dashboard
 * Description:		Easily enable/disable WP_DEBUG with one single click. Also, plugin is clever - in case of errors, it automatically exits the WP_DEBUG mode to avoid failure (in case of problems, just delete this plugin).
 * Text Domain:		enable-wp-debug-from-admin-dashboard
 * Domain Path:		/languages
 * Version:		1.86
 * Plugin URI:		https://github.com/Javilico92/enable-wp-debug-from-admin-dashboard
 * Contributors: 	puvoxsoftware,ttodua
 * Author:		Puvox.software & Javilico92
 * Author URI:		https://github.com/Javilico92/
 * License:		GPL-3.0
 * License URI:		https://www.gnu.org/licenses/gpl-3.0.html
 
 * @copyright:		Puvox.software & Javilico92
*/


namespace EnableWpDebugFromDashboard
{
  if (!defined('ABSPATH')) exit;
  require_once( __DIR__."/library_default_puvox.php" );


  class PluginClass extends \Puvox\default_plugin
  {

	public function declare_settings()
	{
		$this->initial_static_options	=
		[
			'has_pro_version'		=>0, 
			'show_opts'				=>false, 
			'show_rating_message'	=>true, 
			'show_donation_popup'	=>true, 
			'display_tabs'			=>[],
			'required_role'			=>'install_plugins',
			'default_managed'		=>'network',			// network | singlesite
		];

		$this->initial_user_options		= 
		[
		]; 	
	}
	
	
	public function __construct_my()
	{ 
		add_action('admin_init',			[$this, 'first_time_setups'],	22);
		//add button in admin bar
		add_action('admin_bar_menu',		[$this, 'my_admin_bar'], 		5 ); 

		//add_action('admin_enqueue_scripts',	array($this, 'my_admin_enqueue_scripts'), 	22);
		add_action('wp_head',				[$this, 'my_head'],	22);
		add_action('admin_head',			[$this, 'my_head'],	22);
		add_action('init',					[$this, 'reload_page_after_click'], 22);
		//it's better to use footer instead of `shutdown` hook, because `shutdown` might fire any time, even when WP is not outputed yet, so, in that case,we wont be able to determine if WP loads successfuly. So, WP_footer is better, because it will prove that page-loaded well enough.
		add_action('admin_footer',			[$this, 'footer'], 990);
		add_action('wp_footer',				[$this, 'footer'], 990);
		//add_action('shutdown',			[$this, 'my_shutdown'), 1);
		
		$this->wp_congif_addon_php = __DIR__.'/_wp_config_addon.php';
		$this->permission_file= __DIR__.'/_wp_debug_ip_permission.php'; 
		$this->phrase_start	= '///////// WP_DEBUG_FROM_DASHBOARD_PLUGIN___START (If you need to remove this, at first deactivate plugin, and then fully remove this block, not partially!)';	
		$this->phrase_end	= '///////// WP_DEBUG_FROM_DASHBOARD_PLUGIN___END';
	}

	// ============================================================================================================== //
	// ============================================================================================================== //
	
	public function deactivation_funcs(){      
		unlink($this->permission_file);
	}
	
	public function first_time_setups()
	{
		// ========= insert code in wp-config  =========// 
		$wp_config=ABSPATH.'wp-config.php';
		
		//if not yet included, let's include
		if(!defined("ewdfad_included"))
		{
			$wp_config_content=file_get_contents($wp_config);
			if( strpos($wp_config_content, $this->phrase_start) === false )
			{
				$inserting_code_block= 
				"\r\n".
				"\r\n".$this->phrase_start.
				"\r\n".'if(file_exists($a=__DIR__."/'. 
					str_replace( $this->helpers->replace_slashes(ABSPATH), '', $this->helpers->replace_slashes( $this->wp_congif_addon_php ))
				.'") && include_once($a)){  '.
				"\r\n".'	define("WP_DEBUG", ewdfad_WP_DEBUG);'.
				"\r\n".'	define("WP_DEBUG_DISPLAY", ewdfad_WP_DEBUG_DISPLAY);'.
				"\r\n".'	define("WP_DEBUG_LOG", ewdfad_WP_DEBUG_LOG);'.
				"\r\n".'}'.
				"\r\n".$this->phrase_end.
				"\r\n".
				"\r\n".
				"\r\n";

				$pattern= function($which){ return '/\bdefine\b(|\W+)\([\W]+'.$which.'(.*?)\;/i'; };
				
				$new_content= $wp_config_content;
				$new_content= preg_replace($pattern('WP_DEBUG'), 		'//$0', $new_content);
				$new_content= preg_replace($pattern('WP_DEBUG_LOG'), 	'//$0', $new_content); 
				$new_content= preg_replace($pattern('WP_DEBUG_DISPLAY'),'//$0', $new_content);
				$new_content= preg_replace('/\/\* That\'s all, stop editing/i', $inserting_code_block.'$0', $new_content);
				//copy($wp_config, ABSPATH.'wp-config_backup__Can_be_deleted_after_'.date('Y-m-d H-i-s').rand(1,99999).rand(1,99999).'.php');
				file_put_contents($wp_config, $new_content);
			}
		}
		
		if (!get_option("EWDFD_1_8_update_issue"))
		{
			update_option("EWDFD_1_8_update_issue", true);
			
			$wp_config_content=file_get_contents($wp_config);
			if( strpos($wp_config_content, 'WP_DEBUG_LOGS') !== false )
			{
				$new_content = str_replace( 'WP_DEBUG_LOGS', 'WP_DEBUG_LOG', $wp_config_content);
				file_put_contents($wp_config, $new_content);
			}
		}
		// ===============================================//
	}
	 
	// output functins
	public function my_admin_bar($wp_admin_bar)
	{
		if(!$this->helpers->is_administrator()) return;

		$wp_admin_bar->add_node(array(
			'id' => $this->plugin_slug.'_wp_debug',
			'parent' => 'top-secondary',
			'title' => 'WP_DEBUG is: '. (WP_DEBUG ? "ON":"OFF"),
			'href' => esc_url( add_query_arg( array('ewdfad_STATE'=>(WP_DEBUG ? 0 : 1), 'ewdfad_nonce'=>wp_create_nonce('ewdfd') ),	$_SERVER['REQUEST_URI']	) ),
			'meta' => array(
				'class' => 'button_ewdfad ewdfad_'.(WP_DEBUG ? "on":"off"),
				'onclick'=>(WP_DEBUG ? "" : 'redirect_to_ewdfad(this); return false;' )
			)
		));
	} 
 
	public function my_head()
	{
		if(!$this->helpers->is_administrator()) return;
		?>
		<style>
		body #wpadminbar .ab-top-menu li.button_ewdfad { float:left; }
		body #wpadminbar .ab-top-menu li.ewdfad_off a.ab-item,
		body #wpadminbar .ab-top-menu li.ewdfad_off a.ab-item:focus,
		body #wpadminbar .ab-top-menu li.ewdfad_off:hover a.ab-item{background:red; }
		body #wpadminbar .ab-top-menu li.ewdfad_on a.ab-item,
		body #wpadminbar .ab-top-menu li.ewdfad_on a.ab-item:focus,
		body #wpadminbar .ab-top-menu li.ewdfad_on:hover a.ab-item{background:green; }
		</style>	
		<script>
		function redirect_to_ewdfad(el){   
			 window.location = el.href + '&ewdfad_debug_type='+( prompt('<?php _e('1 [enables:  WP_DEBUG]\n2 [enables:  WP_DEBUG + WP_DEBUG_DISPLAY]\n3 [enables:  WP_DEBUG + WP_DEBUG_DISPLAY + WP_DEBUG_LOG]','enable-wp-debug-from-dashboard');?>', 2) ) + '&ewdfad_ip_type='+( confirm('<?php _e('Click "OK" if it should be only enabled for your IP (otherwise, clicking the "cancel" will turn on wp-debug for everyone for 24 hours)\n','enable-wp-debug-from-dashboard');?>') ? 1 : 0) ;  
		}
		</script>
		<?php 
	}

	public function reload_page_after_click()
	{
		if(!$this->helpers->is_administrator()) return;

		if(isset($_GET['ewdfad_STATE']))
		{
			if($_GET['ewdfad_STATE']==1 &&      isset($_GET['ewdfad_nonce']) && wp_verify_nonce($_GET['ewdfad_nonce'], 'ewdfd')){
				$this->write_permissions_file();
			}	
			if($_GET['ewdfad_STATE']==0 &&      file_exists($this->permission_file) )  {
				unlink($this->permission_file); 
			}
			$this->helpers->php_redirect( remove_query_arg( array('ewdfad_STATE','ewdfad_nonce','ewdfad_ip_type', 'ewdfad_debug_type'), $_SERVER['REQUEST_URI'])  ); 
		}
	}
	
	public function footer()
	{ 
		// temporarily disabled this function, because wp-ajax and all other areas doesn't execute this function...
		return;

		if (!$this->helpers->is_administrator()) return;
		
		file_put_contents(__DIR__.'/_pageload_success', 1);

		if (!empty($GLOBALS['ewdfad_pageBreak_happened'])) 
		{ ?>
			<script>
			alert('<?php _e('Seems on the previous page-load, there happend fatal php-error, causing to prevent normal page-load (maybe unexpected PHP error or \u0022exit\u0022 command happens, which could be caused due the fact that WP_DEBUG was enabled by this plugin). So, this plugin has turned off WP_DEBUG for your flexibility (to avoid you to manually enter FTP and disable WP_DEBUG there). If you will again see this message (but you think that page was not broken during load), then ensure (when DEBUG is enabled) if page-source is ending with </html> tag or PHP_SESSIONS are not blocked on your hosting/website.', 'enable-wp-debug-from-dashboard');?>');
			</script>
			<?php 
			@unlink($this->permission_file);
		}
	}



	public function write_permissions_file()
	{
		if(!$this->helpers->is_administrator()) return;
		
		//if passed all checking, then write permission file.
		file_put_contents($this->permission_file,  '<?php //'.json_encode(  
				array(
					'ip'		=> $_GET['ewdfad_ip_type']==0 ? "all" : $this->helpers->ip,   
					'type'		=> sanitize_key($_GET['ewdfad_debug_type']),   
					'expires_at'=> time()+ 24*60*60, //limit to 24 hours
					'path'		=> network_site_url("/")
				)
			)
		);
		
		//file_put_contents(__DIR__.'/_pageload_success', 1);
	}


  } // End Of Class

  $GLOBALS[__NAMESPACE__] = new PluginClass();

} // End Of NameSpace

?>