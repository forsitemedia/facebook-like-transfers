<?php
/*
Plugin Name: Facebook Like Transfers
Plugin URI: https://forsite.media/
Description: Accumulates Facebook likes even when switching from http to https
Author: Forsite Media
Version: 0.1
Author URI: https://forsite.media/

Copyright 2015 Forsite Media

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License (GPL v2) only.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Facebook Like Transfers class.
 */
class Facebook_Like_Transfers {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_fields' ) );
		add_filter( 'wpseo_opengraph_url', array( $this, 'filter' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
		add_action( 'wp_head', array( $this, 'add_meta_tag' ) );
	}

	/**
	 * Get current URL.
	 *
	 * @return  string  $url  The current page URL
	 */
	public function get_current_url() {
		$ssl      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
		$sp       = strtolower( $_SERVER['SERVER_PROTOCOL'] );
		$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port     = $_SERVER['SERVER_PORT'];
		$port     = ( ( ! $ssl && $port == '80' ) || ( $ssl && $port == '443' ) ) ? '' : ':' . $port;
		$host     = ( isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null );
		$host     = isset( $host ) ? $host : $_SERVER['SERVER_NAME'] . $port;
		$url      = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
		return $url;
	}

	/**
	 * Add the meta tag.
	 */
	public function add_meta_tag() {

		// Bail out if WordPress SEO is installed (it already provides this tag for us)
		if ( function_exists( 'wpseo_auto_load' ) ) {
			return;
		}


		$post = get_post( get_the_ID() );
		$url = get_the_permalink( $post->ID );
		if ( $url == $this->get_current_url() ) {

			$found_time = strtotime( $post->post_date );
			$set_time = strtotime( get_option( 'facebook_likes_https' ) );
			if ( $set_time > $found_time ) {
				$url = str_replace( 'https://', 'http://', $url );
			}

			echo '<meta property="og:url" content="' . esc_url( $url ) . '" />';
		}

	}

	/**
	 * Filter for modifying the WordPress SEO og:url URL's.
	 * This will not fire unless WordPress SEO is installed.
	 */
	public function filter( $url ) {
		$post = get_post( get_the_ID() );
		$found_url = get_permalink( $post->ID );

		if ( $found_url == $url ) {
			$found_time = strtotime( $post->post_date );
			$set_time = strtotime( get_option( 'facebook_likes_https' ) );
			if ( $set_time > $found_time ) {
				$url = str_replace( 'https://', 'http://', $url );
			}
		}

		return $url;
	}

	/**
	 * Add new fields to wp-admin/options-general.php page.
	 */
	public function register_fields() {
		register_setting( 'general', 'facebook_likes_https', 'esc_attr' );
		add_settings_field(
			'fav_color',
			'<label for="facebook_likes_https">' . __( 'Facebook Likes HTTPS date cutoff' , 'facebook_likes_https' ) . '</label>',
			array( $this, 'fields_html' ),
			'general'
		);
	}

	/**
	 * HTML for extra settings.
	 */
	public function fields_html() {
		$value = get_option( 'facebook_likes_https' );
		echo '<input type="date" id="facebook_likes_https" name="facebook_likes_https" value="' . esc_attr( $value ) . '" />';
	}

	/**
	 * Add settings links.
	 *
	 * @param  array  $links  The list of plugin page links
	 * @return array  $links  The modified list of plugin page links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( '/options-general.php#facebook_likes_https' ) ) . '">' . __( 'Settings' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

}
new Facebook_Like_Transfers();
