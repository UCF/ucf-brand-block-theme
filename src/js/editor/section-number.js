/**
 * The `ucf_brand_number` meta key, and the JS port of its PHP formatter.
 *
 * Shared by the three editor-only consumers that need the page's number: the Brand order
 * panel, the canvas `--brand-section` variable, and the hero's eyebrow binding.
 *
 * `formatSectionNumber()` restates `ucf_brand_format_number()` in includes/sections.php.
 * Two copies of one format can drift — the hazard CLAUDE.md flags over the heading slugs.
 * The blast radius is much smaller here: the front end never runs this code, so drift
 * shows up as a wrong preview, never a wrong page. Keep them in step anyway. PHP is the
 * original.
 */

export const META_KEY = 'ucf_brand_number';

/**
 * Zero-pad a section number for display (1 → "01").
 *
 * @param {number|string} value Raw meta value.
 * @return {string} Zero-padded number, or '' when unset/0.
 */
export function formatSectionNumber( value ) {
	const number = parseInt( value, 10 );

	return number > 0 ? String( number ).padStart( 2, '0' ) : '';
}
