<?php
/**
 * The escaping and translation functions the theme calls, reproduced for the test suites.
 *
 * Two consumers share this file on purpose:
 *
 * - tests/php/TestCase.php, for the unit suite.
 * - tests/php/render-patterns.php, which renders pattern files so their block markup can be
 *   validated.
 *
 * They must agree byte for byte. The pattern renderer produces the markup the validity sweep
 * parses, so a difference between the two would mean the sweep validates something the site
 * never emits. Keeping one implementation is the only reliable way to prevent that — this
 * theme has been bitten before by two copies of one rule drifting apart (see the note on
 * heading slugs in docs/architecture.md).
 *
 * Behavior mirrors core where the difference is observable:
 *
 * - `esc_html()`/`esc_attr()` do NOT double-encode. Core's `_wp_specialchars()` defaults
 *   `$double_encode` to false, so `&amp;` stays `&amp;`. Search highlighting escapes each
 *   run between matches separately, and a double-encoding stub would make those assertions
 *   pass against behavior WordPress does not have.
 * - `wp_strip_all_tags()` removes script/style bodies before `strip_tags()`, then trims.
 *   Heading slugs and section text both run through it.
 *
 * Translation is identity: the suites assert on English output.
 *
 * @package ucf-brand-block-theme
 */

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Core does a great deal more here (protocol allowlisting, entity fixing). Only the
	 * ampersand rewrite is observable in the markup these suites assert on.
	 *
	 * @param string $url URL to escape.
	 * @return string
	 */
	function esc_url( $url ) {
		return str_replace( '&', '&#038;', (string) $url );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * @param string $text          Text to strip.
	 * @param bool   $remove_breaks Whether to collapse whitespace runs.
	 * @return string
	 */
	function wp_strip_all_tags( $text, $remove_breaks = false ) {
		if ( ! is_scalar( $text ) ) {
			return '';
		}

		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
		$text = strip_tags( $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Mirrors core's implementation.

		if ( $remove_breaks ) {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
		}

		return trim( $text );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data    Data to encode.
	 * @param int   $options json_encode options.
	 * @return string|false
	 */
	function wp_json_encode( $data, $options = 0 ) {
		return json_encode( $data, $options ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Mirrors core's implementation.
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.textFound
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text   Text to translate and escape.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * @param string $text   Text to translate and escape.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_attr__( $text, $domain = 'default' ) {
		return esc_attr( $text );
	}
}

if ( ! function_exists( '_e' ) ) {
	/**
	 * @param string $text   Text to translate and echo.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function _e( $text, $domain = 'default' ) {
		echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Mirrors core; callers escape.
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * @param string $text   Text to translate, escape and echo.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	/**
	 * @param string $text   Text to translate, escape and echo.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}
