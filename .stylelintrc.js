/**
 * Stylelint configuration.
 *
 * WHY: the `scss` preset, not the `scss-stylistic` one wp-scripts defaults to. This theme
 * runs Prettier over `src/scss/` (see `npm run format:check`, enforced in CI alongside this
 * lint), and the stylistic rules contradict it on any declaration long enough to wrap:
 * Prettier's multi-line `calc()` draws seven `@stylistic/*` errors, and stylelint's own
 * `--fix` of it is then rejected by Prettier. One formatter owns whitespace; stylelint keeps
 * every rule about what the CSS *means*.
 *
 * FIX: do not run `stylelint --fix` on the Sass maps in `_compositions.scss` with
 * `@stylistic/indentation` enabled. Its fixer deletes the comment lines inside a map and
 * overwrites the rows below them — it silently turned `"heading": "black"` into
 * `"headin<tab>: <tab><tab>ack"`. That is the other reason the stylistic preset is off here.
 *
 * `selector-class-pattern` is off in the wp-scripts default too, and restated because a
 * project config replaces that default rather than merging with it.
 *
 * `scss/comment-no-empty` is off because the bare `//` lines inside this theme's file-header
 * banners are paragraph spacers, not empty comments. See CLAUDE.md § Comments.
 *
 * SYNC: the two `first-nested` exceptions below exist for the same reason as the preset
 * choice — Prettier deletes a blank line at the start of a block, and both rules demand one
 * there. `except` inverts the rule for that position, so the two tools now agree.
 *
 * @package ucf-brand-block-theme
 */

module.exports = {
	extends: '@wordpress/stylelint-config/scss',
	rules: {
		'selector-class-pattern': null,
		'scss/comment-no-empty': null,
		'rule-empty-line-before': [
			'always',
			{
				except: [ 'first-nested' ],
				ignore: [ 'after-comment' ],
			},
		],
		'at-rule-empty-line-before': [
			'always',
			{
				except: [ 'blockless-after-blockless', 'first-nested' ],
				ignore: [ 'after-comment' ],
			},
		],
	},
};
