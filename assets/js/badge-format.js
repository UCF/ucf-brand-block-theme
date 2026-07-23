/**
 * UCF Brand Badge — rich-text inline formats.
 *
 * Applies a small, uppercase "badge" to a run of text inside any RichText
 * (Paragraph, Heading, List item, …). Each tone wraps the selection in
 * <span class="badge…">…</span>; tones are mutually exclusive.
 *
 * UI: a single "Badge" button in the formatting toolbar opens a swatch popover —
 * built from the same ColorPalette component Gutenberg's Highlight dialog uses —
 * to pick a tone (or clear it). To make that work, every tone is still
 * registered as a class-based format (that is what recognizes the span on load
 * and lets us toggle it), but only an inert controller format renders the button.
 *
 * Each swatch shows the tone's background fill, resolved from the theme palette
 * by slug at runtime (see theme.json / src/scss/_badge.scss), so no hex is
 * hard-coded here and a palette change flows through.
 *
 * Ported from ucf-wordpress-block-theme. No build step: this uses the global
 * `wp.*` packages enqueued as script dependencies in functions.php.
 */
( function ( wp ) {
	var registerFormatType = wp.richText.registerFormatType;
	var toggleFormat = wp.richText.toggleFormat;
	var removeFormat = wp.richText.removeFormat;
	var getActiveFormat = wp.richText.getActiveFormat;
	var useAnchor = wp.richText.useAnchor;
	var RichTextToolbarButton = wp.blockEditor.RichTextToolbarButton;
	var Popover = wp.components.Popover;
	var ColorPalette = wp.components.ColorPalette;
	var select = wp.data.select;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;

	// name = format id; className = the class the span carries (and how the
	// format is recognized on load); bg = palette slug of the badge's fill, used
	// for the swatch color; title = swatch label. Tones map to src/scss/_badge.scss.
	var tones = [
		{ name: 'ucf/badge', className: 'badge', bg: 'line', title: __( 'Default', 'ucf-brand-block-theme' ) },
		{ name: 'ucf/badge-gold', className: 'badge-gold', bg: 'gold', title: __( 'Gold', 'ucf-brand-block-theme' ) },
		{ name: 'ucf/badge-blue', className: 'badge-blue', bg: 'link-blue', title: __( 'Blue', 'ucf-brand-block-theme' ) },
		{ name: 'ucf/badge-success', className: 'badge-success', bg: 'success', title: __( 'Success', 'ucf-brand-block-theme' ) },
		{ name: 'ucf/badge-danger', className: 'badge-danger', bg: 'danger', title: __( 'Danger', 'ucf-brand-block-theme' ) },
		{ name: 'ucf/badge-dark', className: 'badge-dark', bg: 'ink', title: __( 'Dark', 'ucf-brand-block-theme' ) },
		{ name: 'ucf/badge-inverse', className: 'badge-inverse', bg: 'white', title: __( 'Inverse', 'ucf-brand-block-theme' ) },
	];

	// Apply one tone to the selection, first clearing every other tone so the
	// set stays mutually exclusive. Pass null to clear all tones.
	function applyTone( value, toneName ) {
		var next = value;
		tones.forEach( function ( tone ) {
			next = removeFormat( next, tone.name );
		} );
		if ( toneName ) {
			next = toggleFormat( next, { type: toneName } );
		}
		return next;
	}

	// The tone currently on the selection, or null.
	function activeTone( value ) {
		for ( var i = 0; i < tones.length; i++ ) {
			if ( getActiveFormat( value, tones[ i ].name ) ) {
				return tones[ i ];
			}
		}
		return null;
	}

	// Resolve a palette slug to its hex from the editor's color settings, so the
	// swatches track theme.json rather than a duplicated hex list.
	function paletteHex( slug ) {
		var settings = select( 'core/block-editor' ).getSettings();
		var palette = ( settings && settings.colors ) || [];
		for ( var i = 0; i < palette.length; i++ ) {
			if ( palette[ i ].slug === slug ) {
				return palette[ i ].color;
			}
		}
		return undefined;
	}

	// Register every tone as a plain class-based format: no toolbar button of
	// its own, but this is what parses/serializes the span and lets the
	// controller toggle it.
	tones.forEach( function ( tone ) {
		registerFormatType( tone.name, {
			title: tone.title,
			tagName: 'span',
			className: tone.className,
		} );
	} );

	// Controller: the single "Badge" toolbar button that opens the swatch grid.
	// It carries a class no element ever has, so it never wraps anything itself —
	// the real tone formats do. Gutenberg only renders `edit` for registered
	// formats, which is why the picker lives on its own format type.
	registerFormatType( 'ucf/badge-picker', {
		title: __( 'Badge', 'ucf-brand-block-theme' ),
		tagName: 'span',
		className: 'ucf-badge-picker',
		edit: function ( props ) {
			var value = props.value;
			var onChange = props.onChange;
			var active = activeTone( value );

			var stateHook = useState( false );
			var isOpen = stateHook[ 0 ];
			var setOpen = stateHook[ 1 ];

			// Anchor the popover to the current selection so it opens under the
			// highlighted text, like core's rich-text popovers — instead of the
			// toolbar-slot mount point at the screen edge.
			var popoverAnchor = useAnchor( {
				editableContentElement: props.contentRef ? props.contentRef.current : undefined,
				settings: { tagName: 'span', className: 'ucf-badge-picker', isActive: !! active },
			} );

			// ColorPalette entries: one swatch per tone, colored by its fill.
			var swatches = tones.map( function ( tone ) {
				return {
					name: tone.title,
					color: paletteHex( tone.bg ),
					slug: tone.bg,
					toneName: tone.name,
				};
			} );

			// ColorPalette speaks in hex; map the chosen color back to its tone
			// (or clear when the swatch is cleared).
			function onSelectColor( color ) {
				var picked = null;
				if ( color ) {
					for ( var i = 0; i < swatches.length; i++ ) {
						if ( swatches[ i ].color === color ) {
							picked = swatches[ i ].toneName;
							break;
						}
					}
				}
				onChange( applyTone( value, picked ) );
			}

			var children = [
				el( RichTextToolbarButton, {
					key: 'button',
					icon: 'tag',
					title: __( 'Badge', 'ucf-brand-block-theme' ),
					isActive: !! active,
					onClick: function () {
						setOpen( ! isOpen );
					},
				} ),
			];

			if ( isOpen ) {
				children.push(
					el(
						Popover,
						{
							key: 'popover',
							className: 'ucf-badge-popover',
							anchor: popoverAnchor,
							placement: 'bottom-start',
							onClose: function () {
								setOpen( false );
							},
						},
						el(
							'div',
							{ className: 'ucf-badge-popover__inner', style: { padding: '12px', maxWidth: '220px' } },
							el(
								'div',
								{
									style: {
										fontSize: '11px',
										fontWeight: 600,
										textTransform: 'uppercase',
										marginBottom: '8px',
									},
								},
								__( 'Badge', 'ucf-brand-block-theme' )
							),
							el( ColorPalette, {
								colors: swatches,
								value: active ? paletteHex( active.bg ) : undefined,
								onChange: onSelectColor,
								disableCustomColors: true,
								clearable: true,
							} )
						)
					)
				);
			}

			return el( Fragment, null, children );
		},
	} );
} )( window.wp );
