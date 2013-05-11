<?php

namespace Rarst\Fragment_Cache;

/**
 * Cache widgets.
 */
class Widget_Cache extends Fragment_Cache {

	public function enable() {

		add_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10, 3 );
	}

	public function disable() {

		remove_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10, 3 );
	}

	/**
	 * Set up and echo widget cache
	 *
	 * @param array  $instance
	 * @param object $widget
	 * @param array  $args
	 *
	 * @return bool false
	 */
	public function widget_display_callback( $instance, $widget, $args ) {

		echo $this->fetch(
			$widget->id,
			array(
				'callback' => array( $widget, 'widget' ),
				'args'     => array( $args, $instance )
			)
		);

		return false;
	}

	/**
	 * Generate widget output, capture with buffer and timestamp.
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return string
	 */
	public function callback( $name, $args ) {

		ob_start();
		call_user_func_array( $args['callback'], $args['args'] );

		return ob_get_clean() . $this->get_comment( $name );
	}
}