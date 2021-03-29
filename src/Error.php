<?php namespace IdeasOnPurpose\WP;

class Error
{
    public function __construct($msg)
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
