<?php

/**
 * Class RCP_Member_Access_Cache
 *
 * Caches to memory:
 * a member's access to a post
 * a post's various restriction settings.
 *
 * This is an internal class not meant for public use. Don't use it in your code, as it may
 * go away at any time without warning.
 */
final class RCP_Member_Access_Cache {

	/**
	 * @var array The post ID access cache.
	 */
	private $cache = array();

	/**
	 * @var array Post ID restrictions.
	 */
	private $restrictions = array();

	/**
	 * Returns the cached post access status.
	 *
	 * @param $post_id
	 *
	 * @return bool|WP_Error True if member can access, false if not, WP_Error if post ID not cached.
	 */
	public function can_access( $post_id ) {

		/** Return a WP_Error object if the post ID is not cached. */
		if ( ! array_key_exists( $post_id, $this->cache ) ) {
			return new WP_Error( 200, 'post_id not cached' );
		}

		if ( true === $this->cache[$post_id] ) {
			return true;
		}

		return false;
	}

	/**
	 * Adds the post ID entry to the cache.
	 *
	 * @param int $post_id The post ID to cache.
	 * @param bool $can_access True if the member can access, false if not.
	 */
	public function cache_post_id_access( $post_id, $can_access = false ) {
		$this->cache[$post_id] = $can_access;
	}

	/**
	 * Caches the specified post's content restrictions.
	 *
	 * @param int $post_id The post ID to cache.
	 */
	public function cache_post_restrictions( $post_id ) {

		$post_levels = get_post_meta( $post_id, 'rcp_subscription_level', true );

		$post_type_restrictions = rcp_get_post_type_restrictions( get_post_type( $post_id ) );

		$term_restricted_post_ids = rcp_get_post_ids_assigned_to_restricted_terms();

		$this->restrictions[$post_id] = array(
			'post' => array(
				'is_paid'            => get_post_meta( $post_id, '_is_paid', true ),
				'subscription_level' => ( ! empty( $post_levels ) && 'all' !== $post_levels ) ? $post_levels : false,
				'access_level'       => get_post_meta( $post_id, 'rcp_access_level', true ),
				'user_level'         => get_post_meta( $post_id, 'rcp_user_level', true ),
			),
			'post_type_restrictions' => array(
				'is_paid'            => ! empty( $post_type_restrictions['is_paid'] ) ? $post_type_restrictions['is_paid'] : false,
				'subscription_level' => ! empty( $post_type_restrictions['subscription_level'] ) ? $post_type_restrictions['subscription_level'] : false,
				'access_level'       => ! empty( $post_type_restrictions['access_level'] ) ? $post_type_restrictions['access_level'] : false,
				'user_level'         => ! empty( $post_type_restrictions['user_level'] ) ? $post_type_restrictions['user_level'] : false
			)
		);

		if ( in_array( $post_id, $term_restricted_post_ids ) ) {
			$this->restrictions[$post_id]['has_term_restrictions'] = true;
		}

		foreach( $this->restrictions[$post_id]['post'] as $key => $restriction ) {
			if ( false === $restriction || empty( $restriction ) ) {
				unset( $this->restrictions[$post_id]['post'][$key] );
			}
		}

		foreach( $this->restrictions[$post_id]['post_type_restrictions'] as $key => $restriction ) {
			if ( false === $restriction || empty( $restriction ) ) {
				unset( $this->restrictions[$post_id]['post_type_restrictions'][$key] );
			}
		}


		if (
			! empty( $this->restrictions[$post_id]['post']['is_paid'] ) ||
			! empty( $this->restrictions[$post_id]['post']['subscription_level'] ) ||
			! empty( $this->restrictions[$post_id]['post']['access_level'] ) ||
			! empty( $this->restrictions[$post_id]['post']['user_level'] )
		) {
			$this->restrictions[$post_id]['has_post_restrictions'] = true;
		}

		if (
			! empty( $this->restrictions[$post_id]['post_type_restrictions']['is_paid'] ) ||
			! empty( $this->restrictions[$post_id]['post_type_restrictions']['subscription_level'] ) ||
			! empty( $this->restrictions[$post_id]['post_type_restrictions']['access_level'] ) ||
			! empty( $this->restrictions[$post_id]['post_type_restrictions']['user_level'] )
		) {
			$this->restrictions[$post_id]['has_post_type_restrictions'] = true;
		}

		if ( ! empty( $this->restrictions[$post_id]['has_post_restrictions'] ) ||
		     ! empty( $this->restrictions[$post_id]['has_term_restrictions'] ) ||
		     ! empty( $this->restrictions[$post_id]['has_post_type_restrictions'] )
		) {
			$this->restrictions[$post_id]['is_restricted_content'] = true;
		}

	}

	/**
	 * Gets the specified post's restrictions.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The post's restrictions, if any. Empty array if not.
	 */
	public function get_post_restrictions( $post_id ) {
		return ( ! empty( $this->restrictions[$post_id] ) ? $this->restrictions[$post_id] : array() );
	}

	/**
	 * Checks if a post is Paid Only.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool True if the post is Paid Only, false if not.
	 */
	public function is_paid_content( $post_id ) {

		$restrictions = $this->get_post_restrictions( $post_id );

		if ( empty( $restrictions ) ) {
			return false;
		}

		return ( ! empty( $restrictions['post']['is_paid'] ) || ! empty( $restrictions['post_type_restrictions']['is_paid'] ) );
	}

	/**
	 * Returns if the specified post ID is restricted content or not.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool True if the post is restricted, false if not.
	 */
	public function is_restricted_content( $post_id ) {

		$restrictions = $this->get_post_restrictions( $post_id );

		if ( empty( $restrictions ) ) {
			return false;
		}

		return ( ! empty( $restrictions['is_restricted_content'] ) && true === $restrictions['is_restricted_content'] );
	}

	/**
	 * Returns if the specified post ID has taxonomy term restrictions.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool True if the post has term restrictions, false if not.
	 */
	public function has_term_restrictions( $post_id ) {

		$restrictions = $this->get_post_restrictions( $post_id );

		if ( empty( $restrictions ) ) {
			return false;
		}

		return ( ! empty( $restrictions['has_term_restrictions'] ) && true === $restrictions['has_term_restrictions'] );
	}
}
