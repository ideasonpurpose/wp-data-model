<?php
namespace ideasonpurpose\WP\Admin;

/**
 * Simple class to add separators to the main WordPress admin left-sidebar
 * Initialize the class with a list (array or multiple arguments) of numbers
 * representing the menu index locations where separators should appear.
 * Separators will appear after the provided index.
 */
class Separators
{
    public function __construct()
    {
        $this->seps = new \RecursiveIteratorIterator(new \RecursiveArrayIterator(func_get_args()));
        add_action('admin_menu', [$this, 'addSeparators']);
        add_action('admin_enqueue_scripts', [$this, 'styleSeparators'], 100);
    }

    public function addSeparators()
    {
        global $menu;
        foreach ($this->seps as $pos) {
            $pos = floatval($pos) + count($menu) / 1000;
            $menu["$pos"] = [
                0 => '',
                1 => 'read',
                2 => "separator-$pos",
                3 => '',
                4 => 'wp-menu-separator'
            ];
        }
        ksort($menu);
    }

    public function styleSeparators()
    {
        $css = '
            #adminmenu li.wp-menu-separator {
              margin: 6px 0;
            }
            #adminmenu li.wp-menu-separator .separator {
              border-top: 1px dotted rgba(255, 255, 255, 0.25);
            }
        ';
        wp_add_inline_style('wp-admin', $css);
    }
}
