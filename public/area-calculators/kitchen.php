<?php
/** Kitchen multi-surface preset */

ad_materials_calc_register_area_preset( 'kitchen', function () {

    return [
        'summary_order'               => ['room', 'counter', 'backsplash', 'floor'],
        'dimensions_note'             => __( 'Build the kitchen surface by surface - start with the counters, add what the room needs. One system covers them all: the same materials, counter to ceiling.', 'ad-materials-calculator' ),
        'known_area_note'             => __( 'Enter the combined area you plan to finish. We apply the material allowance automatically.', 'ad-materials-calculator' ),
        'sheen_note'                  => __( 'Choosing Matte? Every matte build starts with Armourcoat Satin as its base coat - the satin base is still in your kit, so you can keep a satin sheen if you change your mind.', 'ad-materials-calculator' ),
        'protection_note'             => __( 'The level sets the thickness of the build: Maximum simply adds more material and sealer to your kit for harder-working surfaces. Nothing else changes.', 'ad-materials-calculator' ),
        'micromosaic_protection_note' => __( 'Sheen is always matte - sealed and finished with the certified Anti-Slip R-11 topcoat.', 'ad-materials-calculator' ),
        'sheen_copy'                  => ['Matte' => __( 'Quiet, low reflection - over a satin base', 'ad-materials-calculator' ), 'Satin' => __( 'Softly reflective - the kitchen standard', 'ad-materials-calculator' )],
        'dimension_mode_label'        => __( 'Use kitchen dimensions', 'ad-materials-calculator' ),
        'heading'                     => __( 'Price your complete kitchen system', 'ad-materials-calculator' ),
        'known_mode_label'            => __( 'I know my total sq ft', 'ad-materials-calculator' ),
        'title'                       => __( 'Your kitchen kit', 'ad-materials-calculator' ), 'steps' => 5, 'route' => 'counter',
        'sheens'                      => ['Matte', 'Satin'], 'default_sheen'                        => 'Satin',
        'known_note'                  => __( 'Enter each surface separately so its correct recipe is used.', 'ad-materials-calculator' ),
        'known_fields'                => [
            'counter'    => ['key' => 'known_counter', 'label' => __( 'Countertops', 'ad-materials-calculator' ), 'route' => 'counter'],
            'backsplash' => ['key' => 'known_backsplash', 'label' => __( 'Backsplash', 'ad-materials-calculator' ), 'route' => 'counter'],
            'floor'      => ['key' => 'known_floor', 'label' => __( 'Floor', 'ad-materials-calculator' ), 'route' => 'floor'],
            'room'       => ['key' => 'known_room', 'label' => __( 'Walls + ceiling - the full room', 'ad-materials-calculator' ), 'route' => 'wall'],
        ],
        'standard_copy'               => __( 'For everyday home kitchens - daily cooking, cleaning and family traffic.', 'ad-materials-calculator' ),
        'maximum_copy'                => __( 'For hard-working kitchens - rentals, chefs\' homes and commercial prep areas. More build material and sealer for a thicker armor.', 'ad-materials-calculator' ),
        'finishes'                    => [['value' => 'Terrazzo Granitech Micromosaic', 'label' => __( 'Micromosaic', 'ad-materials-calculator' ), 'description' => __( 'Floors only - a delicate terrazzo field underfoot, in twenty stone blends', 'ad-materials-calculator' )]],
        'surfaces'                    => [
            'counter'    => [
                'summary_label' => __( 'Countertops', 'ad-materials-calculator' ),
                'note'          => __( 'Measure along the front edge - standard counters run about 24 inches deep.', 'ad-materials-calculator' ),
                'label'         => __( 'Countertops', 'ad-materials-calculator' ), 'checked' => true, 'route' => 'counter',
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Counter run', 'ad-materials-calculator' ), 18 ), ad_materials_calc_preset_field( 'depth', __( 'Depth', 'ad-materials-calculator' ), 24, 'in' )],
                'terms'         => [[1 / 12, 'length', 'depth']],
                'extra'         => [
                    'summary_label' => __( 'Island / second run', 'ad-materials-calculator' ),
                    'label'         => __( '+ Add an island or second run', 'ad-materials-calculator' ),
                    'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Island length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'width', __( 'Island width', 'ad-materials-calculator' ) )],
                    'terms'         => [[1, 'length', 'width']],
                ],
            ],
            'backsplash' => [
                'summary_label' => __( 'Backsplash', 'ad-materials-calculator' ),
                'overlap_note'  => __( 'Your full walls already include the backsplash - nothing is counted twice.', 'ad-materials-calculator' ),
                'note'          => __( 'Standard splashes run about 18 inches high - going full-height to the cabinets? Just raise the height.', 'ad-materials-calculator' ),
                'label'         => __( 'Backsplash', 'ad-materials-calculator' ), 'checked' => true, 'route' => 'counter',
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Backsplash length', 'ad-materials-calculator' ), 18 ), ad_materials_calc_preset_field( 'height', __( 'Height', 'ad-materials-calculator' ), 18, 'in' )],
                'terms'         => [[1 / 12, 'length', 'height']],
            ],
            'floor'      => [
                'summary_label' => __( 'Kitchen floor', 'ad-materials-calculator' ),
                'note'          => __( 'Continue the same surface underfoot - one plane, no thresholds. Cabinet and island footprints can stay in the math; they become extra margin.', 'ad-materials-calculator' ),
                'label'         => __( 'Floor', 'ad-materials-calculator' ), 'checked' => true, 'route' => 'floor',
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 10 ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 10 )],
                'terms'         => [[1, 'length', 'width']],
                'extra'         => [
                    'summary_label' => __( 'Connected floor area', 'ad-materials-calculator' ),
                    'label'         => __( '+ Add a connected area', 'ad-materials-calculator' ),
                    'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Connected length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'width', __( 'Connected width', 'ad-materials-calculator' ) )],
                    'terms'         => [[1, 'length', 'width']],
                ],
            ],
            'room'       => [
                'summary_label'                 => __( 'Kitchen walls + ceiling', 'ad-materials-calculator' ),
                'summary_label_without_ceiling' => __( 'Kitchen walls', 'ad-materials-calculator' ),
                'note'                          => __( 'The full envelope - all four walls and the ceiling, calculated from room dimensions. No need to subtract cabinets or openings.', 'ad-materials-calculator' ),
                'label'                         => __( 'Walls + ceiling - the full room', 'ad-materials-calculator' ), 'checked' => false, 'route' => 'wall', 'ceiling' => true,
                'fields'                        => [ad_materials_calc_preset_field( 'length', __( 'Room length', 'ad-materials-calculator' ), 10 ), ad_materials_calc_preset_field( 'width', __( 'Room width', 'ad-materials-calculator' ), 10 ), ad_materials_calc_preset_field( 'height', __( 'Wall height', 'ad-materials-calculator' ), 9 )],
                'terms'                         => [[2, 'length', 'height'], [2, 'width', 'height']],
                'extra'                         => [
                    'summary_label' => __( 'Additional wall section', 'ad-materials-calculator' ),
                    'label'         => __( '+ Add an additional wall section', 'ad-materials-calculator' ),
                    'fields'        => [ad_materials_calc_preset_field( 'width', __( 'Extra wall width', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'height', __( 'Extra wall height', 'ad-materials-calculator' ) )],
                    'terms'         => [[1, 'width', 'height']],
                ],
            ],
        ],
    ];
} );

function ad_materials_calc_build_kitchen_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'kitchen', $payload );
}