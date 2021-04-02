<?php namespace IdeasOnPurpose\WP;

/**
 * A basic error handler for WordPress Projects.
 * Messages will be always be logged with PHP's error_log function.
 * If the WP_DEBUG constant is set and true, an HTML comment will also
 * be added to the document from the wp_head hook.
 *
 * Log an error like this:
 *
 *    new WP\Error('error message');
 *
 * @link https://www.php.net/manual/en/function.error-log.php
 * @link https://developer.wordpress.org/reference/hooks/wp_head/
 *
 * TODO: Migrate this to it's own project (more portable & consistent)
 * TODO: Any reason not to just wrap Monolog? Why re-invent the wheel?
 *       Or, too bloated? We only need a basic log and head-injection
 *       @link http://seldaek.github.io/monolog/
 */
class Error
{
    public function __construct($msg)
    {
        $this->handler($msg);
    }

    public function handler($msg)
    {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;
        $trace = debug_backtrace();

        if ($this->is_debug) {
            add_action('wp_head', function () use ($msg, $trace) {
                echo "\n\n<!-- Error triggered in {$trace[1]['class']}:{$trace[1]['line']} --> \n";
                echo "<!-- $msg --> \n\n";
            });
        }
        error_log($msg);
    }
}
