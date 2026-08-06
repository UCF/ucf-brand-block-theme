<?php
/**
 * Minimal stand-in for core's WP_Block.
 *
 * `ucf_brand_binding_section_number()` type-checks the instance and reads `context`. Same
 * reasoning as the WP_Post stub alongside it: a data holder, not behavior.
 *
 * @package ucf-brand-block-theme
 */

if ( ! class_exists( 'WP_Block' ) ) {

	/**
	 * Data-only stand-in for the core class of the same name.
	 */
	class WP_Block {

		/**
		 * Block context, e.g. `postId`.
		 *
		 * @var array<string, mixed>
		 */
		public $context = array();

		/**
		 * Construct from a context map.
		 *
		 * @param array<string, mixed> $context Block context.
		 */
		public function __construct( array $context = array() ) {
			$this->context = $context;
		}
	}
}
