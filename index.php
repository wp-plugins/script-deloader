<?php
/**
 * Plugin Name: Script DeLoader
 * Description: For those poorly created wordpress themes, Manage which javascript loads on which page.
 * Version: 1.0
 * Author: Sajesh Bahing
 * License: Its free
 */
class Index{
	/*
	Consstructor of the class and loading all the actions
	*/
		
	function __construct(){		
		add_action('admin_menu', array($this, 'add_menu')); // call to function to add the options page
		add_action('wp_print_scripts', array($this, 'dequeuewer')); // call to function to dequeue the useless plugins
		add_action('admin_enqueue_scripts', array($this, 'add_scripts')); //call to function which displays Script Deloader user interface
		register_activation_hook(__FILE__, array($this, 'put_data'));
		register_deactivation_hook(__FILE__, array($this, 'exit_data'));
	}
	
	/*
	Adding a Option page
	*/
	function add_menu(){
		add_options_page( "Script", "Script", 'manage_options', "Script", array($this, 'ScriptPage') );
	}
	
	/*
	Script Deloader user interface
	
	1. A form for selecting the tempelates and plugins
	2. A table for displaying the relative information
	*/
	function ScriptPage(){
		global $wpdb;
		$red = admin_url('admin.php').'?page=Script';
		$templates = get_page_templates();
		
		$read_file = $wpdb->get_row("SELECT option_value FROM ".$wpdb->prefix."options WHERE option_name = 'script_deloader_scripts_db'");
		$read_file = json_decode($read_file->option_value);//reading the information
		
		if(count($read_file)<=0) // if file is empty assign the $read_file as an array
			$read_file = array();
		
		if(isset($_POST['submit'])){//processing the user's post request
			$put_array = array();
			foreach($_POST['template'] as $key => $value){
				if(array_key_exists($value, $read_file))
					unset($read_file->$value);
					
				$put_array[$value] = $_POST['scripts'];
			}
			$final = array_merge($put_array, (array)$read_file);
			
			$wpdb->update($wpdb->prefix.'options', 
							array('option_value'=> json_encode($final)),
							array('option_name' => 'script_deloader_scripts_db'));
			
			header("Location: $red");
		}else if(isset($_GET['delete'])){
			$deletor = urldecode($_GET['delete']);
			
			unset($read_file->$templates[$deletor]);
			$wpdb->update($wpdb->prefix.'options', 
							array('option_value'=> json_encode($read_file)),
							array('option_name' => 'script_deloader_scripts_db'));
			
			header("Location: $red");
		}
		?>
		<form method="POST" class="box1">
			<div class="box">
			<h1>All Templates</h1>
				<select name="template[]" multiple size="<?php echo count($templates); ?>">
					<?php foreach($templates as $handle => $page): ?>
					<option value="<?php echo $page; ?>"><?php echo $handle; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
			
			$scri = $wpdb->get_row("SELECT option_value FROM ".$wpdb->prefix."options WHERE option_name = 'script_deloader_scripts'");
			$scri = json_decode($scri->option_value); // Getting the list of scripts used in the site/blog
			?>
			<div class="box">
			<?php
			if(count($scri) <= 0){
				echo "<h3>Please Open you home page once for Getting all scripts</h3>";
			}
			?>
			<h1>All Scripts</h1>
				<select name="scripts[]" multiple size="<?php echo count($templates); ?>">
					<?php foreach($scri as $handle): ?>
					<option value="<?php echo $handle; ?>"><?php echo $handle; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			
			<div class="box">
				<input type="submit" name="submit" value="OK" class="button button-large button-primary" >
			</div>
		</form>
		
		<div class="box1">
			<table class="wp-list-table widefat">
			
			<thead>
				<th>Theme</th>
				<th>Scripts not to load</th>
				<th>Delete</th>
			</thead>
			<tbody>
				<?php foreach($read_file as $key => $value): ?>
					<tr>
						<td><?php echo array_search($key, $templates); ?></td>
						<td class="scripts"><?php if(count($value) <= 0) $value = array(); foreach($value as $scri){echo $scri." // ";} ?></td>
						<td><?php echo "<a href='$red&delete=".urlencode( array_search($key, $templates) )."' class='button'>Delete</a>"; ?></td>
					</tr>
				<?php endforeach; ?>
			<tbody>
			</table>
		</div>
		<?php
		
	}
	
	function dequeuewer(){ //function to dequeue the useless scripts
		global $wpdb;
		$page = get_page_template_slug( url_to_postid( $_SERVER['PHP_SELF'] ) );
		global $wp_scripts;
		$scripts =  $wp_scripts->queue ;
		
		if(is_front_page()){
			$wpdb->update($wpdb->prefix.'options', 
							array('option_value'=> json_encode($scripts)),
							array('option_name' => 'script_deloader_scripts'));
		}
			
		$read_file = $wpdb->get_row("SELECT option_value FROM ".$wpdb->prefix."options WHERE option_name = 'script_deloader_scripts_db'");
		$read_file = $read_file->option_value;
		$db = json_decode($read_file);//json to array
		if(count($db) <= 0)
			$db = array();
		foreach($db as $key => $value){
			if($key === $page){
				wp_dequeue_script($value);
			}
		}		
	}
	
	function add_scripts(){// loading the small css file for better user interface :P :D
		wp_enqueue_style('admin-script-css', plugins_url( 'style.css' , __FILE__ ));
	}
	
	function put_data(){ //function which runs at start up i.e when plugin is activated
		global $wpdb;
		$insert = $wpdb->insert($wpdb->prefix.'options', 
										array('option_name' => 'script_deloader_scripts',
												'option_value' => '{}',
												'autoload' => 'no'));
		$wpdb->insert($wpdb->prefix.'options', 
										array('option_name' => 'script_deloader_scripts_db',
												'option_value' => '{}',
												'autoload' => 'no'));
	}
	
	function exit_data(){ //function which runs at start up i.e when plugin is deactivated
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix.'options',
			array('option_name' => 'script_deloader_scripts')
		);
		
		$wpdb->delete(
			$wpdb->prefix.'options',
			array('option_name' => 'script_deloader_scripts_db')
		);
	}
}

new Index(); //Object of the class