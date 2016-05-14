<?php

namespace Rarst\Fragment_Cache;

/**
 * Cache navigation menus.
 */
class Menu_Cache extends Fragment_Cache {

	/**
	 * @inheritDoc
	 */
	public function enable() {

		if ( is_admin() ) {
			add_action( 'admin_footer-nav-menus.php', array( $this, 'update_menus_edited' ) );
			add_action( 'wp_ajax_menu-locations-save', array( $this, 'update_menus_edited' ), 0 );
		} else {
			add_filter( 'wp_nav_menu_args', array( $this, 'wp_nav_menu_args' ), 20 );
			add_filter( 'wp_nav_menu_objects', array( $this, 'wp_nav_menu_objects' ) );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function disable() {

		if ( is_admin() ) {
			remove_action( 'admin_footer-nav-menus.php', array( $this, 'update_menus_edited' ) );
			remove_action( 'wp_ajax_menu-locations-save', array( $this, 'update_menus_edited' ), 0 );
		} else {
			remove_filter( 'wp_nav_menu_args', array( $this, 'wp_nav_menu_args' ), 20 );
			remove_filter( 'wp_nav_menu_objects', array( $this, 'wp_nav_menu_objects' ) );
		}
	}

	/**
	 * Fake no menu matches to force menu run custom callback.
	 *
	 * @param array $args Menu arguments.
	 *
	 * @return array
	 */
	public function wp_nav_menu_args( $args ) {

		if ( empty( $args['kessel_run'] ) ) {

			add_filter( 'wp_get_nav_menus', '__return_empty_array' ); // These are not the droids you are looking for.

			$args = array(
				'menu'           => '',
				'theme_location' => '',
				'fallback_cb'    => array( $this, 'fallback_cb' ),
				'original_args'  => $args,
			);
		}

		return $args;
	}

	/**
	 * Strip current* classes from menu items, since shared when cached.
	 *
	 * @param array $menu_items Array of menu item objects.
	 *
	 * @return array
	 */
	public function wp_nav_menu_objects( $menu_items ) {

		foreach ( $menu_items as $item_key => $item ) {
			foreach ( $item->classes as $class_key => $class ) {
				if ( 0 === stripos( $class, 'current' ) ) {
					unset( $menu_items[ $item_key ]->classes[ $class_key ] );
				}
			}
		}

		return $menu_items;
	}

	/**
	 * Save timestamp when menus were last modified for cache salt.
	 */
	public function update_menus_edited() {

		if ( ! empty( $_POST ) ) {
			update_option( 'fc_menus_edited', time() );
		}
	}

	/**
	 * Restore arguments and fetch cached fragment for them.
	 *
	 * @param array $args Arguments.
	 *
	 * @return string
	 */
	public function fallback_cb( $args ) {

		remove_filter( 'wp_get_nav_menus', '__return_empty_array' );

		$args = $args['original_args'];
		unset( $args['original_args'] );
		$echo                    = $args['echo'];
		$args['echo']            = false;
		$args['kessel_run']      = true;
		$args['fc_menus_edited'] = get_option( 'fc_menus_edited' );
		$name                    = is_object( $args['menu'] ) ? $args['menu']->slug : $args['menu'];

		if ( empty( $name ) && ! empty( $args['theme_location'] ) ) {
			$name = $args['theme_location'];
		}

		$output = $this->fetch( $name, $args, $args );

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * Generate and timestamp menu output.
	 *
	 * @param string $name Fragment name.
	 * @param array  $args Arguments.
	 *
	 * @return string
	 */
	protected function callback( $name, $args ) {

		$output = wp_nav_menu( $args ) . $this->get_comment( $name );

		return $output;
	}
}
