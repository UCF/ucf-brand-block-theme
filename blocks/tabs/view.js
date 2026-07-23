/**
 * Tabs — front-end behavior (progressive enhancement).
 *
 * The saved markup is a role-free stack of `.ucf-tabs__label` / `.ucf-tabs__panel`
 * pairs. This script is the ONLY thing that turns that stack into tabs, and only above
 * the tabs breakpoint. Below it — and whenever this script does not run — the stack is
 * the mobile layout: every panel visible, each label a section heading.
 *
 * Enqueued as the block's `viewScript`, so WordPress loads it only on pages that use
 * the block, and never in the editor. Everything is keyed off matchMedia so rotating a
 * tablet or resizing flips modes live, with no reload.
 *
 * This file must not reference theme functions or paths — it ships with the block.
 */
( function () {
	'use strict';

	// Mirrors $breakpoint-tabs in src/scss/_variables.scss. Kept in sync by hand: the
	// grid layout and this script must agree on where tabs engage.
	var TABS_QUERY = '(min-width: 768px)';

	var mql = window.matchMedia( TABS_QUERY );
	var instances = [];
	var uid = 0;

	/**
	 * Give an element a stable, unique id, preserving one the author already set.
	 *
	 * @param {HTMLElement} el       Element to identify.
	 * @param {string}      fallback Base id to build from when it has none.
	 * @return {string} The element's id.
	 */
	function ensureId( el, fallback ) {
		if ( el.id ) {
			return el.id;
		}
		var candidate = fallback;
		while ( document.getElementById( candidate ) ) {
			uid += 1;
			candidate = fallback + '-' + uid;
		}
		el.id = candidate;
		return candidate;
	}

	/**
	 * Collect one tabs instance: its label/panel pairs and current active index.
	 *
	 * @param {HTMLElement} root The `.ucf-tabs` container.
	 * @return {?Object} Instance descriptor, or null if it has no complete pairs.
	 */
	function collect( root ) {
		var labels = Array.prototype.slice.call(
			root.querySelectorAll( ':scope > .ucf-tabs__set > .ucf-tabs__label' )
		);
		var panels = Array.prototype.slice.call(
			root.querySelectorAll( ':scope > .ucf-tabs__set > .ucf-tabs__panel' )
		);

		if ( ! labels.length || labels.length !== panels.length ) {
			return null;
		}

		labels.forEach( function ( label, i ) {
			uid += 1;
			ensureId( label, 'ucf-tab-' + uid );
			ensureId( panels[ i ], 'ucf-panel-' + uid );
		} );

		// Drives the grid columns, so save() never has to count children.
		root.style.setProperty( '--tab-count', String( labels.length ) );

		return { root: root, labels: labels, panels: panels, active: 0 };
	}

	/**
	 * Reflect the active index into ARIA state and panel visibility (tabs mode).
	 *
	 * @param {Object} inst  Instance descriptor.
	 * @param {number} index Index to activate.
	 * @param {boolean} focus Whether to move focus to the new tab.
	 */
	function activate( inst, index, focus ) {
		inst.active = index;
		inst.labels.forEach( function ( label, i ) {
			var selected = i === index;
			label.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
			label.tabIndex = selected ? 0 : -1;
			inst.panels[ i ].hidden = ! selected;
			if ( selected && focus ) {
				label.focus();
			}
		} );
	}

	/**
	 * Keyboard model for the tablist: arrows move and activate, Home/End jump.
	 *
	 * @param {Object}        inst  Instance descriptor.
	 * @param {KeyboardEvent} event Key event on a tab.
	 */
	function onKeydown( inst, event ) {
		var last = inst.labels.length - 1;
		var next = null;

		switch ( event.key ) {
			case 'ArrowRight':
				next = inst.active === last ? 0 : inst.active + 1;
				break;
			case 'ArrowLeft':
				next = inst.active === 0 ? last : inst.active - 1;
				break;
			case 'Home':
				next = 0;
				break;
			case 'End':
				next = last;
				break;
			default:
				return;
		}

		event.preventDefault();
		activate( inst, next, true );
	}

	/**
	 * Switch an instance into tabs mode: tablist roles, keyboard, one panel shown.
	 *
	 * @param {Object} inst Instance descriptor.
	 */
	function enhance( inst ) {
		inst.root.classList.add( 'is-enhanced' );
		inst.root.setAttribute( 'role', 'tablist' );

		inst.labels.forEach( function ( label, i ) {
			label.setAttribute( 'role', 'tab' );
			label.setAttribute( 'aria-controls', inst.panels[ i ].id );
			inst.panels[ i ].setAttribute( 'role', 'tabpanel' );
			inst.panels[ i ].setAttribute( 'aria-labelledby', label.id );
			inst.panels[ i ].tabIndex = 0;

			if ( ! label._ucfBound ) {
				label._ucfBound = true;
				label.addEventListener( 'click', function () {
					if ( ! mql.matches ) {
						return;
					}
					activate( inst, i, false );
				} );
				label.addEventListener( 'keydown', function ( event ) {
					if ( ! mql.matches ) {
						return;
					}
					if ( event.key === 'Enter' || event.key === ' ' ) {
						event.preventDefault();
						activate( inst, i, false );
					} else {
						onKeydown( inst, event );
					}
				} );
			}
		} );

		// Restore the remembered tab (defaults to the first).
		activate( inst, inst.active, false );
	}

	/**
	 * Switch an instance back to the stacked layout: strip every tab affordance so
	 * the ARIA matches what is on screen — a plain list of headings and sections.
	 *
	 * @param {Object} inst Instance descriptor.
	 */
	function reset( inst ) {
		inst.root.classList.remove( 'is-enhanced' );
		inst.root.removeAttribute( 'role' );

		inst.labels.forEach( function ( label, i ) {
			label.removeAttribute( 'role' );
			label.removeAttribute( 'aria-selected' );
			label.removeAttribute( 'aria-controls' );
			label.removeAttribute( 'tabindex' );
			inst.panels[ i ].removeAttribute( 'role' );
			inst.panels[ i ].removeAttribute( 'aria-labelledby' );
			inst.panels[ i ].removeAttribute( 'tabindex' );
			inst.panels[ i ].hidden = false;
		} );
	}

	/**
	 * Apply the mode that matches the current viewport to every instance.
	 */
	function sync() {
		instances.forEach( function ( inst ) {
			if ( mql.matches ) {
				enhance( inst );
			} else {
				reset( inst );
			}
		} );
	}

	function init() {
		var roots = document.querySelectorAll( '.ucf-tabs' );

		Array.prototype.forEach.call( roots, function ( root ) {
			var inst = collect( root );
			if ( inst ) {
				instances.push( inst );
			}
		} );

		if ( ! instances.length ) {
			return;
		}

		sync();
		if ( mql.addEventListener ) {
			mql.addEventListener( 'change', sync );
		} else if ( mql.addListener ) {
			mql.addListener( sync );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
