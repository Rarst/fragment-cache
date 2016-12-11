<?php

namespace Rarst\Fragment_Cache;

use Pimple\Container;

/**
 * Main plugin's class.
 */
class Plugin extends Container {

	/** @var array $handlers Set of registered fragment handlers. */
	protected $handlers = array();

	/**
	 * Start the plugin after initial setup.
	 */
	public function run() {

		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'update_blocker_blocked', array( $this, 'update_blocker_blocked' ) );
	}

	/**
	 * Enable registered fragment handlers on init.
	 */
	public function init() {

		if (
			'on' === filter_input( INPUT_POST, 'wp_customize' )
			&& empty( filter_input( INPUT_POST, 'action' ) )
			&& current_user_can( 'customize' )
		) {
			return; // We don’t want cache running in Customizer previews.
		}
		
		foreach ( $this->handlers as $key => $type ) {
			if ( isset( $this[ $type ] ) ) {
				/** @var Fragment_Cache $handler */
				$handler = $this[ $type ];
				$handler->enable();
			} else {
				unset( $this->handlers[ $key ] );
			}
		}
	}

	/**
	 * @see https://github.com/Rarst/update-blocker
	 *
	 * @param array $blocked Configuration data for blocked items.
	 *
	 * @return array
	 */
	public function update_blocker_blocked( $blocked ) {

		$blocked['plugins'][] = plugin_basename( dirname( __DIR__ ) . '/fragment-cache.php' );

		return $blocked;
	}

	/**
	 * Add (or override) cache handler and enable it.
	 *
	 * @param string $type       Handler type name.
	 * @param string $class_name Handler class name to instance.
	 */
	public function add_fragment_handler( $type, $class_name ) {

		if ( isset( $this[ $type ] ) ) {
			/** @var Fragment_Cache $handler */
			$handler = $this[ $type ];
			$handler->disable();
			unset( $this[ $type ] );
		}

		$this[ $type ] = function ( $plugin ) use ( $type, $class_name ) {
			return new $class_name( array( 'type' => $type, 'timeout' => $plugin['timeout'] ) );
		};

		if ( ! in_array( $type, $this->handlers, true ) ) {
			$this->handlers[] = $type;
		}
	}
}
