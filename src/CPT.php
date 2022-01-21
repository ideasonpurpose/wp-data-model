<?php
namespace IdeasOnPurpose\WP;

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
        add_action('init', [$this, 'addQueryVars']);

        add_action('pre_get_posts', [$this, 'postsPerPage']);

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

    /**
     * @var $typenow is a strange WordPress global used on admin pages, usually set from post_type
     */
    public function filterByTaxonomy($slug)
    {
        global $typenow;
        if ($typenow === $this->type) {
            $tax = get_taxonomy($slug);
            if (!$tax) {
                return;
            }
            $terms = get_terms($slug);
            $terms = array_map(function ($term) use ($slug) {
                $template = '<option value="%s"%s>%s (%d)</option>';
                $selected =
                    isset($_GET[$slug]) && $_GET[$slug] == $term->slug
                        ? ' selected="selected"'
                        : '';
                return sprintf($template, $term->slug, $selected, $term->name, $term->count);
            }, $terms);
            echo "<select name='$slug' id='$slug' class='postform'>";
            echo "<option value=''>All {$tax->label}</option>";
            echo implode("\n", $terms);
            echo '</select>';
        }
    }

    /**
     * @deprecated Theme-specific view option, shouldn't be here. Will be removed
     *
     * Reference: https://developer.wordpress.org/reference/hooks/pre_get_posts/#change-the-number-of-posts-per-page-by-post-type
     *
     * Assignment hierarchy for posts_per_page:
     *      1. $query->query['per_page']
     *      2. $query->query['posts_per_page']
     *      3. $this->posts_per_page
     *      4. get_option('posts_per_page')
     *
     *
     * TODO: This is theme-specific, and should not be here
     *
     */
    public function postsPerPage($query)
    {
        new Error(
            'The postsPerPage method is deprecated and will be removed in the next minor release. Settings for the number of posts to return for a given post_type should be done in the theme.'
        );

        // d( $query);
        if (
            !is_admin() &&
            !wp_is_json_request() &&
            isset($query->query_vars['post_type']) &&
            $query->query_vars['post_type'] == $this->type
            ) {
                // d('here?');
            $per_page =
                $query->query['per_page'] ??
                ($query->query['posts_per_page'] ??
                    ($this->posts_per_page ?? get_option('posts_per_page')));
            $query->set('posts_per_page', $per_page);
        }
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
     * Add `per_page` query var for overriding posts_per_page from the url
     * NOTE: This overlaps with the wp-json query var
     *
     * TODO: This seems like a really bad idea in retrospect. In use? Can we remove it?
     */
    public function addQueryVars()
    {
        global $wp;
        $wp->add_query_var('per_page');
    }

    /**
     * @deprecated Renaming this to $this->css
     * @var $adminCSS A blob of CPT-specific CSS.
     * Rules should probably start with `.post-type-$this->type` so
     * selectors remain specific to the defined post_type.
     *
     * TODO: This should be an empty string
     */
    protected $adminCSS = '';

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
        if (!empty($this->adminCSS)) {
            //TODO: Replace with common Error Reporter (when it exists)
            new Error('The $this->adminCSS property is deprecated. Use $this->css instead.');
            wp_add_inline_style('wp-admin', $this->adminCSS);
        }
        if (!empty($this->css)) {
            $cssComment = "\n    /* %s " . get_class($this) . " inline CSS */\n";
            $css = sprintf("$cssComment\n%s\n$cssComment", 'START', $this->css, 'END');
            wp_add_inline_style('wp-admin', $css);
        }
    }
}
