<?php

namespace IdeasOnPurpose\WP;

// require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Inflector\InflectorFactory;
use IdeasOnPurpose\WP\DataModel\Labels;

abstract class DataModel
{
    /**
     * Plugins define Taxonomies and Custom Post Types in the child class by overriding `register`
     */
    abstract public function register();

    public $__FILE__;
    public $map;
    public $taxonomyMap = [];

    public function __construct()
    {
        /**
         * Plugins are usually defined with __FILE__ but that won't work inside
         * a parent class, so we use a ReflectionClass with the inherited name to
         * discover the file path of the child class then pass that new `__FILE__`
         * to our Plugin\API class.
         *
         * @link https://www.php.net/manual/en/reflectionclass.getfilename.php
         * @link https://developer.wordpress.org/reference/functions/register_activation_hook/
         */
        $childRef = new \ReflectionClass(get_class($this));
        $this->__FILE__ = $childRef->getFileName();
        new Plugin\Api($this);

        /**
         * Store the existing taxonomy/post_type map for comparison
         * after registering new taxonomies and CPTs.
         */
        $this->map = $this->getNavMenuNames();

        /**
         * Call the child class's register method then parse the TaxonomyMap
         * `register` must be called before init 10
         * `parseTaxonomyMap` must be called after init 10
         */
        add_action('init', [$this, 'register'], 5);
        add_action('init', [$this, 'parseTaxonomyMap'], 100);
        add_action('restrict_manage_posts', [$this, 'parseTaxonomyFilterMap']);

        /**
         * Set all Taxonomies and CPTs to be visible by default in the nav-menu admin
         */
        add_filter('get_user_option_metaboxhidden_nav-menus', [$this, 'navMenuVisibility']);
    }

    /**
     * @var $taxonomyMap is an associative array where each key-value pair
     * is a taxonomy-slug pointing to an array of post_type slugs. Single
     * post_type assignments can be strings instead of arrays.
     * Undefined taxonomies or post_types will pass through with no effect.
     *
     * All associations are additive, no existing post_type relationships will
     * be removed if already defined by not specified in the map.
     *
     * @link https://developer.wordpress.org/reference/functions/register_taxonomy_for_object_type/
     *
     * Note: The slugs for built-in taxonomies are: 'post_tag' and 'category'
     *
     * @example
     *    $this->taxonomyMap = [
     *        'topic' => ['post', 'project', 'person'],
     *        'post_tag' => ['project', 'person'],
     *    ];
     */
    public function parseTaxonomyMap()
    {
        foreach ($this->taxonomyMap as $tax => $types) {
            foreach ((array) $types as $type) {
                register_taxonomy_for_object_type($tax, $type);
            }
        }
    }

    public $taxonomyFilterMap = [];
    public function parseTaxonomyFilterMap()
    {
        /**
         * @var array $typenow is a WordPress global used on admin pages, assigned from post_type
         */
        global $typenow;

        $taxMenus = [];

        foreach ($this->taxonomyFilterMap as $tax_name => $types) {
            if (in_array($typenow, (array) $types)) {
                $taxMenus[] = get_taxonomy($tax_name);
            }
        }
        $taxMenus = array_filter($taxMenus);

        foreach ($taxMenus as $tax) {
            wp_dropdown_categories([
                'option_none_value' => '',
                'show_option_none' => $tax->labels->all_items,
                'name' => $tax->query_var,
                'taxonomy' => [$tax->name],
                'orderby' => 'name',
                'selected' => @$_GET[$tax->query_var],
                'hierarchical' => $tax->hierarchical,
                'depth' => 3,
                'value_field' => 'slug',
            ]);
        }
    }

    /**
     * A copy of updateLabels which requires a singular and plural label definition
     * so we can remove the inflector dependency.
     *
     * TODO: Unused?
     * @param array $labelBase
     * @param mixed $labels
     * @return void
     */
    #[\Deprecated(message: 'use WP\DataModel\Labels::updateLabels instead', since: '1.0.0')]
    public static function updateLabelsDirect($singular, $plural, $labels)
    {
        $deprecation_msg =
            "<code>WP\DataModel::updateLabelsDirect</code> is deprecated.\n" .
            "Please update $singular labels to use " .
            '<code>WP\DataModel\Labels::updateLabels</code> instead';
        new Error($deprecation_msg);

        return Labels::updateLabels($singular, $plural, (array) $labels);
    }

    /**
     * @param string $labelBase
     * @param mixed $labels
     * @param bool $inflect
     * @return array
     */
    #[\Deprecated(message: 'use WP\DataModel\Labels::updateLabels instead', since: '1.0.0')]
    public static function updateLabels($labelBase, $labels, $inflect = true)
    {
        $deprecation_msg =
            "<code>WP\DataModel::updateLabels</code> is deprecated.\n" .
            "Please update $labelBase labels to use " .
            '<code>WP\DataModel\Labels::updateLabels</code> instead';
        new Error($deprecation_msg);

        if ($inflect) {
            list($singular, $plural) = self::inflectorBridge($labelBase);
        } else {
            $singular = $plural = $labelBase;
        }
        return Labels::updateLabels($singular, $plural, (array) $labels);
    }

    /**
     * Generate a set of labels based on $labelBase for the post_type or Taxonomy in $object
     *
     * @param  String $labelBase - The name to use as the basis of the generated labels
     * @param  Array $overrides - A set of non-standard labels to apply over defaults
     * @param  String $object - The kind of labels to generate, 'page', 'category', etc.
     * @param  Boolean $inflect - Whether or not to normalize $labelBase to singular/plural
     * @return Object
     */
    #[\Deprecated(message: 'use WP\DataModel\Labels::labels instead', since: '1.0.0')]
    public static function labels($labelBase, $inflect = true, $overrides = [], $object = 'page')
    {
        global $wp_post_types, $wp_taxonomies;

        $deprecation_msg =
            "<code>WP\DataModel::labels</code> is deprecated.\n" .
            "Please update $labelBase labels to use " .
            '<code>WP\DataModel\Labels::labels</code> instead';
        new Error($deprecation_msg);

        if ($inflect) {
            list($singular, $plural) = self::inflectorBridge($labelBase);
        } else {
            $singular = $plural = $labelBase;
        }

        /**
         * Check to see if $object exists as a Post_type or Taxonomy. If $labels do not
         * exist, bail out early.
         */
        if (array_key_exists($object, $wp_post_types)) {
            $is_post = true;
        } elseif (array_key_exists($object, $wp_taxonomies)) {
            $is_post = false;
        } else {
            /**
             * No matching post_types or taxonomies
             */
            $msg = "Data Model renaming failed: '{$object}' is not a known Post_type or Taxonomy.";
            return new Error($msg);
        }
        $newLabels = Labels::labels($singular, $plural, $is_post);
        return array_merge($newLabels, $overrides);
    }

    /**
     * Bridges the existing postTypeLabels and taxonomyLabels into the new
     * Labels class methods. Those expect both singular and plural strings.
     * @param mixed $base
     * @return array
     */
    #[\Deprecated(message: 'remove once deprecated label methods are gone', since: '1.0.0')]
    public static function inflectorBridge($base)
    {
        $inflector = InflectorFactory::create()->build();
        $singular = strtolower($inflector->singularize($base));
        $plural = strtolower($inflector->pluralize($base));

        return [$singular, $plural];
    }

    /**
     * Default to 'page' since that has a few more labels than 'post',
     * extra labels will be ignored.
     */
    #[\Deprecated(message: 'use WP\DataModel\Labels::post_type instead', since: '1.0.0')]
    public static function postTypeLabels($labelBase, $inflect = true, $overrides = [])
    {
        $deprecation_msg =
            "<code>WP\DataModel::postTypeLabels</code> is deprecated.\n" .
            "Please update $labelBase labels to use " .
            '<code>WP\DataModel\Labels::post_type</code> instead';
        new Error($deprecation_msg);

        if ($inflect) {
            list($singular, $plural) = self::inflectorBridge($labelBase);
        } else {
            $singular = $plural = $labelBase;
        }
        $newLabels = Labels::post_type($singular, $plural);
        return array_merge($newLabels, $overrides);
    }

    /**
     * Default to 'category' since that has a few more labels than 'post_tag',
     * extra labels will be ignored.
     */
    #[\Deprecated(message: 'use WP\DataModel\Labels::taxonomy instead', since: '1.0.0')]
    public static function taxonomyLabels($labelBase, $inflect = true, $overrides = [])
    {
        $deprecation_msg =
            "<code>WP\DataModel::taxonomyLabels</code> is deprecated.\n" .
            "Please update $labelBase labels to use " .
            '<code>WP\DataModel\Labels::taxonomy</code> instead';
        new Error($deprecation_msg);

        if ($inflect) {
            list($singular, $plural) = self::inflectorBridge($labelBase);
        } else {
            $singular = $plural = $labelBase;
        }
        $newLabels = Labels::taxonomy($singular, $plural);
        return array_merge($newLabels, $overrides);
    }

    public function getNavMenuNames()
    {
        global $wp_taxonomies, $wp_post_types;

        $taxonomies = array_map(function ($tax) {
            return "add-{$tax}";
        }, array_keys($wp_taxonomies));

        $post_types = array_map(function ($post_type) {
            return "add-post-type-{$post_type}";
        }, array_keys($wp_post_types));

        $map = array_merge($post_types, $taxonomies);
        return $map;
    }

    /*
     *  If $hidden is false, then this is likely the initial metadata definition
     *  This filter intercepts that and replaces it using a modified copy of the
     *  code from wp-admin/includes/nav-menu.php
     */
    public function navMenuVisibility($hidden)
    {
        global $wp_meta_boxes;

        /**
         * Only recreate the hidden nav-menu array if it's not already set
         */
        if ($hidden !== false) {
            return $hidden;
        }

        /**
         * from wp-admin/includes/nav-menu.php:185
         */
        $wp_defaults = [
            'add-post-type-page',
            'add-post-type-post',
            'add-custom-links',
            'add-category',
        ];
        $map = array_diff($this->getNavMenuNames(), $this->map);
        $initial_meta_boxes = array_merge($wp_defaults, $map);

        /**
         * Mostly direct copy from wp-admin/includes/nav-menu.php
         * @link https://github.com/WordPress/WordPress/blob/0d1e4e553c7b0ec0bf57a02dc0e40b46e2d1ac1d/wp-admin/includes/nav-menu.php#L171-L202
         */
        $hidden_meta_boxes = [];

        foreach (array_keys($wp_meta_boxes['nav-menus']) as $context) {
            foreach (array_keys($wp_meta_boxes['nav-menus'][$context]) as $priority) {
                foreach ($wp_meta_boxes['nav-menus'][$context][$priority] as $box) {
                    if (in_array($box['id'], $initial_meta_boxes, true)) {
                        unset($box['id']);
                    } else {
                        $hidden_meta_boxes[] = $box['id'];
                    }
                }
            }
        }

        $user = wp_get_current_user();
        update_user_meta($user->ID, 'metaboxhidden_nav-menus', $hidden_meta_boxes);
        return $hidden_meta_boxes;
    }
}
