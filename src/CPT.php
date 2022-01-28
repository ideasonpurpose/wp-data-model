<?php
namespace IdeasOnPurpose\WP;

use IdeasOnPurpose\WP\DataModel;

abstract class CPT
{
    /**
     * The `props` method will be called immediately from the parent's __construct method.
     * Use this to define properties, actions and filters specific to the new CPT or Taxonomy.
     *
     * This is named 'props' and not 'init' to avoid confusion with the WordPress 'init' hook.
     * Props is called immediately, and is used to set up properties for the eventual
     * instantiation of the class.
     */
    abstract protected function props();

    protected $menu_index;
    /**
     * Default value for $menu_index is 21, "below Pages"
     * https://codex.wordpress.org/Function_Reference/register_post_type#menu_position
     *
     * @param integer $menu_index Used to position the item in admin menus
     */
    public function __construct($menu_index = 21)
    {
        $this->menu_index = $menu_index;
        $this->props();

        add_action('init', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'adminStyles'], 100);
    }

    /**
     * This is called from the WordPress init hook. $type and $args should already have been
     * defined by the `props()` method.
     */
    public function register()
    {
        register_post_type($this->type, $this->args);
    }

    public function filterByTaxonomy($slug)
    {
        new Error(
            'CPT::filterByTaxonomy() was moved to DataModel, assign Taxonomy filters to post_types using DataModel::taxonomyFilterMap instead.'
        );
    }

    /**
     * Call this to remove the date menu for this CPT
     * Should be called from the ____ filter:
     *
     *     add_filter('disable_months_dropdown', [$this, 'removeDateMenu'], 10, 2);
     *
     * @link https://developer.wordpress.org/reference/hooks/disable_months_dropdown/
     *
     * TODO: This should be self-contained.
     *       Base it on a setting/flag, and auto-apply the filter as well.
     */
    public function removeDateMenu($disable, $post_type)
    {
        if ($post_type == $this->type) {
            return true;
        }
        return $disable;
    }

    /**
     * @var $css A blob of CPT- or Taxonomy-specific CSS styles.
     * Static rules can be defined directly in the child class, but PHP requires
     * anything dynamic be assembled by a method.
     * Rules should probably start with `.post-type-{$this->type}` or
     * `.taxonomy-{$this->type}` to keep selectors specific to the target object.
     */
    protected $css = '';

    /**
     * Called from the `admin_enqueue_scripts` action, this simply inlines
     * the contents of $this->css into admin pages. Nothing is validated,
     * so if it blows up, it's on you.
     */
    public function adminStyles()
    {
        if (!empty($this->css)) {
            $cssComment = "\n    /* %s " . get_class($this) . " inline CSS */\n";
            $css = sprintf("$cssComment\n%s\n$cssComment", 'START', $this->css, 'END');
            wp_add_inline_style('wp-admin', $css);
        }
    }
}
