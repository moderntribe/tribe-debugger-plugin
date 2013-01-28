<?php
/**
 * Integrate debugging with debug bar plugin.
 *
 * @author Peter Chester
 */

// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }

function load_debugger_debug_bar($panels) {
	if (!class_exists('DebuggerDebugBar') && class_exists('Debug_Bar_Panel')) {
		class DebuggerDebugBar extends Debug_Bar_Panel {

			private static $debug_log = array();
			
			function init() {
				$this->title( __('Debugger', Debugger::PLUGIN_DOMAIN) );
				remove_action( 'debugger_render_log_entry',array( Debugger::instance(), 'renderLog' ), 11, 3 );				
				add_action( 'debugger_render_log_entry', array( $this, 'logDebug' ), 12, 3 );
				wp_enqueue_style( 'debugger_css', plugins_url('resources/debugger-debug-bar.css', dirname(__FILE__)) );				
			}

			function prerender() {
				$this->set_visible( true );
			}

			function render() {
				echo '<div id="debugger-debug-bar">';
				if (count(self::$debug_log)) {
					echo '<ul>';
					foreach(self::$debug_log as $k => $logentry) {
						echo "<li class='debugger-debug-item debugger-debug-{$logentry['format']}'>";
						echo "<div class='debugger-debug-entry-title'>{$logentry['title']}</div>";
						if (isset($logentry['data']) && $logentry['data']) {
							echo '<div class="debugger-debug-entry-data"><pre>';
							print_r($logentry['data']);
							echo '</pre></div>';
						}
						echo '</li>';
					}
					echo '</ul>';
				} else {
					echo "No entries match your debug criteria.";
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
			public function logDebug($title,$format='log',$data=false) {
				self::$debug_log[] = array(
					'title' => $title,
					'data' => $data,
					'format' => $format,
				);
			}
		}
		$panels[] = new DebuggerDebugBar;
	}
	return $panels;
}

add_filter( 'debug_bar_panels', 'load_debugger_debug_bar' );
?>