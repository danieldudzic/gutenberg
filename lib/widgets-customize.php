<?php
/**
 * Bootstrapping the Gutenberg widgets editor in the customizer.
 *
 * @package gutenberg
 */

/**
 * Gutenberg's Customize Register.
 *
 * Adds a section to the Customizer for editing widgets with Gutenberg.
 *
 * @param \WP_Customize_Manager $manager An instance of the class that controls most of the Theme Customization API for WordPress 3.4 and newer.
 */
function gutenberg_widgets_customize_register( $manager ) {
	global $wp_registered_sidebars;

	if ( ! gutenberg_use_widgets_block_editor() ) {
		return;
	}

	require_once __DIR__ . '/class-wp-sidebar-block-editor-control.php';

	foreach ( $manager->sections() as $section ) {
		if ( $section instanceof WP_Customize_Sidebar_Section ) {
			$section->description = '';
		}
	}
	foreach ( $manager->controls() as $control ) {
		if (
			$control instanceof WP_Widget_Area_Customize_Control ||
			$control instanceof WP_Widget_Form_Customize_Control
		) {
			$manager->remove_control( $control->id );
		}
	}

	foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
		$manager->add_setting(
			"sidebars_widgets[$sidebar_id]",
			array(
				'capability' => 'edit_theme_options',
				'transport'  => 'postMessage',
			)
		);

		$manager->add_control(
			new WP_Sidebar_Block_Editor_Control(
				$manager,
				"sidebars_widgets[$sidebar_id]",
				array(
					'section'    => "sidebar-widgets-$sidebar_id",
					'settings'   => "sidebars_widgets[$sidebar_id]",
					'sidebar_id' => $sidebar_id,
				)
			)
		);
	}
}

/**
 * Swaps the customizer setting's sanitize_callback and sanitize_js_callback
 * arguments with our own implementation that adds raw_instance to the sanitized
 * value. This is only done if the widget has declared that it supports raw
 * instances via the show_instance_in_rest flag. This lets the block editor use
 * raw_instance to create blocks.
 *
 * When merged to Core, these changes should be made to
 * WP_Customize_Widgets::sanitize_widget_instance and
 * WP_Customize_Widgets::sanitize_widget_js_instance.
 *
 * @param array  $args Array of Customizer setting arguments.
 * @param string $id   Widget setting ID.
 */
function gutenberg_widgets_customize_add_unstable_instance( $args, $id ) {
	if ( preg_match( '/^widget_(?P<id_base>.+?)(?:\[(?P<widget_number>\d+)\])?$/', $id, $matches ) ) {
		$id_base = $matches['id_base'];

		$args['sanitize_callback'] = function( $value ) use ( $id_base ) {
			global $wp_customize;

			if ( isset( $value['raw_instance'] ) ) {
				$widget_object = gutenberg_get_widget_object( $id_base );
				if ( ! empty( $widget_object->show_instance_in_rest ) ) {
					return $value['raw_instance'];
				}
			}

			return $wp_customize->widgets->sanitize_widget_instance( $value );
		};

		$args['sanitize_js_callback'] = function( $value ) use ( $id_base ) {
			global $wp_customize;

			$sanitized_value = $wp_customize->widgets->sanitize_widget_js_instance( $value );

			$widget_object = gutenberg_get_widget_object( $id_base );
			if ( ! empty( $widget_object->show_instance_in_rest ) ) {
				$sanitized_value['raw_instance'] = (object) $value;
			}

			return $sanitized_value;
		};
	}

	return $args;
}

if ( gutenberg_is_experiment_enabled( 'gutenberg-widgets-in-customizer' ) ) {
	add_action( 'customize_register', 'gutenberg_widgets_customize_register' );
	add_filter( 'widget_customizer_setting_args', 'gutenberg_widgets_customize_add_unstable_instance', 10, 2 );
}
