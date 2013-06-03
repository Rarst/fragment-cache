<?php

namespace Rarst\Fragment_Cache;

/**
 * Cache galleries.
 */
class Gallery_Cache extends Fragment_Cache {

	protected $original_shortcode;

	public function enable() {

		global $shortcode_tags;

		if ( isset( $shortcode_tags['gallery'] ) )
			$this->original_shortcode = $shortcode_tags['gallery'];

		add_shortcode( 'gallery', array( $this, 'gallery_shortcode' ) );
	}

	public function disable() {

		if ( ! empty( $this->original_shortcode ) )
			add_shortcode( 'gallery', $this->original_shortcode );
	}

	/**
	 * Fetch and return cached gallery.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function gallery_shortcode( $args ) {

		if ( empty( $args ) )
			$args = array();

		// salt for cases post edited or attachments changed
		$args['fc_post_modified']    = get_the_modified_time( 'U' );
		$args['fc_post_attachments'] = $this->get_attachment_ids();

		$post_id = get_the_ID();
		$output  = $this->fetch( 'post-' . $post_id, compact( 'args', 'post_id' ), $args );

		return $output;
	}

	/**
	 * Retrieve array of attachment IDs for current post.
	 *
	 * @return array
	 */
	public function get_attachment_ids() {

		static $attachments = array();

		$post_id = get_the_ID();

		if ( ! isset( $attachments[$post_id] ) )
			$attachments[$post_id] = get_posts( array(
				'post_type'   => 'attachment',
				'post_parent' => $post_id,
				'orderby'     => 'ID',
				'fields'      => 'ids',
			) );

		return $attachments[$post_id];
	}

	/**
	 * Set up post context and generate gallery output.
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return string
	 */
	public function callback( $name, $args ) {

		global $post;

		$post = get_post( $args['post_id'] );
		setup_postdata( $post );
		$shortcode = isset( $this->original_shortcode ) ? $this->original_shortcode : 'gallery_shortcode';
		$output    = call_user_func( $shortcode, $args['args'] ) . $this->get_comment( $name );
		wp_reset_postdata();

		return $output;
	}
}