<?php
namespace IdeasOnPurpose\WP;

abstract class Taxonomy
{
    /**
     * The `props` method will be called immediately from the parent's __construct method.
     * Use this to define properties, actions and filters specific to the new CPT or Taxonomy.
     */
    abstract protected function props();

    public function __construct($post_types = null)
    {
        $this->props();
        $this->post_types = $post_types;

        add_action('init', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'adminStyles'], 100);
    }

    public function register()
    {
        register_taxonomy($this->slug, $this->post_types, $this->args);
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
