<?php

namespace Rarst\Fragment_Cache;

/**
 * Cache widgets.
 */
class Widget_Cache extends Fragment_Cache {

	public function enable() {

		if ( is_admin() ) {
			add_action( 'admin_footer-widgets.php', array( $this, 'update_widgets_edited' ) );
			add_action( 'wp_ajax_save-widget', array( $this, 'update_widgets_edited' ), 0 );
		}
		else {
			add_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10, 3 );
		}
	}

	public function disable() {

		if ( is_admin() ) {
			remove_action( 'admin_footer-widgets.php', array( $this, 'update_widgets_edited' ) );
			remove_action( 'wp_ajax_save-widget', array( $this, 'update_widgets_edited' ), 0 );
		}
		else {
			remove_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10, 3 );
		}
	}

	/**
	 * Save timestamp when widgets were last modified for cache salt.
	 */
	public function update_widgets_edited(  ) {

		if ( ! empty( $_POST ) )
			update_option( 'fc-widgets-edited', time() );
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
			),
			get_option( 'fc-widgets-edited' )
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
	protected function callback( $name, $args ) {

		ob_start();
		call_user_func_array( $args['callback'], $args['args'] );

		return ob_get_clean() . $this->get_comment( $name );
	}
}