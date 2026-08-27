/**
 * Testimonials Gutenberg block — editor script.
 *
 * Written against the WordPress-provided `wp` globals directly (no JSX,
 * no build step) so the plugin has zero Node/npm dependency at runtime.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;

	blocks.registerBlockType( 'testimonials-manager/testimonials', {
		title: __( 'Testimonials', 'testimonials-manager' ),
		description: __( 'Display customer testimonials in a grid, carousel, or list.', 'testimonials-manager' ),
		icon: 'format-quote',
		category: 'widgets',
		attributes: {
			layout: { type: 'string', default: 'grid' },
			limit: { type: 'number', default: 6 },
			category: { type: 'string', default: '' },
			featured: { type: 'string', default: '' }
		},

		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var inspector = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Testimonials Settings', 'testimonials-manager' ) },
					el( SelectControl, {
						label: __( 'Layout', 'testimonials-manager' ),
						value: attributes.layout,
						options: [
							{ label: __( 'Grid', 'testimonials-manager' ), value: 'grid' },
							{ label: __( 'Carousel', 'testimonials-manager' ), value: 'carousel' },
							{ label: __( 'List', 'testimonials-manager' ), value: 'list' }
						],
						onChange: function ( value ) {
							setAttributes( { layout: value } );
						}
					} ),
					el( TextControl, {
						label: __( 'Number of testimonials', 'testimonials-manager' ),
						type: 'number',
						value: attributes.limit,
						onChange: function ( value ) {
							setAttributes( { limit: parseInt( value, 10 ) || 6 } );
						}
					} ),
					el( TextControl, {
						label: __( 'Category slug (optional)', 'testimonials-manager' ),
						value: attributes.category,
						onChange: function ( value ) {
							setAttributes( { category: value } );
						}
					} ),
					el( SelectControl, {
						label: __( 'Featured only', 'testimonials-manager' ),
						value: attributes.featured,
						options: [
							{ label: __( 'Any', 'testimonials-manager' ), value: '' },
							{ label: __( 'Featured only', 'testimonials-manager' ), value: 'true' },
							{ label: __( 'Exclude featured', 'testimonials-manager' ), value: 'false' }
						],
						onChange: function ( value ) {
							setAttributes( { featured: value } );
						}
					} )
				)
			);

			var preview = ServerSideRender
				? el( ServerSideRender, {
					block: 'testimonials-manager/testimonials',
					attributes: attributes
				} )
				: el( 'p', {}, __( 'Testimonials preview (layout: ', 'testimonials-manager' ) + attributes.layout + ')' );

			return el( element.Fragment, {}, inspector, preview );
		},

		// Rendering is handled entirely server-side (see TM_Block::render()).
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
