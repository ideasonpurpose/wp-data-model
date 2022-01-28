<?php

namespace IdeasOnPurpose\WP;

// require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Inflector\InflectorFactory;

abstract class DataModel
{
    /**
     * Plugins define Taxonomies and Custom Post Types in the child class by overriding `register`
     */
    abstract protected function register();

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
    public $taxonomyMap = [];
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
         * @var $typenow is a WordPress global used on admin pages, assigned from post_type
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

    private static function updateLabels($labelBase, $labels, $inflect = true)
    {
        /**
         * Enforce singular/plural and capitalizations for objects and labels
         */
        $inflector = InflectorFactory::create()->build();

        $singularSrc = strtolower($inflector->singularize($labels->name));
        $singularSrcTitleCase = $inflector->capitalize($singularSrc);
        $pluralSrc = strtolower($inflector->pluralize($labels->name));
        $pluralSrcTitleCase = $inflector->capitalize($pluralSrc);

        if ($inflect) {
            $singular = strtolower($inflector->singularize($labelBase));
            $singularTitleCase = $inflector->capitalize($singular);
            $plural = strtolower($inflector->pluralize($labelBase));
            $pluralTitleCase = $inflector->capitalize($plural);
        } else {
            $singular = $plural = strtolower($labelBase);
            $singularTitleCase = $pluralTitleCase = $inflector->capitalize($labelBase);
        }

        $patterns = [
            "/\b$singularSrc\b/",
            "/\b$singularSrcTitleCase\b/",
            "/\b$pluralSrc\b/",
            "/\b$pluralSrcTitleCase\b/",
        ];
        $replacements = [$singular, $singularTitleCase, $plural, $pluralTitleCase];

        $newLabels = new \stdClass();
        foreach ($labels as $key => $value) {
            if ($value === null) {
                $newLabels->$key = null;
            } else {
                $newLabels->$key = preg_replace($patterns, $replacements, $value);
            }
        }
        return $newLabels;
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
    public static function labels($labelBase, $inflect = true, $overrides = [], $object = 'page')
    {
        global $wp_post_types, $wp_taxonomies;

        /**
         * Check to see if $object exists as a Post_type or Taxonomy. If $labels do not
         * exist, bail out early.
         */
        if (array_key_exists($object, $wp_post_types)) {
            $labels = $wp_post_types[$object]->labels;
        } elseif (array_key_exists($object, $wp_taxonomies)) {
            /**
             * Tags and Categories do not overlap as cleanly as Posts/Pages, so we
             * request both default sets of labels then manually construct a super-set
             * of labels by merging tag-labels over category-null labels with all
             * values reset to 'category'.
             */
            $catLabels = $wp_taxonomies['category']->labels;
            $tagLabels = self::updateLabels('category', $wp_taxonomies['post_tag']->labels);
            $labels = new \stdClass();

            foreach ($catLabels as $key => $value) {
                $labels->$key = $value ?? $tagLabels->$key;
            }
        } else {
            /**
             * No matching post_types or taxonomies
             */
            $msg = "Data Model renaming failed: '{$object}' is not a known Post_type or Taxonomy.";
            return new Error($msg);
        }

        $newLabels = self::updateLabels($labelBase, $labels, $inflect);

        /**
         * Apply overrides to new label values
         */
        foreach ($overrides as $key => $value) {
            $newLabels->$key = $value;
        }
        return $newLabels;
    }

    /**
     * Default to 'page' since that has a few more labels than 'post',
     * extra labels will be ignored.
     */
    public static function postTypeLabels($labelBase, $inflect = true, $overrides = [])
    {
        return self::labels($labelBase, $inflect, $overrides, 'page');
    }

    /**
     * Default to 'category' since that has a few more labels than 'post_tag',
     * extra labels will be ignored.
     */
    public static function taxonomyLabels($labelBase, $inflect = true, $overrides = [])
    {
        return self::labels($labelBase, $inflect, $overrides, 'category');
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
