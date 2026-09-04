<?php
/**
 * Download Monitor link template: the UCF download button.
 *
 * Selected by setting Downloads → Settings → Custom Template to `ucf`. The plugin ships no
 * template of this name, so this is an addition rather than a fork of one of its files —
 * nothing upstream to drift from, and a plugin update cannot touch it.
 *
 * WHY: the stock content-download.php appends "(N downloads)" to every label with no
 * setting to disable it. Here the label is the download's own title, so editors set it in
 * the admin with no code.
 *
 * @package ucf-brand-block-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

if ( ! isset( $dlm_download ) || ! $dlm_download ) {
	return esc_html__( 'No download found', 'download-monitor' );
}

$template = __FILE__;

// UPSTREAM: the Gutenberg block passes its own classes through this attribute; without
// this the block's alignment and style settings are dropped.
if ( ! empty( $dlm_attributes['className'] ) ) {
	$attributes['link_attributes']['class'][] = $dlm_attributes['className'];
}

// SYNC: extensions hook these to inject markup around the link. Removing them silently
// breaks any DLM add-on that renders here.
do_action( 'dlm_template_content_before_link', $dlm_download, $attributes, $template );
?>

<div class="wp-block-button">
	<a <?php echo DLM_Utils::generate_attributes( $attributes['link_attributes'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php $dlm_download->the_title(); ?>
	</a>
</div>

<?php
do_action( 'dlm_template_content_after_link', $dlm_download, $attributes, $template );
