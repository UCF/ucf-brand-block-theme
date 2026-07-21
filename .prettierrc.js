/**
 * Prettier configuration.
 *
 * Re-exports the WordPress shared config (tabs, single quotes, 80-col) so JS,
 * JSON, and YAML formatting matches the WordPress coding standards. Shipped with
 * @wordpress/scripts, so no extra dependency is required to run `npm run format`.
 *
 * @package ucf-brand-block-theme
 */

module.exports = require( '@wordpress/prettier-config' );
