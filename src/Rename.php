<?php

namespace IdeasOnPurpose\WP;

abstract class Rename
{
    protected $labels;

    abstract protected function rename();

    public function __construct()
    {
        /**
         * Get name of child Class
         */
        $childRef = new \ReflectionClass(get_class($this));
        $this->type = strtolower($childRef->getShortName());

        /**
         * Special case to translate "tag" into "post_tag"
         */
        if ($this->type == 'tag') {
            $this->type = 'post_tag';
        }

        $this->rename();
        $this->update();
    }

    protected function update()
    {
        global $wp_post_types, $wp_taxonomies;

        if (array_key_exists($this->type, $wp_post_types)) {
            $labels = &$wp_post_types[$this->type]->labels;
        } elseif (array_key_exists($this->type, $wp_taxonomies)) {
            $labels = &$wp_taxonomies[$this->type]->labels;
        }

        if (isset($labels)) {
            foreach ($this->labels as $key => $value) {
                if (property_exists($labels, $key) && $labels->$key != $value) {
                    $labels->$key = $value;
                }
            }
        }
    }
}
