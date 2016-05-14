<?php

namespace Rarst\Fragment_Cache;

/**
 * Cache widgets.
 */
class Widget_Cache extends Fragment_Cache {

	/**
	 * @inheritDoc
	 */
	public function enable() {

		if ( is_admin() ) {
			add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ) );
		} else {
			add_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10, 3 );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function disable() {

		if ( is_admin() ) {
			remove_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ) );
		} else {
			remove_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10 );
		}
	}

	/**
	 * Adds timestamp to widget instance to use as salt.
	 *
	 * @param array $instance Widget instance to modify.
	 *
	 * @return array
	 */
	public function widget_update_callback( $instance ) {

		if ( is_array( $instance ) ) {
			$instance['fc_widget_edited'] = time();
		}

		return $instance;
	}

	/**
	 * Set up and echo widget cache
	 *
	 * @param array  $instance Widget instance data.
	 * @param object $widget   Widget object instance.
	 * @param array  $args     Arguments.
	 *
	 * @return bool false
	 */
	public function widget_display_callback( $instance, $widget, $args ) {

		$edited = isset( $instance['fc_widget_edited'] ) ? $instance['fc_widget_edited'] : '';

		echo $this->fetch(
			$widget->id,
			array(
				'callback' => array( $widget, 'widget' ),
				'args'     => array( $args, $instance ),
			),
			$edited
		);

		return false;
	}

	/**
	 * Generate widget output, capture with buffer and timestamp.
	 *
	 * @param string $name Fragment name.
	 * @param array  $args Arguments.
	 *
	 * @return string
	 */
	protected function callback( $name, $args ) {

		ob_start();
		call_user_func_array( $args['callback'], $args['args'] );

		return ob_get_clean() . $this->get_comment( $name );
	}
}
