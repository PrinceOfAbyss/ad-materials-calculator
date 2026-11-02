<?php
/**
 * Bathroom and shower preset
 *
 * @package Ad_Materials_Calculator
 */

ad_materials_calc_register_area_preset( 'bathroom_shower', function () {

    return [
        'dimensions_note'      => __( 'Build the project surface by surface - start with the shower, add what the room needs.', 'ad-materials-calculator' ),
        'known_area_note'      => __( 'Enter the combined area you plan to finish. We apply the material allowance automatically.', 'ad-materials-calculator' ),
        'sheen_note'           => __( 'Choosing Matte? Every matte build starts with Armourcoat Satin as its base coat - the satin base is still in your kit, so you can keep a satin sheen if you change your mind.', 'ad-materials-calculator' ),
        'protection_note'      => __( 'The level sets the thickness of the build: Maximum simply adds more material and sealer to your kit for harder-working surfaces. Nothing else changes.', 'ad-materials-calculator' ),
        'heading'              => __( 'Price your complete shower & bathroom system', 'ad-materials-calculator' ),
        'known_mode_label'     => __( 'I know my total sq ft', 'ad-materials-calculator' ),
        'title'                => __( 'Your shower & bathroom kit', 'ad-materials-calculator' ), 'project_label' => __( 'Bathroom / Shower', 'ad-materials-calculator' ),
        'steps'                => 5, 'route'                                                                   => 'wet_wall',
        'dimension_mode_label' => __( 'Use shower + floor dimensions', 'ad-materials-calculator' ),
        'known_note'           => __( 'Enter wet walls and floors separately so the calculator can include the correct wet-area and anti-slip products.', 'ad-materials-calculator' ),
        'known_fields'         => [
            'wet_wall'  => ['key' => 'known_walls', 'label' => __( 'Walls + ceiling', 'ad-materials-calculator' ), 'route' => 'wet_wall'],
            'wet_floor' => ['key' => 'known_floors', 'label' => __( 'Floors', 'ad-materials-calculator' ), 'route' => 'wet_floor'],
        ],
        'route_labels'         => ['wet_wall' => __( 'Wet walls + ceiling', 'ad-materials-calculator' ), 'wet_floor' => __( 'Wet floors', 'ad-materials-calculator' )],
        'sheens'               => ['Matte', 'Satin'], 'default_sheen'                                          => 'Satin',
        'sheen_copy'           => ['Matte' => __( 'Quiet, low reflection - over a satin base', 'ad-materials-calculator' ), 'Satin' => __( 'Softly reflective - wet-area standard', 'ad-materials-calculator' )],
        'standard_copy'        => __( 'For everyday family bathrooms - daily showers, routine cleaning. The full wet-area build, sized for residential use.', 'ad-materials-calculator' ),
        'maximum_copy'         => __( 'For hard-working wet rooms - steam showers, rentals and commercial use. More build material and sealer for a thicker armor.', 'ad-materials-calculator' ),
        'surfaces'             => [
            'shower'   => [
                'summary_label' => __( 'Shower', 'ad-materials-calculator' ),
                'note'          => __( 'Calculates 3 walls + ceiling - assumes one side is glass or door.', 'ad-materials-calculator' ),
                'label'         => __( 'Shower - walls + ceiling', 'ad-materials-calculator' ), 'checked' => true, 'route' => 'wet_wall',
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 3 ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 5 ), ad_materials_calc_preset_field( 'height', __( 'Height', 'ad-materials-calculator' ), 8 )],
                'area_callback' => 'ad_materials_calc_bathroom_shower_area', 'preview_geometry'         => 'shower',
                'note'          => __( 'Calculates three walls plus the ceiling and assumes one side is glass or a door.', 'ad-materials-calculator' ),
            ],
            'pan'      => [
                'summary_label'    => __( 'Shower pan', 'ad-materials-calculator' ),
                'summary_with'     => 'shower',
                'summary_included' => __( 'pan included', 'ad-materials-calculator' ),
                'note'             => __( 'Calculated from your shower footprint - finished with the Anti-Slip R-11 topcoat.', 'ad-materials-calculator' ),
                'label'            => __( 'Shower pan', 'ad-materials-calculator' ), 'checked' => true, 'route'               => 'wet_floor',
                'fields'           => [], 'field_source'                                     => 'shower', 'required_fields' => ['length', 'width'],
                'terms'            => [[1, 'length', 'width']],
                'note'             => __( 'Calculated from the shower footprint and finished with the Anti-Slip R-11 topcoat.', 'ad-materials-calculator' ),
            ],
            'bathroom' => [
                'summary_label'    => __( 'Bathroom walls + floor, beyond the shower', 'ad-materials-calculator' ),
                'summary_addition' => __( 'bathroom beyond the shower', 'ad-materials-calculator' ),
                'summary_fields'   => [],
                'note'             => __( 'Carry the same surface across the vanity walls and the bathroom floor - one shell, no transitions, no grout.', 'ad-materials-calculator' ),
                'label'            => __( 'Bathroom - walls + floor, beyond the shower', 'ad-materials-calculator' ), 'checked' => false,
                'fields'           => [ad_materials_calc_preset_field( 'wall_width', __( 'Total wall width', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'wall_height', __( 'Wall height', 'ad-materials-calculator' ), 8 ), ad_materials_calc_preset_field( 'floor_length', __( 'Floor length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'floor_width', __( 'Floor width', 'ad-materials-calculator' ) )],
                'parts'            => [
                    'bathroom_wall'  => ['label' => __( 'Bathroom walls', 'ad-materials-calculator' ), 'route' => 'wet_wall', 'terms' => [[1, 'wall_width', 'wall_height']]],
                    'bathroom_floor' => ['label' => __( 'Bathroom floor', 'ad-materials-calculator' ), 'route' => 'wet_floor', 'terms' => [[1, 'floor_length', 'floor_width']]],
                ],
            ],
        ],
    ];
} );

function ad_materials_calc_build_bathroom_shower_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'bathroom_shower', $payload );
}

function ad_materials_calc_bathroom_shower_area( $key, array $payload ) {

    $length = ad_materials_calc_area_positive_float( $payload[$key . '_length'] ?? 0 );
    $width  = ad_materials_calc_area_positive_float( $payload[$key . '_width'] ?? 0 );
    $height = ad_materials_calc_area_positive_float( $payload[$key . '_height'] ?? 0 );

    return ( 2 * $length + 2 * $width - min( $length, $width ) ) * $height + $length * $width;
}