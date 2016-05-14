<?php

namespace Rarst\Fragment_Cache;

/**
 * Abstract base class for implementation of fragment type handler.
 */
abstract class Fragment_Cache {

	/** @var bool $in_callback Static flag to catch and prevent nested caching calls.  */
	static $in_callback = false;

	/** @var int $timeout Cache timeout duration.  */
	public $timeout;

	/** @var string $type Handler type name. */
	protected $type;

	/**
	 * Create object and set parameters from passed.
	 *
	 * @param array $args Configuration arguments.
	 */
	public function __construct( $args ) {

		$this->type    = $args['type'];
		$this->timeout = $args['timeout'];
	}

	/**
	 * Enable fragment handler.
	 */
	abstract public function enable();

	/**
	 * Disable fragment handler.
	 */
	abstract public function disable();

	/**
	 * Wrapper to retrieve data through TLC Transient.
	 *
	 * @param string $name Name of fragment.
	 * @param array  $args Arguments.
	 * @param mixed  $salt Optional salt data.
	 *
	 * @return mixed
	 */
	public function fetch( $name, $args, $salt = '' ) {

		global $current_user;

		static $empty_user;

		if ( self::$in_callback || apply_filters( 'fc_skip_cache', false, $this->type, $name, $args, $salt ) ) {
			return $this->callback( $name, $args );
		}

		// Anonymize front-end run for consistency.
		if ( is_user_logged_in() ) {

			if ( empty( $empty_user ) ) {
				$empty_user = new \WP_User( 0 );
			}

			$stored_user  = $current_user;
			$current_user = $empty_user;
		}

		$salt   = maybe_serialize( $salt );
		$output = tlc_transient( 'fragment-cache-' . $this->type . '-' . $name . $salt )
				->updates_with( array( $this, 'wrap_callback' ), array( $name, $args ) )
				->expires_in( $this->timeout )
				->get();

		if ( ! empty( $stored_user ) ) {
			$current_user = $stored_user;
		}

		return $output;
	}

	/**
	 * Wraps callback to correctly set execution flag.
	 *
	 * @param string $name Fragment name.
	 * @param array  $args Arguments.
	 *
	 * @return string
	 */
	public function wrap_callback( $name, $args ) {

		self::$in_callback = true;
		$output = $this->callback( $name, $args );
		self::$in_callback = false;

		return $output;
	}

	/**
	 * Used to generate data to be cached.
	 *
	 * @param string $name Fragment name.
	 * @param array  $args Arguments.
	 *
	 * @return string
	 */
	abstract protected function callback( $name, $args );

	/**
	 * Get human-readable HTML comment with timestamp to append to cached fragment.
	 *
	 * @param string $name Fragment name.
	 *
	 * @return string
	 */
	public function get_comment( $name ) {

		return '<!-- ' . esc_html( $name ) . ' ' . esc_html( $this->type ) . ' cached on ' . date_i18n( DATE_RSS ) . ' -->';
	}
}
