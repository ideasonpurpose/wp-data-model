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
         * Call the child class's register method then parse the TaxonomyMap
         * `register` must be called before init 10
         * `parseTaxonomyMap` must be called after init 10
         */
        add_action('init', [$this, 'register'], 5);
        add_action('init', [$this, 'parseTaxonomyMap'], 100);
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
    protected $taxonomyMap = [];
    public function parseTaxonomyMap()
    {
        foreach ($this->taxonomyMap as $tax => $types) {
            foreach ((array) $types as $type) {
                register_taxonomy_for_object_type($tax, $type);
            }
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
     * @return void
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
}
