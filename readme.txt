=== Debugger ===

Contributors:  ModernTribe, peterchester, jbrinley
Tags: modern tribe, tribe, debug, debugger, profiling, profile, performance, tuning, analysis
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 1.4

== Description ==

You can use this plugin to manually log data or to capture logging on WordPress actions. You can capture load time, memory, backrace, data dumps, urls, and server IPs.

This is designed to either be managed using wp-config.php and logging OR using the admin and debugbar plugin ( http://wordpress.org/extend/plugins/debug-bar/ ). Most of the following documentation applies equally to the settings panel or the wp-config.php vars.  Either one works fine. The only exception is the DEBUG_LOG configuration which is only supported as a wp-config var.

Firstly, you can manually log things using the following function:

`do_action( 'log', $message, $group, $data );`

The $group allows you to selectively output logging based on groups of log messages. The $message is the string you want to see in the log. $data is an optional parameter for the data that you want to display in the log (objects, arrays, or any other sort of data really).

To render messages to the log, you must configure wp-config.php as follows:

Run debug on only these groups. Use 'ALL' to debug everything. The group 'ACTIONS' is reserved for WordPress actions.

`define( 'DEBUG_GROUPS', 'ACTIONS,default,myspecialgroup' );`

Display these outputs in the log for each log message.

`define( 'DEBUG_PARAMS', 'time,timedelta,memory,memorydelta,data,backtrace,url,server' );`

WordPress actions that you wish to log.

`define( 'DEBUG_ACTIONS', 'wp_head,switch_theme,wp_footer' );`

Optional restriction by URL (useful on MU installs).

`define( 'DEBUG_URLS', 'myurl.com' );`

Minimum time in milliseconds required to register a log entry as being slow. Default 0 for no minumum.

`define( 'DEBUG_MIN_TIME', 500 );`

Minimum memory in killobytes required to register a log entry as being heavy. Default 0 for no minumum.

`define( 'DEBUG_MIN_MEM', 1024 );`

Path to log file or set to TRUE to use php error log. Default FALSE for no logging.

`define( 'DEBUG_LOG', '/path/to/writable/log/file' );`

or

`define( 'DEBUG_LOG', TRUE );`

= Todo =

* Add variable for sample rate so that this could run randomly on production installs
* Admin panel per site
* Admin panel globally
* WordPress error logging
* Mysql query logging
* WP_Error integration
* Alerts ex: if this takes more than this much memory then log it...

== Screenshots ==

1. Use the debugger to track how much time or memory hooks or benchmarked parts of code take to execute.
2. Configure your test parameters in wp-config.php or right in the admin.
3. Use the debugger to dump stack traces or pass data to see what the data looks like.

== Changelog ==

= 1.4 =

* Bug fixes
* Add screenshots
* Tune CSS performance in debug bar

= 1.3 =

* Add settings panel and options based configuration.
* Log the specific url when http curl hook is logged.

= 1.2 =

* Rename 'delta' to 'timedelta' and add 'memorydelta'

= 1.1 =

* Improve loading order to ensure that production sites aren't adversely effected when no debugging is needed.
* Update logging display.
* Add initializer message so that it's clear when a new load has started.
* Clean up debug bar UI a bit.

= 1.0 =

Initial Release.
