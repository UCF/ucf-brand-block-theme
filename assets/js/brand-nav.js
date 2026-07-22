/**
 * Brand drawer navigation.
 *
 * The primary nav is rendered server-side from the ordered brand sections — one link
 * per numbered top-level page (see ucf-brand/section-nav in functions.php). Sub-navigation
 * is never authored: it is derived at runtime from the H2s of the page you are currently
 * on, injected beneath the matching top-level item, and highlighted as those H2s scroll
 * through the viewport.
 *
 * The drawer's sticky behavior is pure CSS (see src/scss/_drawer.scss). Nothing here
 * positions it.
 */
( function () {
	'use strict';

	var shell = document.querySelector( '.brand-shell' );

	if ( ! shell ) {
		return;
	}

	var sidebar = shell.querySelector( '.brand-sidebar' );
	var content = shell.querySelector( '.brand-content' );
	var reduceMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	// Link glyph appended to each H2. aria-hidden — the anchor carries its own label.
	var LINK_ICON =
		'<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">' +
		'<path fill="currentColor" d="M3.9 12a3.1 3.1 0 0 1 3.1-3.1h4V7H7a5 5 0 0 0 0 10h4v-1.9H7A3.1 3.1 0 0 1 3.9 12zm4.1 1h8v-2H8v2zm9-6h-4v1.9h4a3.1 3.1 0 0 1 0 6.2h-4V17h4a5 5 0 0 0 0-10z"/>' +
		'</svg>';

	/**
	 * Normalize a path for comparison: strip the trailing slash and lowercase it.
	 *
	 * @param {string} path Raw pathname.
	 * @return {string} Comparable path.
	 */
	function normalizePath( path ) {
		return path.replace( /\/+$/, '' ).toLowerCase();
	}

	/**
	 * Turn heading text into a URL-safe id.
	 *
	 * @param {string} value Heading text.
	 * @return {string} Slug.
	 */
	function slugify( value ) {
		return value
			.toLowerCase()
			.trim()
			.replace( /[^a-z0-9\s-]/g, '' )
			.replace( /\s+/g, '-' )
			.replace( /-+/g, '-' );
	}

	/**
	 * Give a heading a stable, unique id, preserving any id the author already set.
	 *
	 * @param {HTMLElement} heading Heading element.
	 * @param {number}      index   Position, used when the heading has no text.
	 * @return {string} The heading's id.
	 */
	function ensureHeadingId( heading, index ) {
		if ( heading.id ) {
			return heading.id;
		}

		var base =
			slugify( heading.textContent || '' ) || 'section-' + ( index + 1 );
		var candidate = base;
		var suffix = 1;

		while ( document.getElementById( candidate ) ) {
			suffix += 1;
			candidate = base + '-' + suffix;
		}

		heading.id = candidate;

		return candidate;
	}

	/**
	 * Locate the top-level nav item for the page being viewed.
	 *
	 * ucf_brand_render_section_nav() already flags the current item server-side with
	 * `.is-current` / aria-current="page", so trust that first and only fall back to
	 * comparing pathnames when it is absent (unusual permalinks, cached markup).
	 *
	 * @return {HTMLElement|null} The matching <li>, or null.
	 */
	function findCurrentNavItem() {
		var nav = sidebar && sidebar.querySelector( '.brand-nav' );

		if ( ! nav ) {
			return null;
		}

		var flagged = nav.querySelector(
			'.brand-nav__item.is-current, [aria-current="page"]'
		);

		if ( flagged ) {
			return flagged.closest( '.brand-nav__item' );
		}

		var here = normalizePath( window.location.pathname );
		var links = Array.prototype.slice.call(
			nav.querySelectorAll( 'a[href]' )
		);
		var match = null;

		links.forEach( function ( link ) {
			var linkPath;

			try {
				linkPath = normalizePath(
					new URL( link.href, window.location.origin ).pathname
				);
			} catch ( e ) {
				return;
			}

			if ( linkPath === here ) {
				match = link.closest( '.brand-nav__item' );
			}
		} );

		return match;
	}

	/**
	 * Build the sub-nav list from the current page's H2s.
	 *
	 * @return {{list: HTMLElement, headings: HTMLElement[]}|null} List and targets.
	 */
	function buildSubnav() {
		var headings = Array.prototype.slice.call(
			content.querySelectorAll( 'h2' )
		);

		if ( ! headings.length ) {
			return null;
		}

		var list = document.createElement( 'ul' );
		list.className = 'brand-subnav';

		headings.forEach( function ( heading, index ) {
			var id = ensureHeadingId( heading, index );
			var item = document.createElement( 'li' );
			var link = document.createElement( 'a' );

			item.className = 'brand-subnav__item';
			link.className = 'brand-subnav__link';
			link.href = '#' + id;
			link.textContent = ( heading.textContent || '' ).trim();

			item.appendChild( link );
			list.appendChild( item );
		} );

		return { list: list, headings: headings };
	}

	/**
	 * Highlight the sub-nav link whose heading is currently in view.
	 *
	 * @param {HTMLElement[]} headings Observed headings.
	 * @param {HTMLElement}   list     The sub-nav list.
	 */
	function watchHeadings( headings, list ) {
		var links = Array.prototype.slice.call(
			list.querySelectorAll( '.brand-subnav__link' )
		);

		function activate( id ) {
			links.forEach( function ( link ) {
				link.classList.toggle(
					'is-active',
					link.getAttribute( 'href' ) === '#' + id
				);
			} );
		}

		// The band runs from 30% to 45% down the viewport. A heading is "current" while
		// it sits in that band, which reads as the reader's focal point.
		var observer = new IntersectionObserver(
			function ( entries ) {
				var visible = entries
					.filter( function ( entry ) {
						return entry.isIntersecting;
					} )
					.sort( function ( a, b ) {
						return b.intersectionRatio - a.intersectionRatio;
					} );

				if ( visible.length && visible[ 0 ].target.id ) {
					activate( visible[ 0 ].target.id );
				}
			},
			{
				rootMargin: '-30% 0px -55% 0px',
				threshold: [ 0, 0.25, 0.6, 1 ],
			}
		);

		headings.forEach( function ( heading ) {
			observer.observe( heading );
		} );

		// Nothing is in the band before the reader scrolls, so seed the first item.
		activate( headings[ 0 ].id );

		list.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( '.brand-subnav__link' );

			if ( link ) {
				activate( link.getAttribute( 'href' ).slice( 1 ) );
			}
		} );
	}

	/**
	 * Wire the off-canvas drawer used below the 960px breakpoint.
	 */
	function initMobileDrawer() {
		var toggle = document.querySelector( '.brand-mobile-bar__toggle' );
		var close = sidebar && sidebar.querySelector( '.brand-sidebar__close' );
		var scrim = document.querySelector( '.brand-scrim' );

		if ( ! toggle || ! sidebar || ! scrim ) {
			return;
		}

		function openDrawer() {
			sidebar.classList.add( 'is-open' );
			scrim.hidden = false;
			toggle.setAttribute( 'aria-expanded', 'true' );
			window.requestAnimationFrame( function () {
				scrim.classList.add( 'is-open' );
			} );

			if ( close ) {
				close.focus();
			}
		}

		function closeDrawer( returnFocus ) {
			sidebar.classList.remove( 'is-open' );
			scrim.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			window.setTimeout(
				function () {
					scrim.hidden = true;
				},
				reduceMotion ? 0 : 250
			);

			if ( returnFocus ) {
				toggle.focus();
			}
		}

		toggle.addEventListener( 'click', openDrawer );
		scrim.addEventListener( 'click', function () {
			closeDrawer( true );
		} );

		if ( close ) {
			close.addEventListener( 'click', function () {
				closeDrawer( true );
			} );
		}

		document.addEventListener( 'keydown', function ( event ) {
			if (
				event.key === 'Escape' &&
				sidebar.classList.contains( 'is-open' )
			) {
				closeDrawer( true );
			}
		} );

		// Following a link inside the drawer navigates or jumps — either way the
		// drawer has served its purpose and should get out of the way.
		sidebar.addEventListener( 'click', function ( event ) {
			if (
				event.target.closest( 'a[href]' ) &&
				sidebar.classList.contains( 'is-open' )
			) {
				closeDrawer( false );
			}
		} );
	}

	/**
	 * Copy text to the clipboard, preferring the async API and falling back to a hidden
	 * textarea + execCommand for insecure contexts / older browsers.
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
	 * Turn every content H2 into a jump link: append a link anchor, and make a click on the
	 * heading (or the anchor) update the URL to the heading's #id and copy that link.
	 */
	function initHeadingLinks() {
		var headings = Array.prototype.slice.call(
			content.querySelectorAll( 'h2' )
		);

		if ( ! headings.length ) {
			return;
		}

		var live = document.createElement( 'div' );
		live.className = 'brand-visually-hidden';
		live.setAttribute( 'aria-live', 'polite' );
		document.body.appendChild( live );

		headings.forEach( function ( heading, index ) {
			var id = ensureHeadingId( heading, index );
			var anchor = document.createElement( 'a' );

			anchor.className = 'brand-heading__anchor';
			anchor.href = '#' + id;
			anchor.setAttribute( 'aria-label', 'Copy link to this section' );
			anchor.innerHTML = LINK_ICON;
			heading.appendChild( anchor );
		} );

		function activate( heading ) {
			var id = heading.id;

			if ( ! id ) {
				return;
			}

			var url =
				window.location.origin + window.location.pathname + '#' + id;

			// Assigning the hash updates the URL and jumps, honouring the CSS
			// scroll-padding-top and scroll-behavior. Re-scroll when already there.
			if ( window.location.hash === '#' + id ) {
				heading.scrollIntoView();
			} else {
				window.location.hash = id;
			}

			copyText( url, function () {
				live.textContent = 'Link copied to clipboard';
				heading.classList.add( 'is-link-copied' );
				window.setTimeout( function () {
					heading.classList.remove( 'is-link-copied' );
				}, 1600 );
			} );
		}

		content.addEventListener( 'click', function ( event ) {
			var heading = event.target.closest( 'h2' );
			var link = event.target.closest( 'a[href]' );

			// If the author linked the heading text, do not hijack that navigation.
			// Only intercept clicks on our appended anchor icon.
			if ( link && ! link.classList.contains( 'brand-heading__anchor' ) ) {
				return;
			}

			if ( ! heading || ! content.contains( heading ) ) {
				return;
			}

			// Leave modified clicks (open-in-tab, etc.) to the browser.
			if (
				event.metaKey ||
				event.ctrlKey ||
				event.shiftKey ||
				event.altKey
			) {
				return;
			}

			// A body click should not fight an in-progress text selection.
			if ( ! event.target.closest( '.brand-heading__anchor' ) ) {
				var selection = window.getSelection && window.getSelection();

				if ( selection && ! selection.isCollapsed ) {
					return;
				}
			}

			event.preventDefault();
			activate( heading );
		} );
	}

	if ( content ) {
		initHeadingLinks();

		if ( sidebar ) {
			var currentItem = findCurrentNavItem();

			if ( currentItem ) {
				currentItem.classList.add( 'is-current' );

				var subnav = buildSubnav();

				if ( subnav ) {
					currentItem.appendChild( subnav.list );
					watchHeadings( subnav.headings, subnav.list );
				}
			}
		}
	}

	initMobileDrawer();
} )();
