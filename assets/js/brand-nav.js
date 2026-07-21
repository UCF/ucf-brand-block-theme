/**
 * Brand drawer navigation.
 *
 * The primary nav is authored one level deep in the Site Editor — one link per
 * top-level brand page. Sub-navigation is never authored: it is derived at runtime
 * from the H2s of the page you are currently on, injected beneath the matching
 * top-level item, and highlighted as those H2s scroll through the viewport.
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
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

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

		var base = slugify( heading.textContent || '' ) || 'section-' + ( index + 1 );
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
	 * Core's navigation block already stamps aria-current="page" server-side when a
	 * link matches the request, so trust that first and only fall back to comparing
	 * pathnames when it is absent (custom links, home page, unusual permalinks).
	 *
	 * @return {HTMLElement|null} The matching <li>, or null.
	 */
	function findCurrentNavItem() {
		var nav = sidebar && sidebar.querySelector( '.brand-nav' );

		if ( ! nav ) {
			return null;
		}

		var flagged = nav.querySelector( '[aria-current="page"]' );

		if ( flagged ) {
			return flagged.closest( '.wp-block-navigation-item' );
		}

		var here = normalizePath( window.location.pathname );
		var links = Array.prototype.slice.call( nav.querySelectorAll( 'a[href]' ) );
		var match = null;

		links.forEach( function ( link ) {
			var linkPath;

			try {
				linkPath = normalizePath( new URL( link.href, window.location.origin ).pathname );
			} catch ( e ) {
				return;
			}

			if ( linkPath === here ) {
				match = link.closest( '.wp-block-navigation-item' );
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
		var headings = Array.prototype.slice.call( content.querySelectorAll( 'h2' ) );

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
		var links = Array.prototype.slice.call( list.querySelectorAll( '.brand-subnav__link' ) );

		function activate( id ) {
			links.forEach( function ( link ) {
				link.classList.toggle( 'is-active', link.getAttribute( 'href' ) === '#' + id );
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
			if ( event.key === 'Escape' && sidebar.classList.contains( 'is-open' ) ) {
				closeDrawer( true );
			}
		} );

		// Following a link inside the drawer navigates or jumps — either way the
		// drawer has served its purpose and should get out of the way.
		sidebar.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( 'a[href]' ) && sidebar.classList.contains( 'is-open' ) ) {
				closeDrawer( false );
			}
		} );
	}

	if ( content && sidebar ) {
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

	initMobileDrawer();
} )();
