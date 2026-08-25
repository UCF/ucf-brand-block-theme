/**
 * Color Swatch — click-to-copy on the HEX line (progressive enhancement).
 *
 * The same gesture as the content headings, which is deliberate: hovering an H2 fades in a
 * link icon and clicking it copies the section's URL (`initHeadingLinks()` in
 * `src/js/brand-nav.js`). Here, hovering the HEX line fades in a clipboard icon and clicking
 * it copies the hex. One interaction to learn, two places it works.
 *
 * The saved markup is a plain `<p class="brand-swatch__value">` whose lines are text nodes
 * separated by `<br>`. This script wraps the `HEX #XXXXXX` node in a span and appends the
 * icon button to it — nothing about what is saved changes.
 *
 * Doing it at runtime rather than in `save()` is deliberate on two counts:
 *
 * 1. The markup already on every page stays exactly what it was, so no swatch sitting in a
 *    page is invalidated and the block needs no `deprecated` entry — the same reason
 *    `labelInk` adds no class when unset. `patterns/groups/color-swatches.php` hand-writes
 *    this markup too, and it must match `save()` byte for byte.
 * 2. A copy button is worth nothing without JS. Saving one into the markup would ship a
 *    control that looks live and does nothing whenever the script fails to load; building it
 *    from the script means the affordance and the behavior arrive together or not at all.
 *
 * Enqueued as the block's `viewScript`, so WordPress loads it only on pages that use the
 * block, and never in the editor.
 *
 * This file must not reference theme functions or paths — it ships with the block.
 */
( function () {
	'use strict';

	/**
	 * The saved HEX line, e.g. `HEX #EDB80C`. Group 1 is what gets copied — the value alone,
	 * without the label, because what an author wants on the clipboard is the color.
	 *
	 * Three to eight digits rather than a flat six: `derivedFromColor()` writes six, but a
	 * HEX typed by hand may be short-form or carry an alpha pair.
	 */
	var HEX_LINE = /^\s*HEX\s+(#?[0-9A-Fa-f]{3,8})\s*$/;

	/**
	 * Clipboard, drawn to sit beside the link icon in brand-nav.js without looking like a
	 * different set: same 24-unit box, same `currentColor`, same `aria-hidden` (the button
	 * carries the name). Stroked rather than filled because a solid glyph at this size reads
	 * as a blob against a saturated swatch.
	 */
	var CLIPBOARD_ICON =
		'<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" ' +
		'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" ' +
		'stroke-linejoin="round">' +
		'<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>' +
		'<rect x="8" y="2" width="8" height="4" rx="1"/>' +
		'</svg>';

	/** How long the "Copied" bubble stays up. Matches the heading anchors. */
	var DONE_MS = 1600;

	/** The one polite live region, created on first use. */
	var live = null;

	/**
	 * Copy text to the clipboard, preferring the async API and falling back to a hidden
	 * textarea for insecure contexts and older browsers.
	 *
	 * Deliberately duplicated from `src/js/brand-nav.js` rather than shared: a block source
	 * has to lift into a plugin unchanged, so it may not import from the theme. The fallback
	 * is not optional cruft — `navigator.clipboard` is undefined on any plain-http host, which
	 * describes most local stacks, and without it the whole affordance silently disappears
	 * there.
	 *
	 * @param {string}   text   The string to copy.
	 * @param {Function} onDone Called once, only if the copy succeeded.
	 */
	function copyText( text, onDone ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( onDone, function () {
				if ( fallbackCopy( text ) ) {
					onDone();
				}
			} );
		} else if ( fallbackCopy( text ) ) {
			onDone();
		}
	}

	/**
	 * @param {string} text Text to copy.
	 * @return {boolean} Whether the copy succeeded.
	 */
	function fallbackCopy( text ) {
		var ok = false;

		try {
			var field = document.createElement( 'textarea' );
			field.value = text;
			field.setAttribute( 'readonly', '' );
			field.style.position = 'fixed';
			field.style.top = '-1000px';
			document.body.appendChild( field );
			field.select();
			ok = document.execCommand( 'copy' );
			document.body.removeChild( field );
		} catch ( e ) {
			ok = false;
		}

		return ok;
	}

	/**
	 * Announce a copy to assistive technology.
	 *
	 * The visible confirmation is a bubble drawn with CSS `content`, which is not reliably in
	 * the accessibility tree, so the news goes through a live region — the same arrangement
	 * brand-nav.js uses for "Link copied to clipboard".
	 *
	 * @param {string} message Text to announce.
	 */
	function announce( message ) {
		if ( ! live ) {
			live = document.createElement( 'div' );
			live.className = 'brand-visually-hidden';
			live.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( live );
		}

		live.textContent = message;
	}

	/**
	 * Wrap one `HEX …` text node in a clickable line carrying the copy button.
	 *
	 * @param {Text}   node The text node to wrap.
	 * @param {string} hex  The value to copy.
	 */
	function enhanceLine( node, hex ) {
		var line = document.createElement( 'span' );
		var button = document.createElement( 'button' );
		var timer = null;

		line.className = 'brand-swatch__hex';
		line.appendChild( document.createTextNode( node.nodeValue ) );

		button.type = 'button';
		button.className = 'brand-swatch__copy';
		button.innerHTML = CLIPBOARD_ICON;
		// The icon has no text of its own, so this is the button's whole accessible name.
		button.setAttribute( 'aria-label', 'Copy ' + hex + ' to the clipboard' );
		line.appendChild( button );

		/**
		 * Copy, then hold the icon and its "Copied" bubble up for a moment. `is-copied` on
		 * the line is what the CSS keys off, exactly as `is-link-copied` on the heading does.
		 */
		function activate() {
			copyText( hex, function () {
				line.classList.add( 'is-copied' );
				announce( hex + ' copied to clipboard' );

				window.clearTimeout( timer );
				timer = window.setTimeout( function () {
					line.classList.remove( 'is-copied' );
				}, DONE_MS );
			} );
		}

		// The whole line is the target, not just the icon — the icon is the affordance that
		// says so. One listener covers both because the button sits inside the line.
		line.addEventListener( 'click', function ( event ) {
			// Copying should not fight an in-progress text selection: someone dragging across
			// the hex to select it wants the selection, not the clipboard. Clicking the icon
			// is unambiguous, so it is exempt. Same guard as the headings.
			if ( ! event.target.closest( '.brand-swatch__copy' ) ) {
				var selection = window.getSelection && window.getSelection();

				if ( selection && ! selection.isCollapsed ) {
					return;
				}
			}

			activate();
		} );

		node.parentNode.replaceChild( line, node );
	}

	function init() {
		var values = document.querySelectorAll( '.brand-swatch__value' );

		Array.prototype.forEach.call( values, function ( value ) {
			// A static copy: replacing a child mutates the live childNodes list underneath us.
			var nodes = Array.prototype.slice.call( value.childNodes );

			nodes.forEach( function ( node ) {
				if ( node.nodeType !== 3 ) {
					return;
				}

				var match = HEX_LINE.exec( node.nodeValue );

				if ( match ) {
					enhanceLine( node, match[ 1 ] );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();