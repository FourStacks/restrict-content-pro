<?php
/**
 * Template Functions
 *
 * Functions related to loading and displaying template files
 *
 * @package     Restrict Content Pro
 * @subpackage  Template Functions
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */


/**
 * Returns the path to the EDD templates directory
 *
 * @access      private
 * @since       1.5
 * @return      string
 */
function rcp_get_templates_dir() {
	return RCP_PLUGIN_DIR . 'templates';
}

/**
 * Returns the URL to the EDD templates directory
 *
 * @access      private
 * @since       1.3.2.1
 * @return      string
 */
function rcp_get_templates_url() {
	return RCP_PLUGIN_URL . 'templates';
}

/**
 * Retrieves a template part
 *
 * Taken from bbPress
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Default null
 *
 * @uses  rcp_locate_template()
 * @uses  load_template()
 * @uses  get_template_part()
 *
 * @since  1.5
 * @return string The template filename if one is located.
 */
function rcp_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parst to be filtered
	$templates = apply_filters( 'rcp_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return rcp_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Taken from bbPress
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *                            Has no effect if $load is false.
 *
 * @since  1.5
 * @return string The template filename if one is located.
 */
function rcp_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	$template_stack = array();

	// check child theme first
	$template_stack[] = trailingslashit( get_stylesheet_directory() ) . 'rcp/';

	// check parent theme next
	$template_stack[] = trailingslashit( get_template_directory() ) . 'rcp/';

	// check custom directories
	$template_stack = apply_filters( 'rcp_template_stack', $template_stack, $template_names );

	// check theme compatibility last
	$template_stack[] = trailingslashit( rcp_get_templates_dir() );

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) ) {
			continue;
		}

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Loop through template stack.
		foreach ( (array) $template_stack as $template_location ) {

			// Continue if $template_location is empty.
			if ( empty( $template_location ) ) {
				continue;
			}

			// Check child theme first.
			if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
				$located = trailingslashit( $template_location ) . $template_name;
				break 2;
			}
		}

	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Add post classes to indicate whether content is restricted and if the
 * current user has access.
 *
 * @param array        $classes Array of post classes.
 * @param string|array $class Additional classes added to the post.
 * @param int          $post_id ID of the current post.
 *
 * @since 2.7
 * @return array
 */
function rcp_post_classes( $classes, $class = '', $post_id = false ) {

	$user_id = get_current_user_id();

	if ( ! $post_id || is_admin() ) {
		return $classes;
	}

	if ( rcp_is_restricted_content( $post_id ) ) {
		$classes[] = 'rcp-is-restricted';

		$classes[] = rcp_user_can_access( $user_id, $post_id ) ? 'rcp-can-access' : 'rcp-no-access';
	}

	return $classes;

}

add_filter( 'post_class', 'rcp_post_classes', 10, 3 );