<?php
/*
 * @package Wordpress
 * @subpackage Plugin Propagator
 * 
 */

/*
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */


// Checks if it is accessed from Wordpress Admin
if ( ! function_exists( 'add_action' ) ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
	
}

	
load_plugin_textdomain( 'plugin-propagator', null, 'languages/' ); // Load plugin i18n

add_action( 'admin_init', 'pprogator_AdminInit' );
add_action( 'admin_menu', 'ppropagator_AddOptionsMenu' );
add_action( 'wp_ajax_propagator_add_new', 'ppropagator_PostBoxProcess' );
add_filter( 'plugin_action_links', 'ppropagator_PluginActionLinks', 10, 2 );


add_action('wp_ajax_my_special_action2', 'pprogator_update_slave_mode');

function pprogator_update_slave_mode() {
	global $wpdb; // this is how you get access to the database

	if( isset( $_POST['marked_as_worker'] ) ) { // If options were updated it saves them in DataBase
	
		$post = $_POST['marked_as_worker'];
	
		update_option( 'ppropagator_marked_as_worker', $post );

		echo $post;
	}
	
	die(); // this is required to return a proper result
}

add_action('wp_ajax_my_special_action3', 'pprogator_get_slave_mode');

function pprogator_get_slave_mode() 
{
	$val = get_option( 'ppropagator_marked_as_worker' );

	echo $val;
	
	die(); // this is required to return a proper result
}


/**
 * Register Plugin Propagator admin page
 */
function pprogator_AdminInit() {

	//register settings to DB
	register_setting( 'ppropagator-settings-group', 'ppropagator_sites' );
	register_setting( 'ppropagator-settings-group', 'ppropagator_master' );
	register_setting( 'ppropagator-settings-group', 'ppropagator_version' );
		
	if( isset($_GET['page']) AND $_GET['page'] == 'plugin-propagator' ) {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'ppropagator-admin', plugins_url( 'admin.js', __FILE__ ), array( 'jquery' ), '0.1' );
		wp_localize_script( 'ppropagator-admin', 'ppa', array(
			'alert_url' => __('You must specify a valid URL','plugin-propagator'),
			'alert_user' => __('You must specify a Username','plugin-propagator'),
			'alert_pass' => __('You must specify a Password','plugin-propagator'),
		) );
	}
	
	add_contextual_help( 'settings_page_plugin-propagator', __(
		"
		<h3>Adding sites to the Network</h3>
		<p>To use this plugin you first have to create new entries using the <i>Add new sites to the network</i> form.</p>
		<ul>
			<li><b>URL:</b> <span>The address to your site in format <code>http://www.example.com</code></span></li>
			<li><b>Username:</b> <span>Enter the Username you use to enter to the site above</span></li>
			<li><b>Password:</b> <span>The password for the username above</span></li>
		</ul>
		<p>After you have added or removed entries will need to click in <i>Save changes</i> to save the modifications made.</p>
		<h3>Choosing the master site</h3>
		<p>After you finish adding sites, click on the checkbox that exists in the line you want to be your master Web site.
		Click in <i>Save changes</i> to apply modifications.</p>
		"
	) ); 
	
}




/**
 * Creates a menu entry for the admin page
 */
function ppropagator_AddOptionsMenu() {
	
	add_options_page( __( 'Plugin Propagator', 'plugin-propagator' ), __( 'Plugin Propagator', 'plugin-propagator' ), 'manage_options', 'plugin-propagator', 'ppropagator_SettingsPage' );		
	
}





/**
 * Add Settings link to the Plugin inside plugin.php page
 */
function ppropagator_PluginActionLinks( $links, $file ) {
	
	if ( $file == plugin_basename( dirname(__FILE__).'/ppropagator.php' ) ) {
		$links[] = '<a href="options-general.php?page=plugin-propagator">'.__('Settings').'</a>';
	}

	return $links;
}





/**
 * Displays Plugin Admin Page, treat any POST to that page and updates options
 */
function ppropagator_SettingsPage() {
	
	if ( ! current_user_can( 'manage_options' ) ) { // If user is not admin, they can't handle the page
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'plugin-propagator' ) );
			
	}
	
	if( isset( $_POST['ppropagator_save'] ) ) { // If options were updated it saves them in DataBase
	
		$post = $_POST['ppropagator_sites'];
	
		if (isset( $_POST['ppropagator_master'] ) AND $post ) {
			$master = $_POST['ppropagator_master'];
			$post[$master]['master'] = 'master';
			update_option( 'ppropagator_master', $post[$master]['url'] );
			
		} elseif ( $post ){
			$post[0]['master'] = 'master';
			update_option( 'ppropagator_master', $post[0]['url'] );
			
		}
		
		update_option( 'ppropagator_sites', ($post) ? $post : '' );
				
	}


	$sites = get_option( 'ppropagator_sites' );
	
?>

<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2><?php _e( 'Plugin Propagator Settings', 'plugin-propagator' ); ?></h2>

<br/>
<input type="checkbox" id="mark-as-worker" name="mark-as-worker" class="mark-as-worker"></input> Mark as Worker

<div id="poststuff" style="margin-top:15px;">

<div class="postbox" style="width:200px; float:left;">

	<h3><?php _e( 'Add Sites to the Network', 'plugin-propagator' ); ?></h3>
	<div class="inside">
	
		<div class="customlinkdiv" id="customlinkdiv">
		
			<p><label class="howto" for="ppropagator-new-url">
				<span><?php _e( 'URL', 'plugin-propagator' ); ?></span>
				<br />
				<input maxlength="80" size="30" id="ppropagator-new-url" name="ppropagator-new-url" class="ppropagator-url" style="width:100%" type="text" value="http://" />
			</label></p>
			
			<p><label class="howto" for="ppropagator-new-user">
				<span><?php _e( 'Username', 'plugin-propagator' ); ?></span>
				<br />
				<input maxlength="80" size="30" id="ppropagator-new-user" name="ppropagator-new-user" class="ppropagator-user" style="width:100%" type="text" />
			</label></p>
				
			<p><label class="howto" for="ppropagator-new-pass">
				<span><?php _e( 'Password', 'plugin-propagator' ); ?></span>
				<br />
				<input maxlength="80" size="30" id="ppropagator-new-pass" name="ppropagator-new-pass" class="ppropagator-pass" style="width:100%" type="password" />
			</label></p>

		
			<p class="button-controls">
				<span class="add-to-menu">
					<img class="waiting alignleft" src="images/wpspin_light.gif" alt="" style="display:none;" />
					<input type="button" class="button-secondary addsite alignright" value="<?php _e( 'Add new Site', 'plugin-propagator' ); ?>" name="add-new-site"/>
					<div style="clear:both;"></div>
				</span>
			</p>
			
		</div>
	</div>
</div>
</div>


<form id="nav-menu-meta" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="nav-menu-meta" method="post" enctype="multipart/form-data">

<?php settings_fields( 'ppropagator-settings-group' ); ?>

<div id="sitetable" class="alignleft" style="margin-left:20px;">

<table class="widefat">
<thead>
    <tr>
        <th><?php _e('Master', 'plugin-propagator'); ?></th>
        <th width="200px"><?php _e('URL', 'plugin-propagator'); ?></th>
        <th width="150px"><?php _e('Username', 'plugin-propagator'); ?></th>
        <th width="125px"><?php _e('Password', 'plugin-propagator'); ?></th>
    </tr>
</thead>
<tfoot>
    <tr>
        <th><?php _e('Master', 'plugin-propagator'); ?></th>
        <th><?php _e('URL', 'plugin-propagator'); ?></th>
        <th><?php _e('Username', 'plugin-propagator'); ?></th>
        <th><?php _e('Password', 'plugin-propagator'); ?></th>
    </tr>
</tfoot>
<tbody>
   <?php if(is_array($sites)) { $i=0; while( list(,$site ) = @each( $sites ) ) { ppropagator_PostBoxProcess( $i, $site ); $i++; } } ?>
</tbody>
</table>

		
		<input type="hidden" id="propagator_count" name="propagator_count" value="<?php echo count($sites); ?>" />
		
		<p class="submit">
			<input type="submit" class="button-primary alignright" id="ppropagator_save" name="ppropagator_save" value="<?php _e( 'Save Changes', 'plugin-propagator' ) ?>" />	
		</p>

</div>

</form>

</div>


<?php
}

/**
 * Outputs a any given row of site. Used for creating new rows after add
 * new site via AJAX
 * @param $id
 * @param $site
 */
function ppropagator_PostBoxProcess( $id = false, $site = false ) {

	$id = isset( $_POST['id'] ) ? $_POST['id'] : $id;
	
	$url = isset( $_POST['url'] ) ? $_POST['url'] : $site['url'];
	$user = isset( $_POST['user'] ) ? $_POST['user'] : $site['user'];
	$pass = isset( $_POST['pass'] ) ? $_POST['pass'] : $site['pass'];
	
	/*if ( empty($url) OR $url = 'http://' OR empty($user) OR empty($pass) ) {
		die();
	}*/
	
	$checked = (isset( $site['master'] ) AND $site['master']) ? 'checked="checked" ' : '';
?>

	<tr>
		<td>
			<center style="margin-top:2px;">
			<input name="ppropagator_master" value="<?php echo $id; ?>" <?php echo $checked; ?>class="ppropagator-master" type="radio" />
			</center>
		</td>
		<td>
			<span class="url"><?php echo $url;?></span>
			<input class="url" name="<?php echo "ppropagator_sites[$id][url]"; ?>" value="<?php echo $url;?>" type="hidden" />
			<div class="row-actions">
			<!--<span class="edit"><a class="edit" href="<?php echo $_SERVER['REQUEST_URI'].'&site='.$id; ?>" title="Edit this item">Edit</a> | </span>-->
			<span class="trash"><a class="submitdelete remove" title="Remove this item" href="#">Remove</a></span>
			</div>
		</td>
		<td>
			<span class="user"><?php echo $user; ?></span>
			<input class="user" name="<?php echo "ppropagator_sites[$id][user]"; ?>" value="<?php echo $user; ?>" type="hidden" />
		</td>
		<td>
			<span class="pass" style="font-style:italic;color:#ccc;"><?php _e( sprintf('Password lenght: %d',strlen($pass)) ); ?></span>
			<input class="pass" name="<?php echo "ppropagator_sites[$id][pass]"; ?>" value="<?php echo $pass; ?>" type="hidden" />
		</td>
	</tr>
	
<?php

	if ( isset( $_POST['id'] ) ) {
		die();
	}
}

?>
