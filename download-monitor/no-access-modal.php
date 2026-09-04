<?php
/**
 * Download Monitor: the no-access modal shell.
 *
 * A true override — DLM has no setting for naming a different modal template, so this
 * forks the plugin's file. It replaces DLM's prefixed Tailwind markup with the theme's own
 * classes, styled in src/scss/_download-gate.scss.
 *
 * SYNC: DLM's JS binds by selector. `#dlm-no-access-modal`, `.dlm-no-access-modal-window`,
 * `.dlm-no-access-modal-overlay` and `.dlm-no-access-modal-close` must survive any
 * redesign, or the modal cannot open or close.
 *
 * SYNC: @version tracks the plugin template this overrides. Downloads → Status compares the
 * two and flags this file when upstream bumps it — that is the cue to re-diff.
 *
 * @package ucf-brand-block-theme
 * @version 5.0.14
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}
?>
<div id="dlm-no-access-modal" class="dlm-no-access-modal ucf-gate">
	<div class="dlm-no-access-modal-overlay ucf-gate__overlay"></div>

	<div class="dlm-no-access-modal-window ucf-gate__window">
		<?php
		// A11Y: aria-labelledby may only reference an element that exists. DLM's modal
		// defaults leave $title empty, so pointing at #modal-title unconditionally gives the
		// dialog a dangling accessible name. Labelling the element directly is the fallback —
		// inventing a heading would be wrong for the members-lock and error modals this same
		// template serves.
		$ucf_brand_gate_has_title = ! empty( $title );
		?>
		<div class="ucf-gate__panel" role="dialog" aria-modal="true"
			<?php if ( $ucf_brand_gate_has_title ) : ?>
				aria-labelledby="modal-title"
			<?php else : ?>
				aria-label="<?php esc_attr_e( 'Download', 'ucf-brand-block-theme' ); ?>"
			<?php endif; ?>
		>
			<?php if ( $ucf_brand_gate_has_title ) : ?>
				<h2 id="modal-title" class="ucf-gate__title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php
			// CONTEXT: $content is the terms body and accept form, already escaped by the
			// extension that produced it, and carries the styles the modal needs.
			?>
			<div class="ucf-gate__body">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ucf-gate__actions">
				<button type="button" class="dlm-no-access-modal-close ucf-gate__close">
					<?php echo esc_html__( 'Close', 'ucf-brand-block-theme' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
