<?php 
/*
Plugin Name:     Ax-sidebar
Plugin URI:      http://eagerfish.eu/wordpress-plugin-ax-sidebar/
Version:         1.31
Description:     With this plugin you can add extra HTML or just plain text when posting a new page or post. That content will be displayed in sidebar widget. It is tested from WP 2.6 to 3.0.1.
Author:          <a href="http://eagerfish.eu/about/">Janar Jürisson</a>

**************************************************************************

Credits:
* Janar Jürisson

I did it :) 
It's my first WP plugin so dont hit me hard for it :) instead give me notes and guidelines. 

*/

class AxSidebar {
	
	var $dbver = '1.0';
	var $codever = '1.31';
	var $default_options = array(
				'show_only_on_single_page' => 0,
				'show_without_title' => 0,
				'show_without_ul_li' => 0,
			);
	var $table_name = '';
	
	/* add plugin to wordpress and initalize it */
	function AxSidebar() {
		global $wpdb;
		
		$this->table_name = $wpdb->prefix.'ax_sidebar';
		add_action('init', array(&$this, 'initAxSidebar'), 11 );		
		add_action('edit_page_form', array(&$this, 'axSideBarPageEditShow'));
		add_action('edit_form_advanced', array(&$this, 'axSideBarPageEditShow'));
		add_action('save_post', array(&$this, 'axSidebarSave'));
	}
	

	/* saves data sent from form */
	function axSidebarSave($postId) {	
		global $wpdb;
		
		if(isset($_POST['ax_sidebar']) && is_array($_POST['ax_sidebar'])){
			$array = $_POST['ax_sidebar'];
			
			/* there where too many escaping char's.. so this "hack" was added */
			$array[0] = stripslashes($array[0]);
			$array[1] = stripslashes($array[1]);
			
			$title = $wpdb->escape($array[0]);
			$content = $wpdb->escape($array[1]);
			$table_name = $wpdb->escape($wpdb->prefix.'ax_sidebar');
			
			$wpdb->query("INSERT INTO $table_name SET title = '$title',`content` = '$content', `parent_id` = '$postId' ON DUPLICATE KEY UPDATE title = '$title',`content` = '$content' ");
		}
		
	}
	
	
	/* Function for adding the actual content into page/post editing  screen */
	function axSideBarPageEditShow(){
		global $wpdb;
		
		$id = (int)$_GET['post'];
		$table_name = $wpdb->escape($wpdb->prefix.'ax_sidebar');
		$content = '';
		$title = '';
		$extraClass = ' closed';
		
		if($id > 0) {
			$objects = (array) $wpdb->get_results("SELECT p.content, p.title FROM $table_name AS p WHERE p.parent_id = '$id'");
	
			if(!empty($objects)) {
				/* escape quot chars */
				$content = $this->escape_quot_chars($objects[0]->content);
				$title = $this->escape_quot_chars($objects[0]->title);
			}
			
			if($content != '' || $title != ''){
				$extraClass = '';
			}
		}
		
		?>
		<div id="postboxAxSidebar" class="postbox<?php echo $extraClass; ?>">
			<h3>Ax-sidebar content</h3>
			<div class="inside">
				<h2>Title</h2>
				<input type="text" size='40' name="ax_sidebar[0]" style="width: 90%;" value="<?php echo stripslashes($title); ?>" />
				<h2>Content</h2>
				<textarea rows='10' cols='40' name="ax_sidebar[1]" style="width: 90%;"><?php echo stripslashes($content); ?></textarea>
			</div>
		</div>		
		<?php
	}
	
	
	/* initalize plugin */
	function initAxSidebar(){		
		global $wpdb;
		
		$table_name = $wpdb->escape($wpdb->prefix.'ax_sidebar');
		
		/* If database table is not created, create it */
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = " 
				CREATE TABLE `" . $table_name . "` (
					`parent_id` INT NOT NULL COMMENT 'Post or page id', 
					`content` TEXT NOT NULL COMMENT 'The content', 
					`title` VARCHAR(255) NOT NULL COMMENT 'The widget title', 
					UNIQUE  (
						`parent_id`
					)
				)
			";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			
			add_option("axSidebar_db_version", $this->dbver);
			add_option("axSidebar_code_version", $this->codever);
		}
		
		/* add widget into widgets */
		if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
			return;
		else {
			register_sidebar_widget('Ax-sidebar', array(&$this, 'widget_axSidebar'));
			register_widget_control('Ax-sidebar', array(&$this, 'widget_axSidebarControl'));
		}
	}
	
	
	/* widget control code for admin */
	function widget_axSidebarControl() {
		
		/* default values for options */
		$options = $this->default_options;
		
		/* get saved options and merge */
		if($options_saved = get_option('ax_sidebar_widget_options')){
			$options = array_merge($options, $options_saved);
		}
		
		/* update new values */
		if(isset($_POST['ax_sidebar_save_values']) && $_POST['ax_sidebar_save_values']){
			$options['show_only_on_single_page'] = $_POST['ax_sidebar_show_only_on_single_page'] ? 1 : 0;
			$options['show_without_ul_li'] = $_POST['ax_sidebar_show_without_ul_li'] ? 1 : 0;
			$options['show_without_title'] = $_POST['ax_sidebar_show_without_title'] ? 1 : 0;
			update_option('ax_sidebar_widget_options', $options);
		}
		
		/* render  */
		?>
		<input type="checkbox" name="ax_sidebar_show_only_on_single_page" id="ax_sidebar_show_only_on_single_page" <?php echo $options['show_only_on_single_page'] == 1 ? 'checked="checked"' : ''; ?> value="1" />
		<label for="ax_sidebar_show_only_on_single_page">Show only on single post/page</a>
		<br />
		<input type="checkbox" name="ax_sidebar_show_without_title" id="ax_sidebar_show_without_title" <?php echo $options['show_without_title'] == 1 ? 'checked="checked"' : ''; ?> value="1" />
		<label for="ax_sidebar_show_without_title">Don't show title</a>
		<br />
		<input type="checkbox" name="ax_sidebar_show_without_ul_li" id="ax_sidebar_show_without_ul_li" <?php echo $options['show_without_ul_li'] == 1 ? 'checked="checked"' : ''; ?> value="1" />
		<label for="ax_sidebar_show_without_ul_li">Don't render contents as list item (ul,li tags)</a>
		<input type="hidden" id="ax_sidebar_save_values" name="ax_sidebar_save_values" value="1" />
		<?php
	}
	
	
	/* widget code */
	function widget_axSidebar($args) {
		global $wpdb;
		extract($args);
		$arrPostIds = array();
		$table_name = $wpdb->escape($wpdb->prefix.'ax_sidebar');
		
		/* get default options first */
		$options = $this->default_options;
		
		/* get saved options and merge */
		if($options_saved = get_option('ax_sidebar_widget_options')){
			$options = array_merge($options, $options_saved);
		}
		
		/* a hack to get current page post(s) ID(s) */
		if (have_posts()){		
			while (have_posts()) {
				ob_start();
				$tempPost = the_post();
				the_ID();
				$arrPostIds[] = (int)ob_get_contents();
				ob_end_clean();
			}
		}
		
		/* if forced to only when single post on page, and there is more than one, return */
		if(count($arrPostIds) > 1 && $options['show_only_on_single_page']){
			return false;
		}
		
		/* get all post ids on currently loaded page */
		$strPostIds = implode(', ', $arrPostIds);
		
		/* render all existing posts sidebar contents */
		if($strPostIds != ''){
			$objects = (array) $wpdb->get_results("SELECT p.content, p.title FROM " . $table_name. " AS p WHERE p.parent_id IN (" . $strPostIds . ") ORDER BY p.parent_id DESC ");
			
			foreach($objects as $k => $i){
				/* if there is some actual content lets display it */
				if(trim($i->title) != '' && trim($i->content) != '') {
					echo $before_widget;
					
					
					if(!$options['show_without_title']){
						echo $before_title . $i->title . $after_title;
					}
					
					if($options['show_without_ul_li']){
						echo $i->content;
					} else {
						echo '<ul><li class="cat-item cat-item-1">' . $i->content . '</li></ul>';
					}
					
					echo $after_widget;
				}
			}
		}
	}
	
	
	/**
	 * this function escapes quotes 
	 */
	function escape_quot_chars($data)
	{
		$data = str_replace('"', '&quot;', $data);
		$data = str_replace("'", '&#39;', $data);
		return $data;
	}
}


$axSidebar = new AxSidebar();

?>