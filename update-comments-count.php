<?php
/*
Plugin Name: Update Comments Count
Plugin URI: http://blogestudio.com
Description: An easy way to update post comments counters, even for large sites, using WordPress standar function.
Version: 1.0
Author: Pau Iglesias, Blogestudio
License: GPLv2 or later
*/

// Avoid script calls via plugin URL
if (!function_exists('add_action'))
	die;

// Quick context check
if (!is_admin())
	return;

// Avoid network admin
if (function_exists('is_network_admin') && is_network_admin())
	return;

/**
 * Update Comments Count plugin class
 *
 * @package WordPress
 * @subpackage Update Comments Count
 */

// Avoid declaration plugin class conflicts
if (!class_exists('BE_Update_Comments_Count')) {
	
	// Create object plugin
	add_action('init', array('BE_Update_Comments_Count', 'instance'));

	// Main class
	class BE_Update_Comments_Count {



		// Constants and properties
		// ---------------------------------------------------------------------------------------------------



		// Plugin menu
		private $plugin_url;
		private $parent_slug;
		private $parent_file;
		
		// Plugin title
		const title = 					'Update Comments Count';
		const title_menu = 				'Update Comments Count';
		
		// Admin page settings
		const slug = 					'update-comments-count';
		const action = 					'be-ucc-update-comments-count';
		const parent = 					'tools.php';
		
		// Role
		const capability = 				'edit_others_posts';
		
		// Translation
		const text_domain = 			'update-comments-count';
		
		// Key prefix
		const key = 					'be_ucc_';
		
		// Posts pack num
		const pack = 					50;
		const post_types_avoid = 		'attachment, nav_menu_item, revision';
		const post_status_allow =		'publish, future, draft, pending, private, trash';



		// Initialization
		// ---------------------------------------------------------------------------------------------------



		/**
		 * Creates a new object instance
		 */
		public static function instance() {
			return new BE_Update_Comments_Count;
		}



		/**
		 * Constructor
		 */
		private function __construct() {
			
			// Admin ajax
			if (self::is_admin_ajax()) {
			
				// Check AJAX action links
				if (self::is_admin_ajax(self::action)) {
					
					// AJAX handler
					add_action('wp_ajax_'.self::action, array(&$this, 'ajax_comments_count_update'));
				}
			
			// Not AJAX
			} else {
			
				// Admin menu and enqueued files
				add_action('admin_menu', array(&$this, 'admin_menu'));
				add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
			}
		}



		/**
		 *  Load translation file
		 */
		private static function load_plugin_textdomain($lang_dir = 'languages') {
			
			// Check load
			static $loaded;
			if (isset($loaded))
				return;
			$loaded = true;
			
			// Check if this plugin is placed in wp-content/mu-plugins directory or subdirectory
			if (('mu-plugins' == basename(dirname(__FILE__)) || 'mu-plugins' == basename(dirname(dirname(__FILE__)))) && function_exists('load_muplugin_textdomain')) {
				load_muplugin_textdomain(self::text_domain, ('mu-plugins' == basename(dirname(__FILE__))? '' : basename(dirname(__FILE__)).'/').$lang_dir);
			
			// Usual wp-content/plugins directory location
			} else {
				load_plugin_textdomain(self::text_domain, false, basename(dirname(__FILE__)).'/'.$lang_dir);
			}
		}



		// Admin Page
		// ---------------------------------------------------------------------------------------------------
		
		
		
		/**
		 * Enqueue both js and css files
		 */
		public function admin_enqueue_scripts() {
			global $pagenow;
			$this->parent_file = apply_filters(self::key.'parent_file', self::parent, self::slug, self::capability);
			if ($pagenow == $this->parent_file && (!empty($_GET['page']) && $_GET['page'] == self::slug))
				wp_enqueue_script(self::slug, plugins_url('update-comments-count.js', __FILE__), array('jquery'), false);
		}



		/**
		 * Admin menu hook
		 */
		public function admin_menu() {
			$this->parent_slug = apply_filters(self::key.'parent_menu', self::parent, self::slug, self::capability);
			$this->plugin_url = apply_filters(self::key.'plugin_url', admin_url(self::parent.'?page='.self::slug), $this->parent_slug);
			add_submenu_page($this->parent_slug, apply_filters(self::key.'title', self::title), apply_filters(self::key.'title_menu', self::title_menu), self::capability, self::slug, array(&$this, 'admin_page'));
		}



		/**
		 * Admin page display
		 */
		public function admin_page() {
			
			// Check user capabilities
			if (!current_user_can(self::capability))
				wp_die(__('You do not have sufficient permissions to access this page.'));
			
			// Load translations
			self::load_plugin_textdomain();
			
			?><div class="wrap">
			
				<?php screen_icon('tools'); ?>
			
				<h2><?php echo apply_filters(self::key.'title', self::title); ?></h2>
				
				<div id="poststuff">
				
					<div class="postbox">
					
						<h3 class="hndle"><span><?php _e('Comments counter fix', self::text_domain); ?></span></h3>
						
						<div class="inside">
							
							<div id="postcustomstuff">
								
								<form id="be-ucc-input" method="post" action="<?php echo esc_attr(esc_url($this->plugin_url)); ?>">
									
									<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce(__FILE__)); ?>" id="be-ucc-nonce" />
									<input type="hidden" name="action" value="<?php echo esc_attr(self::action); ?>" id="be-ucc-action" />
									<input type="hidden" name="pack" value="<?php echo esc_attr(self::get_pack()); ?>" id="be-ucc-pack" />
									<input type="hidden" name="count" value="<?php echo esc_attr(self::get_expected_posts_count()); ?>" id="be-ucc-count" />
									
									<p><?php printf(__('The comments counter will be updated in <b>%d entries</b>, allowing the post types: %s', self::text_domain), self::get_expected_posts_count(), '<b>'.implode('</b>, <b>', self::get_allowed_post_types('names')).'</b>.'); ?></p>
									
									<p><?php printf(__('Working via AJAX, each call will process packs of <b>%d entries</b>, using the standar WordPress function <code>wp_update_comment_count_now</code>', self::text_domain), self::get_pack()); ?></p>
									
									<input type="submit" value="<?php _e('Start process', self::text_domain); ?>" class="button-primary" />
									
								</form>
								
								<div id="be-ucc-progress-wrapper" style="display: none; padding: 15px 15px 15px 10px;"><div id="be-ucc-progress"></div>
								
								<p style="display: none;">
									<span id="be-ucc-js-cancelled"><?php _e('Cancelled in %value% entries', self::text_domain); ?></span>
									<span id="be-ucc-js-continue"><?php _e('Continue', self::text_domain); ?></span>
									<span id="be-ucc-js-updating"><?php _e('Updating %value% entries', self::text_domain); ?></span>
									<span id="be-ucc-js-cancel"><?php _e('Cancel', self::text_domain); ?></span>
									<span id="be-ucc-js-completed"><?php _e('<b>Completed</b>. Updated <b>%value% entries</b>.', self::text_domain); ?></span>
									<span id="be-ucc-js-cancelling"><?php _e('Cancelling', self::text_domain); ?></span>
								</p>
								
							</div>
						
						</div>
					
					</div>
				
				</div>
				
			</div><?php
		}



		/**
		 * Handle ajax request
		 */
		public static function ajax_comments_count_update() {
			
			// Check submit
			if (self::check_ajax_submit($response, self::capability, __FILE__)) {
				
				// Check URLs
				if (!isset($_POST['index'])) {
					
					// Parse error
					$response['status'] = 'error';
					$response['reason'] = __('Missing index pack', self::text_domain);
					
				// Process data
				} else {
					
					// Retrieve pack, post types keys and post status
					$pack = self::get_pack();
					$post_types = self::get_allowed_post_types();
					$post_status = self::get_allowed_post_status();
					
					// Check pack
					if (empty($pack)) {
					
						// Parse error
						$response['status'] = 'error';
						$response['reason'] = __('No pack number defined', self::text_domain);
					
					// Check post types
					} elseif (empty($post_types) || !is_array($post_types)) {
						
						// Parse error
						$response['status'] = 'error';
						$response['reason'] = __('No allowed post types values', self::text_domain);
						
					// Check post status
					} elseif (empty($post_status) || !is_array($post_status)) {
						
						// Parse error
						$response['status'] = 'error';
						$response['reason'] = __('No allowed post status values', self::text_domain);
						
					} else {
					
						// Globals
						global $wpdb;
						
						// Input data
						$index = (int) $_POST['index'];
						
						// Get pack of posts
						$posts = $wpdb->get_results('SELECT ID FROM '.esc_sql($wpdb->posts).' WHERE post_type IN ("'.implode('","', array_map('esc_sql', $post_types)).'") AND post_status IN("'.implode('","', array_map('esc_sql', $post_status)).'") LIMIT '.esc_sql($index).', '.esc_sql($pack));
						if (!empty($posts) && is_array($posts)) {
							
							// Control timeout
							set_time_limit(0);
							
							// Existing posts
							$response['ended'] = false;
							
							// Update meta data
							foreach ($posts as $post) {
								$post_id = (int) $post->ID;
								wp_update_comment_count_now($post_id);
							}
						}
					}
				}
			}
			
			// End
			self::output_ajax_response($response);
		}



		// Internal procedures
		// ---------------------------------------------------------------------------------------------------



		/**
		 * Number of posts to save 
		 */
		private static function get_expected_posts_count() {
			
			// Check cache
			static $total;
			if (isset($total))
				return $total;
			
			// Calculate
			global $wpdb;
			$post_types = self::get_allowed_post_types();
			$post_status = self::get_allowed_post_status();
			$total = (empty($post_types) || !is_array($post_types) || empty($post_status) || !is_array($post_status))? 0 : (int) $wpdb->get_var('SELECT COUNT(*) FROM '.esc_sql($wpdb->posts).' WHERE post_type IN ("'.implode('","', array_map('esc_sql', $post_types)).'") AND post_status IN("'.implode('","', array_map('esc_sql', $post_status)).'")');
			return $total;
		}



		/**
		 * Post types allowed for comments count update
		 */
		private static function get_allowed_post_types($output = 'keys') {
			
			// Current avoid post types
			$avoid_post_types = apply_filters(self::key.'avoid_post_types', array_map('trim', explode(',', self::post_types_avoid)));
			if (empty($avoid_post_types) || !is_array($avoid_post_types))
				return false;
			
			// Compute allowed post types
			$post_types = get_post_types(array(), 'objects');			
			$allowed_post_types = array_diff_key($post_types, array_fill_keys($avoid_post_types, true));
			
			// Return keys
			if ('keys' == $output)
				return array_keys($allowed_post_types);
			
			// Return names
			if ('names' == $output) {
				$names = array();
				foreach ($allowed_post_types as $key => $post_type)
					$names[] = $post_type->labels->name;
				return $names;
			}
			
			// Default
			return $allowed_post_types;
		}



		/**
		 * Post status allowed for comment count update
		 */
		private static function get_allowed_post_status() {
			$post_status_allowed = array_map('trim', explode(',', self::post_status_allow));
			$post_status = apply_filters(self::key.'allow_post_status', $post_status_allowed);
			return (empty($post_status) && !is_array($post_status))? false : array_intersect($post_status, $post_status_allowed);
		}



		/*
		 * Number of posts processed per request
		 */
		private static function get_pack() {
			return (int) apply_filters(self::key.'pack', self::pack);
		}



		// AJAX wrappers
		// ---------------------------------------------------------------------------------------------------



		/**
		 * Check and initialize ajax respose
		 */
		private static function check_ajax_submit(&$response, $capability, $nonce = null) {
			
			// Check default output
			if (!isset($response))
				$response = self::default_ajax_response($nonce);
			
			// Check user capabilities
			if (!current_user_can($capability)) {
				$response['status'] = 'error';
				$response['reason'] = __('User can`t perform this action', self::text_domain);
				return false;
			
			// Check if submitted nonce matches with the generated nonce we created earlier
			} elseif (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], isset($nonce)? $nonce : __FILE__)) {
				$response['status'] = 'error';
				$response['reason'] = __('Wordpress nonce verification error', self::text_domain);
				return false;
			}
			
			// Submit Ok
			return true;
		}



		/**
		 * Return array of ajax response
		 */
		private static function default_ajax_response($nonce = null) {
			return array(
				'status' => 	'ok',
				'reason' => 	'',
				'nonce' => 		wp_create_nonce(isset($nonce)? $nonce : __FILE__),
				'ended' => 		true,
			);
		}



		/**
		 * Check if is running an admin AJAX request
		 */
		private static function is_admin_ajax($action = null) {
			return (is_admin() && defined('DOING_AJAX') && DOING_AJAX && (empty($action) || (!empty($action) && !empty($_POST['action']) && $_POST['action'] == $action)));
		}



		/**
		 * Output AJAX in JSON format and exit
		 */
		private static function output_ajax_response($response) {
			header('Content-Type: application/json');
			die(json_encode($response));
		}



	}
}