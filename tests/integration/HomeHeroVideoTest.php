<?php
/**
 * The home hero's video, held back from phones, through the real render pipeline.
 *
 * `ucf_brand_defer_home_hero_video()` is a `render_block` filter driving
 * WP_HTML_Tag_Processor, so it is only honest against real WordPress. What it protects is a
 * 6.8 MB download on every phone visit: the video must leave the server with no `src`.
 *
 * @package ucf-brand-block-theme
 */

namespace UCF\Brand\Tests\Integration;

use WP_UnitTestCase;

/**
 * @covers ::ucf_brand_defer_home_hero_video
 */
final class HomeHeroVideoTest extends WP_UnitTestCase {

	const SRC = 'https://example.test/wp-content/uploads/Website-Loop.mp4';

	/**
	 * A cover with an uploaded background video, as the editor saves one.
	 *
	 * @param string $class_name Class on the cover.
	 * @return string Block markup.
	 */
	private function cover( $class_name = 'is-style-dark brand-home-hero__band' ) {
		return '<!-- wp:cover {"url":"' . self::SRC . '","id":248,"dimRatio":0,"backgroundType":"video","align":"full","className":"' . $class_name . '"} -->'
			. '<div class="wp-block-cover alignfull ' . $class_name . '">'
			. '<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>'
			. '<video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline src="' . self::SRC . '" data-object-fit="cover"></video>'
			. '<div class="wp-block-cover__inner-container"><!-- wp:paragraph --><p>Copy.</p><!-- /wp:paragraph --></div>'
			. '</div>'
			. '<!-- /wp:cover -->';
	}

	/**
	 * The `<video>` tag in rendered HTML.
	 *
	 * @param string $html Rendered HTML.
	 * @return \WP_HTML_Tag_Processor Processor positioned on the video.
	 */
	private function video( $html ) {
		$processor = new \WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag( 'video' ), 'The cover should still render its video.' );

		return $processor;
	}

	/**
	 * @return void
	 */
	public function test_the_home_hero_video_leaves_the_server_unloaded() {
		$video = $this->video( do_blocks( $this->cover() ) );

		$this->assertNull( $video->get_attribute( 'src' ) );
		$this->assertNull( $video->get_attribute( 'autoplay' ) );
		$this->assertSame( 'none', $video->get_attribute( 'preload' ) );
	}

	/**
	 * SYNC: the attribute src/js/hero-video.js reads the source back from.
	 *
	 * @return void
	 */
	public function test_the_source_is_kept_for_the_script() {
		$video = $this->video( do_blocks( $this->cover() ) );

		$this->assertSame( self::SRC, $video->get_attribute( 'data-brand-src' ) );
	}

	/**
	 * Every other cover on the site keeps its video exactly as core renders it.
	 *
	 * @return void
	 */
	public function test_other_covers_are_left_alone() {
		$video = $this->video( do_blocks( $this->cover( 'is-style-dark' ) ) );

		$this->assertSame( self::SRC, $video->get_attribute( 'src' ) );
		$this->assertTrue( $video->get_attribute( 'autoplay' ) );
		$this->assertNull( $video->get_attribute( 'data-brand-src' ) );
	}
}
