<?php

namespace IdeasOnPurpose\WP;

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
}
