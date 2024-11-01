<?php
//error_reporting(E_ALL);
# /* 
#     Plugin Name: Plugin Propagator 
#     Plugin URI: http://www.widgilabs.com/
#     Description: Propagates other plugins into several WP installations. 
#     Author: WidgiLabs
#     Version: 0.1 
#     Author URI: http://www.widgilabs.com/ 
#     */  

global $old_active_plugins;
global $version_under_3;
$version_under_3 = false;

register_deactivation_hook( __FILE__, 'ppropagator_deactivate' );

function ppropagator_deactivate()
{
	delete_option('ppropagator_master');
	delete_option('ppropagator_marked_as_worker');
}

add_action('admin_notices', 'ppropagator_activation');

function ppropagator_admin_path() {
	return 'options-general.php'."?page=plugin-propagator";
}
function ppropagator_activation()
{ 
	if ((get_option( 'ppropagator_master' ) != '') || (get_option( 'ppropagator_marked_as_worker' ) != ''))
		return;
	
	echo "<div class='updated' style='background-color:#f66;'><p>" . sprintf(__('Propagator needs attention: please <a href="%s">configure</a> the master or mark as a worker.', 'stats'), ppropagator_admin_path()) . "</p></div>";

	$temp_active_plugins = (array)get_option('active_plugins');

	//sets a variable to store the previous value for the active plugins.
	update_option('temp_active_plugins', $temp_active_plugins);
		
	update_option('ppropagator_marked_as_worker', 'disabled');
}

//required for call to wp_get_current_user
require_once(ABSPATH. WPINC ."/pluggable.php");
require_once(ABSPATH. WPINC ."/capabilities.php");
////needed for deactivate_sidewide_plugin - mu.php is deprecated after v3.0
if(file_exists(ABSPATH . '/wp-admin/includes/mu.php'))
{
	require_once ( ABSPATH . '/wp-admin/includes/mu.php' );
	$version_under_3 = true;
}
//needed for activate/deactive_plugin
require_once ( ABSPATH . '/wp-admin/includes/plugin.php' );

if (function_exists('get_option')) {
	$LOCALHOST = substr(get_option("siteurl"), 7);
	define( 'ROOT', substr( get_option( 'ppropagator_master' ), 7 ) );
}

// Activates the admin interface
require_once( dirname( __FILE__ ) . '/admin.php');

/**
 * Given a source and a destination this function can compress any
 * file or directory recursively
 * @param $source
 * @param $destination
 */
function Zip($source, $destination)
{
    if (extension_loaded('zip') === true)
    {
        if (file_exists($source) === true)
        {
                $zip = new ZipArchive();

                if ($zip->open($destination, ZIPARCHIVE::CREATE) === true)
                {
                        $source = realpath($source);

                        if (is_dir($source) === true)
                        {
                                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

                                foreach ($files as $file)
                                {
                                        $file = realpath($file);

                                        if (is_dir($file) === true)
                                        {
                                                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                                        }

                                        else if (is_file($file) === true)
                                        {
                                                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                                        }
                                }
                        }

                        else if (is_file($source) === true)
                        {
                                $zip->addFromString(basename($source), file_get_contents($source));
                        }
                }

                return $zip->close();
        }
    }

    return false;
}

/**
 * Given a zip_file with the complete path this function
 * performs the unzip in the same directory as the name of the file.
 * For instance if zip_file is path/to/file.zip the content will be 
 * available in path/to/file
 * @param unknown_type $zip_file
 */
function unZip($zip_file)
{
	$zip = new ZipArchive();
	$zip->open($zip_file);
	
	$destination = 	substr($zip_file,0,strpos($zip_file,".zip"));

	$zip->extractTo($destination);
	
	$zip->close();
	
	unlink($zip_file);
}


/**
 * This function propagates the action of activate/deactivate a given plugin
 * or set of a plugins from one wp installation to another wordpress installations
 * @param unknown_type $new_value
 */
function propagate($new_value)
{
	global $LOCALHOST;
	
	if(strstr($LOCALHOST, ROOT) != false)
	{	
		//echo 'The current host is not the master<br/>';
		return $new_value;
	}
	
	if (get_option( 'ppropagator_marked_as_worker' ) == 'checked')
	{
		//echo 'marked_as_worker is checked<br/>';
		return $new_value;
	}
	
	$old_value = (array) get_option('temp_active_plugins');
		
	$n_new = count($new_value);
	$n_old = count($old_value);
	$diff_value = array();
	$plugin_activated = false;
	if($n_new > $n_old)
	{
		$diff_value = array_diff((array)$new_value,$old_value);
		$plugin_activated = true;
	}
	else if($n_new < $n_old)
	{
		$diff_value = array_diff($old_value,(array)$new_value);
	}
		
	// update the temporaty storage value
	update_option('temp_active_plugins', $new_value);
	
	$hosts_defs = get_option( 'ppropagator_sites' );

	foreach ($diff_value as $current_plugin)
	{
		foreach ( $hosts_defs as $host )
		{
			if(strstr($host['url'], ROOT) != false)
				continue;
			
			$curl_url = $host['url']."/index.php?username=".$host['user']."&password=".$host['pass'];
			
			//contains the dir name of the plugin activated/deactivate
			$plugin_file = plugin_basename(trim($current_plugin));
			$dirname = dirname($plugin_file);
			
			if($dirname == 'wp-propagator') {
				continue;
			}
			
			//plugins can be distributed as a single file or as a directory
			if($dirname == '.')
			{
				$plugin_dir = WP_PLUGIN_DIR."/".$plugin_file;		
				$plugin_name = 	substr($plugin_file,0,strpos($plugin_file,".php"));	
				$zip_file = WP_PLUGIN_DIR."/".$plugin_name.".zip";
				$plugin_file = $plugin_name.'/'.$plugin_file;  
				//this is needed to construct well the zip and the url
			}
			else		
			{
				$plugin_dir = WP_PLUGIN_DIR."/".$dirname;
				$zip_file = WP_PLUGIN_DIR."/".$dirname.".zip";
			}
					
			//creates the zip archive and if success proceed with the request
			if($plugin_activated && Zip($plugin_dir,$zip_file))
			{	
				//I am activating the current plugin
				//echo "I am activating the current plugin ".$plugin_file.' in '.$host['url']."<br/>";
				$ch = curl_init();
				$curl_url = $curl_url."&plugin_action=activated&plugin_mainfile=".$plugin_file;					
				
				curl_setopt($ch, CURLOPT_URL, $curl_url);
				//echo "curl url = ".$curl_url."<br/>";
				
				$data = array('testpost' => 'Foo', 'file' => '@'.$zip_file);
			
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			
				//return the transfer as a string
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
				// $output contains the output string
				$output = curl_exec($ch);
			
				curl_close($ch);
				unlink($zip_file);
				print $output;		
			}
			else
			{
				//I am deactivating the current plugin	
				$ch = curl_init();
				$curl_url = $curl_url."&plugin_action=deactivated&plugin_mainfile=".$plugin_file;
				
				curl_setopt($ch, CURLOPT_URL, $curl_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$output = curl_exec($ch);
				curl_close($ch);	
				print $output;
			}
		}//ends for each host
	}//ends for each plugin
	
	
	return $new_value;
}

/**
 * Given a request with a plugin activate/deactivate/delete action
 * executes that action on the required plugin
 */
function blitz_handlerequest(){
	
	global $LOCALHOST;
	
	if($LOCALHOST == ROOT) // If localhost is root don't update
	{
		return false;
	}
	if(isset($_REQUEST['plugin_action'])) 
	{
				
		$p_action = $_REQUEST['plugin_action'];
		$plugin_mainfile = $_REQUEST['plugin_mainfile'];
		
		if($p_action=='activated')
		{
			$target_path = WP_PLUGIN_DIR."/".basename( $_FILES['file']['name']); 

			if(!file_exists(WP_PLUGIN_DIR."/".$plugin_mainfile))	
			{
				$uploaded_file = $_FILES['file']['tmp_name'];
	
				if(move_uploaded_file($uploaded_file,$target_path))
				{
					//could copy the zip file into plugins dir properly.

					//unzip the plugin
					unZip($target_path);
				}
			}
			//activate the plugin
			//echo "I am activating ".$plugin_mainfile.'in '.get_option('siteurl')."<br/>";
			activate_plugin($plugin_mainfile);
			if ($_REQUEST['sitewide'] == 1)
				activate_sitewide($plugin_mainfile);
		}
		else 
		{
			$pluginsD = array();
			$pluginsD[] = $plugin_mainfile;
			//echo "I am deactivating ".$plugin_mainfile.'in '.get_option('siteurl')."<br/>";
			deactivate_plugins($plugin_mainfile);
			if(!$version_under_3)
				deactivate_sitewide($plugin_mainfile);
		}
		die;
	}
}

/**
 * mu.php does not support activate sitewide with plugin specification.
 * @param unknown_type $plugin
 */
function activate_sitewide($plugin) {
		
	/* Add the plugin to the list of sitewide active plugins */
	$active_sitewide_plugins = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
	
	/* Add the activated plugin to the list */
	$active_sitewide_plugins[ $plugin ] = time();

	/* Write the updated option to the DB */
	if ( !update_site_option( 'active_sitewide_plugins', $active_sitewide_plugins ) )
		return false;

	return true;
}

/*
* After v. 3.0 deactivate plugins does not go sitewide
*/
function deactivate_sitewide($plugin)
{
	/* retrieve active plugins */
	$active_sitewide_plugins = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
	
	/* unset the plugin to the list */
	unset($active_sitewide_plugins[ $plugin ]);

	/* Write the updated option to the DB */
	if ( !update_site_option( 'active_sitewide_plugins', $active_sitewide_plugins ) )
		return false;

	return true;
}


add_action('init', 'blitz_handlerequest', 12);
add_filter('option_active_plugins', 'propagate');
?>
