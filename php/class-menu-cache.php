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

		global $wp_version;

		if ( is_admin() ) {
			add_action( 'admin_footer-nav-menus.php', array( $this, 'update_menus_edited' ) );
			add_action( 'wp_ajax_menu-locations-save', array( $this, 'update_menus_edited' ), 0 );
			add_action( 'wp_ajax_customize_save', array( $this, 'customize_save' ), 0 );

			return;
		}

		add_filter( 'pre_wp_nav_menu', array( $this, 'pre_wp_nav_menu' ), 10, 2 );
		add_filter( 'wp_nav_menu_objects', array( $this, 'wp_nav_menu_objects' ) );

		if ( version_compare( $wp_version, '3.9', '<' ) ) {
			add_filter( 'wp_nav_menu_args', array( $this, 'wp_nav_menu_args' ), 20 );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function disable() {

		if ( is_admin() ) {
			remove_action( 'admin_footer-nav-menus.php', array( $this, 'update_menus_edited' ) );
			remove_action( 'wp_ajax_menu-locations-save', array( $this, 'update_menus_edited' ), 0 );
			remove_action( 'wp_ajax_customize_save', array( $this, 'customize_save' ), 0 );

			return;
		}

		remove_filter( 'pre_wp_nav_menu', array( $this, 'pre_wp_nav_menu' ), 10 );
		remove_filter( 'wp_nav_menu_objects', array( $this, 'wp_nav_menu_objects' ) );
		remove_filter( 'wp_nav_menu_args', array( $this, 'wp_nav_menu_args' ), 20 );
	}

	/**
	 * Return cached menu, using pre-generation hook.
	 *
	 * @param string $menu Menu HTML to return.
	 * @param object $args Menu arguments.
	 *
	 * @return string
	 */
	public function pre_wp_nav_menu( $menu, $args ) {

		$args                    = get_object_vars( $args );
		$args['echo']            = false;
		$args['fc_menus_edited'] = get_option( 'fc_menus_edited' );
		$name                    = is_object( $args['menu'] ) ? $args['menu']->slug : $args['menu'];

		if ( empty( $name ) && ! empty( $args['theme_location'] ) ) {
			$name = $args['theme_location'];
		}

		return $this->fetch( $name, $args, $args );
	}

	/**
	 * Fake no menu matches to force menu run custom callback.
	 *
	 * @deprecated
	 *
	 * @param array $args Menu arguments.
	 *
	 * @return array
	 */
	public function wp_nav_menu_args( $args ) {

		_deprecated_function( __FUNCTION__, '1.3', 'Menu cache with arguments override unnecessary on WP >= 3.9.' );

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
	 * Invalidate menu cache on related Customizer saves.
	 */
	public function customize_save() {

		$customized = filter_input( INPUT_POST, 'customized' );

		if ( empty( $customized ) ) {
			return;
		}

		$customized = json_decode( $customized, true );
		$settings   = array_keys( $customized );

		foreach ( $settings as $setting ) {

			if ( 0 === stripos( $setting, 'nav_menu' ) ) {

				update_option( 'fc_menus_edited', time() );

				return;
			}
		}
	}

	/**
	 * Restore arguments and fetch cached fragment for them.
	 *
	 * @deprecated
	 *
	 * @param array $args Arguments.
	 *
	 * @return string
	 */
	public function fallback_cb( $args ) {

		_deprecated_function( __FUNCTION__, '1.3', 'Menu cache with arguments override unnecessary on WP >= 3.9.' );

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

		remove_filter( 'pre_wp_nav_menu', array( $this, 'pre_wp_nav_menu' ), 10 );
		$output = wp_nav_menu( $args ) . $this->get_comment( $name );
		add_filter( 'pre_wp_nav_menu', array( $this, 'pre_wp_nav_menu' ), 10, 2 );

		return $output;
	}
}
