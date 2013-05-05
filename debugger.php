<?php
/*
Plugin Name:	Debugger Plugin
Description:	Code for debugging code
Author:			Modern Tribe, Inc.
Version:		1.4
Author URI:		http://tri.be/

Usage:

You can use this plugin to manually log data or to capture logging on WordPress actions. You can capture load time, memory, backrace, data dumps, urls, and server IPs.

Firstly, you can manually log things using the following function:
do_action( 'log', $message, $group, $data );

The $group allows you to selectively output logging based on groups of log messages. The $message is the string you want to see in the log. $data is an optional parameter for the data that you want to display in the log (objects, arrays, or any other sort of data really).

To render messages to the log, you must configure wp-config.php as follows:

// Run debug on only these groups. Use 'ALL' to debug everything. The group 'ACTIONS' is reserved for WordPress actions.
define( 'DEBUG_GROUPS', 'ACTIONS,default,myspecialgroup' );

// Display these outputs in the log for each log message.
define( 'DEBUG_PARAMS', 'time,timedelta,memory,memorydelta,data,backtrace,url,server' );

// WordPress actions that you wish to log.
define( 'DEBUG_ACTIONS', 'wp_head,switch_theme,wp_footer' );

// WordPress actions that you wish to log.
define( 'DEBUG_URLS', 'myurl.com' );

// Minimum time in milliseconds required to register a log entry as being slow. Default 0 for no minumum.
define( 'DEBUG_MIN_TIME', 500 );

// Minimum memory in killobytes required to register a log entry as being heavy. Default 0 for no minumum.
define( 'DEBUG_MIN_MEM', 1024 );

// Path to log file or set to TRUE to use php error log. Default FALSE for no logging.
define( 'DEBUG_LOG', '/path/to/writable/log/file' );
// or
define( 'DEBUG_LOG', TRUE );

TODO:

* Test and update for MU (network active global testing vs local blog testing)
* Offer the option to log to the WP error log.
* Offer sql query logging
* WP_Error integration
* Summary mode - to collect a history of logging and try and find patterns over many loads.
*/

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

if ( !class_exists('Debugger') ) {

	class Debugger {

		private static $instance;
		/**
		 * Create the instance of the class
		 *
		 * @static
		 * @return void
		 */
		public static function init() {
			self::$instance = self::instance();
		}

		/** Singleton */

		/**
		 * Get (and instantiate, if necessary) the instance of the class
		 * @static
		 * @return singleton instance
		 */
		public static function instance() {
			if ( !is_a(self::$instance, __CLASS__) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		// Debug vars
		/*private static $groups = array('ACTIONS','default');
		private static $parameters = array('time','memory','data','backtrace','url','server');
		private static $actions = array('wp_head','switch_theme','wp_footer');*/
		private static $groups = array( 'ALL' );
		private static $parameters = array(
			'time',
			'timedelta',
			'memory',
			'memorydelta',
			'data',
			'backtrace',
			'url',
			'server'
		);
		private static $time_threshold = 0; // ms
		private static $memory_threshold = 0; // kb
		private static $actions = array();
		private static $ok_urls = false;
		private static $filter_blacklist = array(
			'debugger_render_log_entry',
			'log',
		);
		private static $log = false;

		private $time;
		private $time_previous;
		private $memory;
		private $memory_previous;

		// Constructor
		public function __construct() {

			if ( is_admin() ) {
				require_once('lib/debugger-admin.class.php');
				Debugger_Admin::init();
			}

			// Check to see if wp-config has defined any of the vars
			if (defined('DEBUG_GROUPS')) {
				self::$groups = apply_filters('debugger_groups',explode(',',DEBUG_GROUPS));
			} else {
				self::$groups = get_option( 'tribe_debugger_groups' );
			}
			if ( empty( self::$groups ) ) return;

			if (defined('DEBUG_URLS')) {
				self::$ok_urls = apply_filters('debugger_urls',explode(',',DEBUG_URLS));
			} else {
				self::$ok_urls = get_option( 'tribe_debugger_ok_urls', false );
			}
			if (!empty( self::$ok_urls ) && is_array( self::$ok_urls ) && !in_array( $_SERVER["HTTP_HOST"], self::$ok_urls ) ) return;

			if (defined('DEBUG_PARAMS')) {
				self::$parameters = apply_filters('debugger_params',explode(',',DEBUG_PARAMS));
			} else {
				self::$parameters = get_option( 'tribe_debugger_parameters', array() );
			}

			if (defined('DEBUG_ACTIONS')) {
				self::$actions = apply_filters('debugger_actions',explode(',',DEBUG_ACTIONS));
			} else {
				self::$actions = get_option( 'tribe_debugger_actions', array() );
			}

			if (defined('DEBUG_MIN_TIME')) {
				self::$time_threshold = apply_filters('debugger_min_time', DEBUG_MIN_TIME );
			} else {
				self::$time_threshold = get_option( 'tribe_debugger_time_threshold', 0 );
			}

			if (defined('DEBUG_MIN_MEM')) {
				self::$memory_threshold = apply_filters('debugger_min_memory', DEBUG_MIN_MEM );
			} else {
				self::$memory_threshold = get_option( 'tribe_debugger_memory_threshold', 0 );
			}

			if (defined('DEBUG_LOG')) {
				self::$log = apply_filters('debugger_log', DEBUG_LOG );
			} else {
				self::$log = get_option( 'tribe_debugger_log', 0 );
			}
			if ( !empty( self::$log ) ) {
				// Set logger to default to error_log.
				add_action( 'debugger_render_log_entry', array( $this, 'render_log' ), 10, 3 );
			}

			// Hook into all the actions in the config
			if ( is_array( self::$actions ) && count( self::$actions ) > 0 ) {
				foreach ( self::$actions as $action ) {
					add_action( $action, array( $this, 'autolog_action' ), 1, 2 );
				}
			}

			// Action for people to log messages to.
			add_action( 'log', array( $this, 'log' ), 1, 3 );

			require_once('lib/debug-bar.class.php');
			do_action( 'debugger_render_log_entry', '===== INITIALIZING DEBUGGER =====' );
		}

		private function get_time() {
			global $timestart;
			$timeend = microtime( true );

			if ( !$this->time )
				$this->time = ($timeend - $timestart) * 1000;
		}

		private function get_memory() {
			if ( !$this->memory )
				$this->memory = ceil( memory_get_usage(true) / 1024 ); // kb
		}

		// Log the actions/filters
		public function autolog_action() {
			if ( !in_array( current_filter(), self::$filter_blacklist ) ) {
				self::log(current_filter(),'ACTIONS');
			}
		}

		// Log messages
		public function log( $message = 'Log Message', $group = 'default', $data = null ) {

			// Check to see if group reporting is set in config
			if ( in_array( $group, self::$groups ) || in_array( 'ALL', self::$groups ) ) {

				$log_this = false; // start by assuming this log entry should not be saved.

				$log_data = array();

				$log_time = in_array('time',self::$parameters);
				$log_time_delta = in_array('timedelta',self::$parameters);
				$log_mem = in_array('memory',self::$parameters);
				$log_mem_delta = in_array('memorydelta',self::$parameters);

				if ( $log_time || $log_time_delta ) {
					$this->get_time();
					$time_delta = ( isset( $this->time_previous[$group] ) ) ? $this->time - $this->time_previous[$group] : 0;

					// If this passes the time test, then log it.
					if ( $time_delta >= self::$time_threshold ) {
						$log_this = true;
					}

					// Report time
					if ( $log_time ) {
						$log_data['time'] = $this->time; // ms
					}

					// Report delta time
					if ( $log_time_delta ) {
						$log_data['timedelta'] = $time_delta; // ms
					}

					$this->time_previous[$group] = $this->time;
				}
				$this->time = false;

				if ( $log_mem || $log_mem_delta ) {
					$this->get_memory();
					$memory_delta = ( isset( $this->memory_previous[$group] ) ) ? $this->memory - $this->memory_previous[$group] : 0;

					// If this passes the memory test, then log it.
					if ( $memory_delta >= self::$memory_threshold ) {
						$log_this = true;
					}

					// Report memory
					if ( $log_mem ) {
						$log_data['memory'] = $this->memory; // kb
					}

					// Report delta memory
					if ( $log_mem_delta ) {
						$log_data['memorydelta'] = $memory_delta; //kb
					}

					$this->memory_previous[$group] = $this->memory;
				}
				$this->memory = false;


				// @TODO: backtrace here to see if this is a plugin and if so then what?

				if ( ( $log_time || $log_time_delta || $log_mem || $log_mem_delta ) && !$log_this ) {
					return;
				}

				// Report URL
				if (in_array('url',self::$parameters)) {
					$log_data['url'] = $_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'];
				}

				// Report Server IP
				if (in_array('server',self::$parameters)) {
					$log_data['server'] = $_SERVER['SERVER_ADDR'];
				}

				// Report Backtrace
				if (in_array('backtrace',self::$parameters)) {
					//$log_data['backtrace'] = debug_backtrace();
					$log_data['backtrace'] = wp_debug_backtrace_summary(null,3,false);
				}

				// Report data
				if (in_array('data',self::$parameters)) {
					$log_data['data'] = ( isset($data) ) ? $data : '';
				}
				do_action( 'debugger_render_log_entry', $message, $group, $log_data );
			}

		}

		public function format_item( $type, $value, $html = false ) {
			switch ( $type ) {
				case 'time' :
				case 'timedelta' :
					$value = number_format( floatval( $value ) ).' ms';
					break;
				case 'memory' :
				case 'memorydelta' :
					$value = number_format( floatval( $value ) ).' kB';
					break;
				case 'backtrace' :
					if ( $html ) {
						$value = sprintf( '<ol><li>%s</li></ol>', join('</li><li>', $value ) );
					} else {
						$value = var_export( $value, true );
					}
					break;
				case 'data' :
					if ( is_array( $value ) || is_object( $value ) ) {
						if ( $html ) {
							$value = sprintf( '<pre>%s</pre>', var_export( $value, true ) );
						} else {
							$value = var_export( $value, true );
						}
					}
			}

			return $value;
		}

		public function render_log( $message='Log Message', $group='', $data=null ) {

			if ( !empty( $group ) ) $message .= " ($group)";

			if (!empty($data)) {
				if ( count( self::$parameters ) > 1 ) {
					foreach ( $data as $k => $v ) {
						$message .= '	' . $this->format_item( $k, $v );
					}
				} else {
					$message .= '	' . $this->format_item( self::$parameters[0], $data[0] );
				}
			}

			if ( is_string( self::$log ) && file_exists( self::$log ) ) {
				error_log( $message, 3, self::$log );
			} else {
				error_log( $message );
			}
		}

	}
	Debugger::init();
}
?>