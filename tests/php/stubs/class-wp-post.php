<?php
/**
 * Minimal stand-in for core's WP_Post.
 *
 * `ucf_brand_get_post_sections()` type-checks with `instanceof WP_Post` and then reads one
 * property. Stubbing a plain data object is not the same as mocking behavior — there is no
 * logic here to get wrong — so the functions that take a post stay in the fast, database-free
 * suite instead of forcing every one of them into the integration tier.
 *
 * @package ucf-brand-block-theme
 */

if ( ! class_exists( 'WP_Post' ) ) {

	/**
	 * Data-only stand-in for the core class of the same name.
	 */
	class WP_Post {

		/**
		 * Stored block markup.
		 *
		 * @var string
		 */
		public $post_content = '';

		/**
		 * Post title.
		 *
		 * @var string
		 */
		public $post_title = '';

		/**
		 * Post ID.
		 *
		 * @var int
		 */
		public $ID = 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- Mirrors core.

		/**
		 * Construct from a property map.
		 *
		 * @param array<string, mixed> $props Properties to set.
		 */
		public function __construct( array $props = array() ) {
			foreach ( $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}
