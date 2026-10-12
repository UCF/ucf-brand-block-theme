/**
 * The home hero's background video: loaded on wide screens only, with a pause button.
 *
 * PERF: includes/home-hero.php sends the video with no `src`, so phones never download it.
 * A11Y: core's Cover loops its video with no controls, which fails WCAG 2.2.2 (Pause, Stop,
 * Hide). The button here is the control it is missing.
 */
( function () {
	'use strict';

	const LABEL_PAUSE = 'Pause background video';
	const LABEL_PLAY = 'Play background video';

	// SYNC: the class templates/front-page.html puts on the cover.
	const band = document.querySelector( '.brand-home-hero__band' );

	// CONTEXT: uploaded files only. A URL background renders an iframe here instead, which
	// this deliberately does not handle — the hero does not use embeds.
	const video = band && band.querySelector( 'video' );

	if ( ! video ) {
		return;
	}

	// SYNC: $breakpoint-video in src/scss/_variables.scss.
	const wide = window.matchMedia( '(min-width: 768px)' );

	// A11Y: a visitor who asked for less motion gets the still frame until they press play.
	let paused = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = 'brand-home-hero__toggle';
	button.innerHTML =
		'<svg class="brand-home-hero__icon-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>' +
		'<svg class="brand-home-hero__icon-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>';

	// SYNC: the attribute includes/home-hero.php parks the source in.
	function load() {
		const src = video.getAttribute( 'data-brand-src' );

		if ( src && ! video.getAttribute( 'src' ) ) {
			video.preload = 'auto';
			video.src = src;
		}
	}

	function render() {
		band.classList.toggle( 'is-video-paused', paused );
		button.setAttribute( 'aria-label', paused ? LABEL_PLAY : LABEL_PAUSE );
	}

	function sync() {
		render();

		// WHY: narrowed after loading, the video is hidden — stop decoding what nobody sees.
		if ( ! wide.matches ) {
			video.pause();
			return;
		}

		load();

		if ( paused ) {
			video.pause();
		} else {
			// FIX: a blocked play() left the button saying "Pause" over a stopped video. WHY:
			// NotAllowedError only — our own pause() aborts play() too, and that is not a block.
			video.play().catch( ( error ) => {
				if ( error && 'NotAllowedError' === error.name ) {
					paused = true;
					render();
				}
			} );
		}
	}

	button.addEventListener( 'click', () => {
		paused = ! paused;
		sync();
	} );

	wide.addEventListener( 'change', sync );

	band.appendChild( button );
	sync();
} )();
