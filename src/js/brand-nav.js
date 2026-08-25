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
 * positions it — the one exception is syncDrawerToPageEnd() below, which drives the
 * drawer's own scrollTop (never its position) so the end of the nav lands in view as the
 * page ends. That comment explains why it cannot be CSS.
 */
( function () {
	'use strict';

	const shell = document.querySelector( '.brand-shell' );

	if ( ! shell ) {
		return;
	}

	const sidebar = shell.querySelector( '.brand-sidebar' );
	const content = shell.querySelector( '.brand-content' );
	const reduceMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	// Link glyph appended to each H2. aria-hidden — the anchor carries its own label.
	const LINK_ICON =
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
	 * Read a heading's id.
	 *
	 * Ids are generated server-side during render_block — see includes/headings.php, which
	 * owns the slug so that search can emit `/page/#heading` links that agree with the
	 * page. Nothing here derives one; this only supplies a positional fallback for an H2
	 * that reached the DOM without passing through that filter.
	 *
	 * @param {HTMLElement} heading Heading element.
	 * @param {number}      index   Position within the content area.
	 * @return {string} The heading's id.
	 */
	function headingId( heading, index ) {
		if ( heading.id ) {
			return heading.id;
		}

		// Positional, never derived from the heading text. Building a slug here would put
		// a second implementation of the server's rule in the browser, which is the bug
		// includes/headings.php exists to prevent.
		//
		// Still collision-checked: on a page old enough to predate the render_block filter
		// every H2 lands here at once, and `section-2` is a name an authored anchor could
		// already hold. Positions are unique, so this only guards against ids owned by
		// something other than another heading.
		const base = 'section-' + ( index + 1 );
		let candidate = base;
		let suffix = 1;

		while ( document.getElementById( candidate ) ) {
			suffix += 1;
			candidate = base + '-' + suffix;
		}

		heading.id = candidate;

		return heading.id;
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
		const nav = sidebar && sidebar.querySelector( '.brand-nav' );

		if ( ! nav ) {
			return null;
		}

		const flagged = nav.querySelector(
			'.brand-nav__item.is-current, [aria-current="page"]'
		);

		if ( flagged ) {
			return flagged.closest( '.brand-nav__item' );
		}

		const here = normalizePath( window.location.pathname );
		const links = Array.prototype.slice.call(
			nav.querySelectorAll( 'a[href]' )
		);
		let match = null;

		links.forEach( function ( link ) {
			let linkPath;

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
		const headings = Array.prototype.slice.call(
			content.querySelectorAll( 'h2' )
		);

		if ( ! headings.length ) {
			return null;
		}

		const list = document.createElement( 'ul' );
		list.className = 'brand-subnav';

		headings.forEach( function ( heading, index ) {
			const id = headingId( heading, index );
			const item = document.createElement( 'li' );
			const link = document.createElement( 'a' );

			item.className = 'brand-subnav__item';
			link.className = 'brand-subnav__link';
			link.href = '#' + id;
			link.textContent = ( heading.textContent || '' ).trim();

			item.appendChild( link );
			list.appendChild( item );
		} );

		return { list, headings };
	}

	/**
	 * Highlight the sub-nav link whose heading is currently in view.
	 *
	 * @param {HTMLElement[]} headings Observed headings.
	 * @param {HTMLElement}   list     The sub-nav list.
	 */
	function watchHeadings( headings, list ) {
		const links = Array.prototype.slice.call(
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
		const observer = new window.IntersectionObserver(
			function ( entries ) {
				const visible = entries
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
			const link = event.target.closest( '.brand-subnav__link' );

			if ( link ) {
				activate( link.getAttribute( 'href' ).slice( 1 ) );
			}
		} );
	}

	/**
	 * Scroll the drawer to its end as the page reaches its end.
	 *
	 * The drawer is a viewport-height scroll container pinned below the header, and its
	 * footer (the contact block and version line) sits in flow at the end of the nav. When
	 * the nav is long enough to overflow, that footer is below the drawer's own fold, so a
	 * reader who never scrolls the drawer itself never sees it — and at the bottom of the
	 * page it reads as clipped by the site footer, since that is exactly where the drawer's
	 * box ends.
	 *
	 * CSS cannot express this: a box taller than the viewport can be anchored by its top or
	 * its bottom, never both, so there is no declaration that keeps the rail pinned under the
	 * header *and* reveals its tail at the end of the page. (`position: sticky; bottom: 0`
	 * was tried; per spec `top` wins when the box does not fit, so it resolves back to a top
	 * pin.) So spend the page's last stretch of scroll on the drawer's remaining scroll:
	 * map the final `overflow` pixels of page scroll onto the drawer's 0→overflow range, and
	 * the drawer lands on its footer exactly as the page lands on the site footer.
	 *
	 * Above that stretch the drawer is left alone, so scrolling it by hand still works
	 * everywhere else on the page.
	 */
	function syncDrawerToPageEnd() {
		// Only on the docked rail. Below the breakpoint the drawer is a fixed off-canvas
		// panel with its own scroll and no relationship to page position at all.
		const docked = window.matchMedia( '(min-width: 961px)' );

		function sync() {
			if ( ! docked.matches ) {
				return;
			}

			const overflow = sidebar.scrollHeight - sidebar.clientHeight;

			if ( overflow <= 0 ) {
				return;
			}

			const pageOverflow =
				document.documentElement.scrollHeight - window.innerHeight;

			// The page needs more scroll room than the drawer is borrowing, or the "last
			// stretch" would begin at (or above) the top of the page: the drawer would then
			// load already scrolled, with the masthead and search out of view and no way to
			// scroll the page back far enough to restore them. A short page — a search result
			// with few hits, say — leaves the drawer alone and scrolls on its own.
			if ( pageOverflow <= overflow ) {
				return;
			}

			const toPageEnd = pageOverflow - window.scrollY;

			if ( toPageEnd > overflow ) {
				return;
			}

			// Clamped because rubber-band scrolling reports a negative distance.
			sidebar.scrollTop = Math.min(
				overflow,
				Math.max( 0, overflow - toPageEnd )
			);
		}

		let queued = false;

		function onScroll() {
			// sync() checks this too, but bailing here keeps the off-canvas breakpoint from
			// queueing a frame per scroll event only to discard it.
			if ( ! docked.matches || queued ) {
				return;
			}

			queued = true;
			window.requestAnimationFrame( function () {
				queued = false;
				sync();
			} );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );

		// The sub-nav is injected into the drawer after this runs, which changes the
		// overflow the mapping is built on. Re-run once the layout settles.
		window.addEventListener( 'load', sync );
		sync();
	}

	/**
	 * Wire the off-canvas drawer used below the 960px breakpoint.
	 */
	function initMobileDrawer() {
		const toggle = document.querySelector( '.brand-mobile-bar__toggle' );
		const close =
			sidebar && sidebar.querySelector( '.brand-sidebar__close' );
		const scrim = document.querySelector( '.brand-scrim' );

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
		if (
			window.navigator.clipboard &&
			window.navigator.clipboard.writeText
		) {
			window.navigator.clipboard
				.writeText( text )
				.then( onDone, function () {
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
		let ok = false;

		try {
			const field = document.createElement( 'textarea' );
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
		const headings = Array.prototype.slice.call(
			content.querySelectorAll( 'h2' )
		);

		if ( ! headings.length ) {
			return;
		}

		const live = document.createElement( 'div' );
		live.className = 'brand-visually-hidden';
		live.setAttribute( 'aria-live', 'polite' );
		document.body.appendChild( live );

		headings.forEach( function ( heading, index ) {
			const id = headingId( heading, index );
			const anchor = document.createElement( 'a' );

			anchor.className = 'brand-heading__anchor';
			anchor.href = '#' + id;
			anchor.setAttribute( 'aria-label', 'Copy link to this section' );
			anchor.innerHTML = LINK_ICON;
			heading.appendChild( anchor );
		} );

		function activate( heading ) {
			const id = heading.id;

			if ( ! id ) {
				return;
			}

			const url =
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
			const link = event.target.closest( 'a[href]' );

			// If the author linked the heading text, do not hijack that navigation.
			// Only intercept clicks on our appended anchor icon.
			if (
				link &&
				! link.classList.contains( 'brand-heading__anchor' )
			) {
				return;
			}

			const heading = event.target.closest( 'h2' );

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
				const view = event.target.ownerDocument.defaultView;
				const selection = view.getSelection && view.getSelection();

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
			const currentItem = findCurrentNavItem();

			if ( currentItem ) {
				currentItem.classList.add( 'is-current' );

				const subnav = buildSubnav();

				if ( subnav ) {
					currentItem.appendChild( subnav.list );
					watchHeadings( subnav.headings, subnav.list );
				}
			}
		}
	}

	if ( sidebar ) {
		syncDrawerToPageEnd();
	}

	initMobileDrawer();
} )();
