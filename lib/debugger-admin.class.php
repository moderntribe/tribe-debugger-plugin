<?php
/*
 * Debugger Admin Class
 */

if ( class_exists( 'Debugger' ) && !class_exists( 'Debugger_Admin' ) ) {
	class Debugger_Admin {

		private static $slug = 'debugger';

		private static $plugin_name;

		/** @var Debugger_Admin */
		private static $instance;

		protected function __construct() {
			self::$plugin_name = __( 'Debugger', 'tribe-debugger' );
			//is_plugin_active_for_network();
			add_action( 'admin_menu', array( $this, 'register_settings_page' ), 10, 0 );
			add_action( 'network_admin_menu', array( $this, 'add_network_page' ) );
		}

		/**
		 * Add network admin settings page.
		 */
		public function add_network_page() {
			add_submenu_page( 'settings.php', self::$plugin_name, self::$plugin_name, 'manage_sites', self::$slug, array( $this, 'network_options_page_view' ) );

			$this->register_settings();

		}

		/**
		 * Load network settings page.
		 */
		public function network_options_page_view() {
			screen_icon(empty($screen_icon)?'options-general':$screen_icon); ?>
			<h2><?php echo self::$plugin_name; ?></h2>
			<div class="wrap">
			<?php
			echo "<form action='".$_SERVER['REQUEST_URI']."' method='post'>";
			settings_fields( self::$slug );
			do_settings_sections( self::$slug );
			submit_button();
			echo '</form>';
			echo '</div>';
		}

		public function register_settings_page() {

			add_options_page(
				__( 'Debugger Settings', 'tribe-debugger' ),
				self::$plugin_name,
				'manage_options',
				self::$slug,
				array($this, 'display_settings_page')
			);

			$this->register_settings();

		}

		public function register_settings() {

			add_settings_section(
				'default',
				__( 'Debugger Configuration', 'tribe-debugger' ),
				array($this, 'display_settings_section'),
				self::$slug
			);

			add_settings_field(
				'tribe_debugger_groups',
				__( 'Debugger Groups', 'tribe-debugger' ),
				array( $this, 'display_groups_field' ),
				self::$slug
			);
			register_setting( self::$slug, 'tribe_debugger_groups', array( $this, 'sanitize_array' ) );

			add_settings_field(
				'tribe_debugger_parameters',
				__( 'Parameters', 'tribe-debugger' ),
				array( $this, 'display_parameters_field' ),
				self::$slug
			);
			register_setting( self::$slug, 'tribe_debugger_parameters' );

			add_settings_field(
				'tribe_debugger_time_threshold',
				__( 'Time Threshold (ms)', 'tribe-debugger' ),
				array( $this, 'display_time_threshold_field' ),
				self::$slug
			);
			register_setting( self::$slug, 'tribe_debugger_time_threshold' );

			add_settings_field(
				'tribe_debugger_memory_threshold',
				__( 'Memory Threshold (kB)', 'tribe-debugger' ),
				array( $this, 'display_memory_threshold_field' ),
				self::$slug
			);
			register_setting( self::$slug, 'tribe_debugger_memory_threshold' );

			add_settings_field(
				'tribe_debugger_actions',
				__( 'Hooks', 'tribe-debugger' ),
				array( $this, 'display_actions_field' ),
				self::$slug
			);
			register_setting( self::$slug, 'tribe_debugger_actions', array( $this, 'sanitize_array' ) );

			add_settings_field(
				'tribe_debugger_ok_urls',
				__( 'Whitelist URLS', 'tribe-debugger' ),
				array( $this, 'display_ok_urls_field' ),
				self::$slug
			);
			register_setting( self::$slug, 'tribe_debugger_ok_urls', array( $this, 'sanitize_array' ) );

		}

		public function display_settings_page() {

			screen_icon(empty($screen_icon)?'options-general':$screen_icon); ?>
			<h2><?php echo self::$plugin_name; ?></h2>
			<div class="wrap">
			<?php
			echo "<form action='".admin_url('options.php')."' method='post'>";
			settings_fields( self::$slug );
			do_settings_sections( self::$slug );
			submit_button();
			echo '</form>';
			echo '</div>';

		}

		/**
		 * Stupid function that needs to be here to avoid WP errors. :(
		 */
		public function display_settings_section() { }

		/**
		 * Make sure that data passed from the text fields is stored as an array.
		 * @param $data
		 *
		 * @return array
		 */
		public function sanitize_array( $data ) {
			if ( empty( $data ) ) {
				$data = array();
			}
			if ( !is_array( $data ) ) {
				$data = explode( "\n", $data );
			}
			return $data;
		}

		public function display_groups_field() {
			$current = join( "\n", get_option( 'tribe_debugger_groups', array('ALL') ) );
			?><p><textarea name="tribe_debugger_groups"><?php echo $current; ?></textarea></p><?php
		}

		public function display_parameters_field() {
			$current = get_option( 'tribe_debugger_parameters', array() );
			?>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="time" <?php checked( in_array('time',$current), true ); ?> /> <?php _e('Time', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="timedelta" <?php checked( in_array('timedelta',$current), true ); ?> /> <?php _e('Time Delta', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="memory" <?php checked( in_array('memory',$current), true ); ?> /> <?php _e('Memory', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="memorydelta" <?php checked( in_array('memorydelta',$current), true ); ?> /> <?php _e('Memory Delta', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="data" <?php checked( in_array('data',$current), true ); ?> /> <?php _e('Data', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="backtrace" <?php checked( in_array('backtrace',$current), true ); ?> /> <?php _e('Backtrace', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="url" <?php checked( in_array('url',$current), true ); ?> /> <?php _e('URL', 'tribe-debugger'); ?></p>
			<p><input type='checkbox' name="tribe_debugger_parameters[]" value="server" <?php checked( in_array('server',$current), true ); ?> /> <?php _e('Server', 'tribe-debugger'); ?></p>
			<?php
		}

		public function display_time_threshold_field() {
			$current = intval( get_option( 'tribe_debugger_time_threshold', 0 ) );
			?><p><input type="text" name="tribe_debugger_time_threshold" value="<?php echo $current; ?>" /></p><?php
		}

		public function display_memory_threshold_field() {
			$current = intval( get_option( 'tribe_debugger_memory_threshold', 0 ) );
			?><p><input type="text" name="tribe_debugger_memory_threshold" value="<?php echo $current; ?>" /></p><?php
		}

		public function display_actions_field() {
			$current = join( "\n", get_option( 'tribe_debugger_actions', array() ) );
			?><p><textarea name="tribe_debugger_actions"><?php echo $current; ?></textarea></p><?php
		}

		public function display_ok_urls_field() {
			$current = join( "\n", get_option( 'tribe_debugger_ok_urls', array() ) );
			?><p><textarea name="tribe_debugger_ok_urls"><?php echo $current; ?></textarea></p><?php
		}

		private function admin_url() {
			return add_query_arg(array('page' => self::$slug), admin_url('options-general.php'));
		}

		/********** Singleton *************/

		/**
		 * Create the instance of the class
		 *
		 * @static
		 * @return void
		 */
		public static function init() {
			self::$instance = self::get_instance();
		}

		/**
		 * Get (and instantiate, if necessary) the instance of the class
		 * @static
		 * @return Debugger_Admin
		 */
		public static function get_instance() {
			if ( !is_a( self::$instance, __CLASS__ ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		final public function __clone() {
			trigger_error( "No cloning allowed!", E_USER_ERROR );
		}

		final public function __sleep() {
			trigger_error( "No serialization allowed!", E_USER_ERROR );
		}
	}
}
?>