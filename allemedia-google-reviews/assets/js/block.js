( function ( wp ) {
    const { __ } = wp.i18n;
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
    const { PanelBody, TextControl, RangeControl, Placeholder } = wp.components;
    const { createElement: el, Fragment } = wp.element;

    registerBlockType( 'allemedia/reviews', {
        title: __( 'Allemedia: Opinie Google', 'allemedia-reviews' ),
        description: __( 'Wyświetl sekcję z opiniami klientów z Google.', 'allemedia-reviews' ),
        icon: 'star-filled',
        category: 'widgets',
        attributes: {
            place_id: {
                type: 'string',
                default: ''
            },
            limit: {
                type: 'number',
                default: 12
            },
            show_more_chars: {
                type: 'number',
                default: 220
            }
        },
        edit: ( props ) => {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps( { className: 'amr-reviews-block-placeholder' } );

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __( 'Ustawienia opinii', 'allemedia-reviews' ) },
                        el( TextControl, {
                            label: __( 'Place ID', 'allemedia-reviews' ),
                            value: attributes.place_id,
                            onChange: ( value ) => setAttributes( { place_id: value } ),
                            help: __( 'Pozostaw puste, aby użyć wartości domyślnej.', 'allemedia-reviews' )
                        } ),
                        el( RangeControl, {
                            label: __( 'Limit opinii', 'allemedia-reviews' ),
                            value: attributes.limit,
                            onChange: ( value ) => setAttributes( { limit: value } ),
                            min: 1,
                            max: 20
                        } ),
                        el( RangeControl, {
                            label: __( 'Znaki przed „Pokaż więcej”', 'allemedia-reviews' ),
                            value: attributes.show_more_chars,
                            onChange: ( value ) => setAttributes( { show_more_chars: value } ),
                            min: 80,
                            max: 400,
                            step: 10
                        } )
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(
                        Placeholder,
                        {
                            label: __( 'Allemedia: Opinie Google', 'allemedia-reviews' ),
                            instructions: __( 'Podgląd sekcji będzie widoczny po zapisaniu lub w podglądzie strony.', 'allemedia-reviews' )
                        },
                        el( 'p', null, __( 'Skonfiguruj atrybuty w panelu bocznym.', 'allemedia-reviews' ) )
                    )
                )
            );
        },
        save: () => null
    } );
} )( window.wp );
