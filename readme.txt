=== Debugger ===

Contributors:  ModernTribe, peterchester, jbrinley
Tags: modern tribe, tribe, debug, debugger, profiling, profile, performance, tuning, analysis
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 1.1

== Description ==

You can use this plugin to manually log data or to capture logging on WordPress actions. You can capture load time, memory, backrace, data dumps, urls, and server IPs.

Firstly, you can manually log things using the following function:

`do_action( 'log', $message, $group, $data );`

The $group allows you to selectively output logging based on groups of log messages. The $message is the string you want to see in the log. $data is an optional parameter for the data that you want to display in the log (objects, arrays, or any other sort of data really).

To render messages to the log, you must configure wp-config.php as follows:

Run debug on only these groups. Use 'ALL' to debug everything. The group 'ACTIONS' is reserved for WordPress actions.

`define( 'DEBUG_GROUPS', 'ACTIONS,default,myspecialgroup' );`

Display these outputs in the log for each log message.

`define( 'DEBUG_PARAMS', 'time,delta,memory,data,backtrace,url,server' );`

WordPress actions that you wish to log.

`define( 'DEBUG_ACTIONS', 'wp_head,switch_theme,wp_footer' );`

WordPress actions that you wish to log.

`define( 'DEBUG_URLS', 'myurl.com' );`

= Todo =

* admin panel per site
* admin panel globally
* wordpress error logging
* sql query logging
* WP_Error integration
* Alerts ex: if this takes more than this much memory then log it...

== Changelog ==

= 1.1 =

* Improve loading order to ensure that production sites aren't adversely effected when no debugging is needed.
* Update logging display.
* Add initializer message so that it's clear when a new load has started.
* Clean up debug bar UI a bit.

= 1.0 =

Initial Release.
