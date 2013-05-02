<?php
/**
 * Integrate debugging with debug bar plugin.
 *
 * @author Peter Chester
 */

// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }

function load_debugger_debug_bar($panels) {
	if (!class_exists('Debugger_Debug_Bar') && class_exists('Debug_Bar_Panel')) {
		class Debugger_Debug_Bar extends Debug_Bar_Panel {

			private static $debug_log = array();

			private static $formats = array();

			private static $columns = array();

			private static $debugger;

			function init() {
				self::$debugger = Debugger::instance();
				$this->title( __('Debugger', 'tribe-debugger') );
				remove_action( 'debugger_render_log_entry',array( self::$debugger, 'render_log' ), 11, 3 );
				add_action( 'debugger_render_log_entry', array( $this, 'log_debug' ), 12, 3 );
				wp_enqueue_style( 'debugger_css', plugins_url('resources/debugger-debug-bar.css', dirname(__FILE__)), array(), '1.1' );
			}

			function prerender() {
				$this->set_visible( true );
			}

			function render() {
				?>
				<style>
					#debugger-debug-bar table {
						clear: both;
						width: 100%;
						cellspacing: 0;
						cellborder: 0;
						border-spacing: 0;
					}
					#debugger-debug-bar th {
						background-color: #f1f1f1;
						background-image: -ms-linear-gradient(top, #f9f9f9, #dfdfdf);
						background-image: -moz-linear-gradient(top, #f9f9f9, #dfdfdf);
						background-image: -o-linear-gradient(top, #f9f9f9, #dfdfdf);
						background-image: -webkit-gradient(linear, left top, left bottom, from(#f9f9f9), to(#dfdfdf));
						background-image: -webkit-linear-gradient(top, #f9f9f9, #dfdfdf);
						background-image: linear-gradient(top, #f9f9f9, #dfdfdf);
						border-bottom: 1px solid rgba(0,0,0,0.17);
						border-top: 1px solid rgba(255,255,255,0.2);
						border-left: 1px solid rgba(255,255,255,0.2);
						border-right: 1px solid rgba(0,0,0,0.04);
					}
					#debugger-debug-bar td {
						border-top: 1px solid rgba(255,255,255,0.2);
						border-left: 1px solid rgba(255,255,255,0.2);
						border-bottom: 1px solid rgba(0,0,0,0.04);
						border-right: 1px solid rgba(0,0,0,0.04);
					}
					#debugger-debug-bar td, #debugger-debug-bar th {
						padding: 10px;
					}
					#debugger-debug-bar td.debugger-debug-entry-data {
						width: 10%;
					}
					td.debugger-debug-entry-data {
						text-align: right;
					}
					td.debugger-debug-entry-data-backtrace, td.debugger-debug-entry-data-data {
						text-align: left;
					}
					tr.debugger-debug-row {
						border-top: solid 1px rgba(0,0,0,0.1);
					}
					tr.debugger-debug-row-color-0 {
						color: rgb(241, 154, 0);
						background-color: rgba(241, 154, 0, 0.1);
					}
					tr.debugger-debug-row-color-1 {
						color: rgb(0, 134, 209);
						background-color: rgba(0, 134, 209, 0.1);
					}
					tr.debugger-debug-row-color-2 {
						color: rgb(159, 209, 0);
						background-color: rgba(159, 209, 0, 0.1);
					}
					tr.debugger-debug-row-color-3 {
						color: rgb(177, 60, 255);
						background-color: rgba(177, 60, 255, 0.1);
					}
					tr.debugger-debug-row-color-4 {
						color: rgb(255, 235, 0);
						background-color: rgba(255, 235, 0, 0.1);
					}
				</style>
				<?php
				echo '<div id="debugger-debug-bar">';
				$time = timer_stop(0);
				$memory = number_format_i18n( ceil( memory_get_usage(true) / 1048576 ) ); // Mb
				printf( '<h2><span>%s</span>%s seconds</h2>', __('Total Execution Time:'), $time );
				printf( '<h2><span>%s</span>%s Mb</h2>', __('Total Memory:'), $memory );
				if (count(self::$debug_log)) {
					echo '<table>';
					echo '<tr>';
					printf( '<th>%s</th>', __('Format') );
					printf( '<th>%s</th>', __('Title') );

					$cols = array_unique(self::$columns);

					foreach ( $cols as $c ) {
						printf( '<th>%s</th>', ucwords($c) );
					}

					echo '</tr>';

					//$formats = array_keys(self::$formats);
					foreach(self::$debug_log as $k => $logentry) {
						if ( count(self::$formats) > 1 ) {
							printf( '<tr class="debugger-debug-row debugger-debug-row-color-%d">', self::$formats[$logentry['format']]%5 );
						} else {
							echo '<tr class="debugger-debug-row">';
						}
						printf( '<td class="debugger-debug-entry-%s">%s</td>', 'format', $logentry['format'] );
						printf( '<td class="debugger-debug-entry-%s">%s</td>', 'title', $logentry['title'] );
						foreach ( $cols as $c ) {
							$v = ( isset( $logentry['data'][$c] ) ) ? $logentry['data'][$c] : '';

							$v = self::$debugger->format_item( $c, $v, true );

							printf( '<td class="debugger-debug-entry-data-%s debugger-debug-entry-data">%s</td>', $c, $v );
						}
						echo '</tr>';
					}
					echo '</table>';
				} else {
					echo "<p class='debugger-notice'>No entries match your debug criteria.</p>";
				}
				echo '</div>';
			}

			/**
			 * log debug statements for display in debug bar
			 *
			 * @param string $title - message to display in log
			 * @param string $data - optional data to display
			 * @param string $format - optional format (log|warning|error|notice)
			 * @return void
			 * @author Peter Chester
			 */
			public function log_debug($title,$format='log',$data=false) {
				self::$debug_log[] = array(
					'title' => $title,
					'format' => $format,
					'data' => $data,
				);
				if ( !isset(self::$formats[$format]) ) {
					self::$formats[$format] = count( self::$formats );
				}
				//self::$columns = array_merge(self::$columns,$data);
				self::$columns += array_keys($data);
			}
		}
		$panels[] = new Debugger_Debug_Bar;
	}
	return $panels;
}

add_filter( 'debug_bar_panels', 'load_debugger_debug_bar' );
?>