<?php

namespace Rarst\Fragment_Cache;

/**
 * Main plugin's class.
 */
class Plugin extends \Pimple {

	protected $handlers = array();

	/**
	 * Start the plugin after initial setup.
	 */
	public function run() {

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Enable registered fragment handlers on init.
	 */
	public function init() {

		foreach ( $this->handlers as $key => $type ) {
			if ( isset( $this[$type] ) ) {
				/** @var Fragment_Cache $handler */
				$handler = $this[$type];
				$handler->enable();
			}
			else {
				unset( $this->handlers[$key] );
			}
		}
	}

	/**
	 * Add (or override) cache handler and enable it.
	 *
	 * @param string $type
	 * @param string $class_name
	 */
	public function add_fragment_handler( $type, $class_name ) {

		if ( isset( $this[$type] ) ) {
			/** @var Fragment_Cache $handler */
			$handler = $this[$type];
			$handler->disable();
			unset( $this[$type] );
		}

		$this[$type] = function ( $plugin ) use ( $type, $class_name ) {
			return new $class_name( array( 'type' => $type, 'timeout' => $plugin['timeout'] ) );
		};

		if ( ! in_array( $type, $this->handlers ) )
			$this->handlers[] = $type;
	}
}