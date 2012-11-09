<?php
/**
 * Plugin Name: ShopLocket
 * Plugin URI: http://shoplocket.com
 * Description: Sell your products straight from your site.
 * Author: Mohammad Jangda, ShopLocket
 * Version: 0.1
 * Author URI: http://shoplocket.com
 * Text Domain: shoploacket
 * Domain Path: /languages
 */
 
class ShopLocket {
	const BASE_URL = 'https://www.shoplocket.com';
	const EMBED_URL_SPRINTF_PATTERN = 'https://www.shoplocket.com/products/%s/embed';
	const PRODUCT_URL_SPRINTF_PATTERN = 'https://www.shoplocket.com/products/%s';
	const URL_REGEX_PATTERN = '#https://www\.shoplocket\.com/products/([\w]+)(/embed[a-zA-Z0-9-]*)?#';
	const IFRAME_REGEX_PATTERN = '!<iframe((?:\s+\w+=[\'"][^\'"]*[\'"]\s*)*)src=[\'"]https://www\.shoplocket\.com/products/(\w+)/embed[a-zA-Z0-9-]*[\'"]((?:\s+\w+=[\'"][^\'"]*[\'"]\s*)*)></iframe>!i';
	const DEFAULT_WIDTH = 510;
	const DEFAULT_HEIGHT = '400';
	const HELP_URL = 'http://help.shoplocket.com/customer/portal/articles/675322-why-isn-t-shoplocket-working-on-my-wordpress-blog-post-';

	static function load() {
		require_once( dirname( __FILE__ ) . '/class-shoplocket-widget.php' );

		add_shortcode( 'shoplocket', array( __CLASS__, 'render_shortcode' ) );
		wp_embed_register_handler( 'shoplocket', self::URL_REGEX_PATTERN, array( __CLASS__, 'embed_handler' ) );

		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	static function init() {
		if ( current_user_can( 'unfiltered_html' ) )
			add_filter( 'content_save_pre', array( __CLASS__, 'content_save_pre_embed_to_shortcode' ) );
		else
			add_filter( 'pre_kses', array( __CLASS__, 'pre_kses_embed_to_shortcode' ) );
	}

	/**
	 * Allow using a URL on its own line:
	 * 		https://www.shoplocket.com/products/02b0c58fd5f/embed
	 * 		https://www.shoplocket.com/products/02b0c58fd5f
	 */
	function embed_handler( $matches, $attr, $url, $rawattr ) {
		return self::render_shortcode( array( 'url' => $url ) );
	}

	function content_save_pre_embed_to_shortcode( $content ) {
		// This data is slashed, so we need to do the strip and add dance
		return addslashes( self::embed_to_shortcode( stripslashes( $content ), self::IFRAME_REGEX_PATTERN ) );
	}

	function pre_kses_embed_to_shortcode( $content ) {
		return self::embed_to_shortcode( $content, self::IFRAME_REGEX_PATTERN );
	}

	/**
	 * Convert an standard iframe embed into a shortcode.
	 * Borrowed from Jetpack's Vimeo Shortcode
	 *
	 * Adding this to post content
	 * 		<iframe class='shoplocket-embed' src='https://www.shoplocket.com/products/02b0c58fd5f/embed' width='510' height='400' frameborder='0' style='max-width:100%;'scrolling='no'></iframe>
	 * Turns into
	 * 		[shoplocket id="02b0c58fd5f" width="510" height="400"]
	 */
	function embed_to_shortcode( $content, $regexp ) {
		if ( false === stripos( $content, self::BASE_URL ) ) 
			return $content;

		$regexp_ent = str_replace( '&amp;#0*58;', '&amp;#0*58;|&#0*58;', htmlspecialchars( $regexp, ENT_NOQUOTES ) ); 

		foreach ( array( 'regexp', 'regexp_ent' ) as $reg ) {
			if ( ! preg_match_all( $$reg, $content, $matches, PREG_SET_ORDER ) )
				continue;

			foreach ( $matches as $match ) {
				$id = $match[2];

				$params = $match[1] . $match[3];

				if ( 'regexp_ent' == $reg ) 
					$params = html_entity_decode( $params );

				$params = wp_kses_hair( $params, array( 'http' ) );

				$width = isset( $params['width'] ) ? (int) $params['width']['value'] : 0;
				$height = isset( $params['height'] ) ? (int) $params['height']['value'] : 0;

				$wh = '';
				if ( $width && $height ) 
					$wh = ' w=' . $width . ' h=' . $height; 

				$shortcode = '[shoplocket id=' . $id . $wh . ']';
				$content = str_replace( $match[0], $shortcode, $content );
			}
		}

		return $content;
	}

	/**
	 * [shoplocket url="https://www.shoplocket.com/products/02b0c58fd5f/embed" width=500 height=400]
	 * or
	 * [shoplocket id="02b0c58fd5f" width=500 height=400]
	 */
	function render_shortcode( $atts ) {
		$atts = self::normalize_args( $atts );

		$error_message = sprintf(
			__( 'You need to specify <a href="%s" target="_blank">a valid "url" or "id" </a> for your ShopLocket product.', 'shoplocket' ),
			self::HELP_URL
		);

		if ( empty( $atts['url'] ) && empty( $atts['id'] ) ) {
			if ( current_user_can( 'edit_posts' ) )
				echo '<p>' . $error_message . '</p>';
			return;
		}

		if ( $atts['url'] ) {
			$atts['id'] = self::get_id_from_url( $atts['url'] );
		}

		if ( $atts['id'] && ctype_alnum( $atts['id'] ) ) {
			$url = self::get_embed_url_from_id( $atts['id'] );
		} else {
			if ( current_user_can( 'edit_posts' ) )
				echo '<p>' . $error_message . '</p>';
			return;
		}

		// TODO: validate width and height

		return sprintf( '<iframe class="shoplocket-embed" src="%1$s" width="%2$s" height="%3$s" frameborder="0" style="max-width:100%;" scrolling="no"></iframe>',
			esc_url( $url ),
			esc_attr( $atts['width'] ),
			esc_attr( $atts['height'] )
		);
	}

	static function is_shoplocket_url( $url ) {
		return preg_match( ShopLocket::URL_REGEX_PATTERN, $url );
	}

	static function get_product_url_from_id( $id ) {
		return sprintf( self::PRODUCT_URL_SPRINTF_PATTERN, $id );
	}

	static function get_embed_url_from_id( $id ) {
		return sprintf( self::EMBED_URL_SPRINTF_PATTERN, $id );
	}

	static function get_id_from_url( $url ) {
		if ( preg_match( ShopLocket::URL_REGEX_PATTERN, $url, $matches ) )
			return sanitize_text_field( $matches[1] );
		return false;
	}

	static function normalize_args( $args ) {
		return shortcode_atts( array(
			'url' => '',
			'id' => '',
			'width' => self::DEFAULT_WIDTH,
			'height' => self::DEFAULT_HEIGHT,
			'title' => '',
		), $args );
	}
}

ShopLocket::load();
