<?php

class ShopLocket_Widget extends WP_Widget {
	function __construct() {
		parent::__construct( false, __( 'ShopLocket', 'shoplocket' ), array(
			'description' => __( 'Embed your ShopLocket product', 'shoplocket' ),
			'classname' => 'widget-shoplocket'
		), array(
			'width' => 325,
		) );
	}

	function widget( $args, $instance ) {
		$instance = ShopLocket::normalize_args( $instance );

		echo $args['before_widget'];
		echo $args['before_title'] . $instance['title'] . $args['after_title'];
		unset( $instance['title'] ); // in case the shortcode also has title output
		echo ShopLocket::render_shortcode( $instance );
		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		$instance = ShopLocket::normalize_args( $new_instance );

		if ( isset( $new_instance['code'] ) ) {
			$code = trim( $new_instance['code'] );

			if ( ShopLocket::is_shoplocket_url( $code ) ) {
				$instance['id'] = ShopLocket::get_id_from_url( $code );
			} elseif ( preg_match( ShopLocket::IFRAME_REGEX_PATTERN, $code, $matches ) ) {
				$instance['id'] = sanitize_text_field( $matches[2] );
			} else {
				$instance['id'] = sanitize_text_field( $code );
			}
			// TODO: do a remote test to check valid?
		}

		if ( isset( $new_instance['title'] ) )
			$instance['title'] = sanitize_text_field( $new_instance['title'] );

		if ( isset( $new_instance['width'] ) )
			$instance['width'] = sanitize_text_field( $new_instance['width'] );

		if ( isset( $new_instance['height'] ) )
			$instance['height'] = sanitize_text_field( $new_instance['height'] );

		// TODO: validate height/width to make sure they're sane values
		// TODO: allow inserting shortcode into widget

		return $instance;
	}

	function form( $instance ) {
		$instance = ShopLocket::normalize_args( $instance );

		if ( ! empty( $instance['id'] ) )
			$instance['url'] = ShopLocket::get_product_url_from_id( $instance['id'] );

		// TODO: show error on invalid product id
		?>

		<?php if ( ! empty( $instance['url'] ) ) : ?>
			<p>
				<?php printf( __( 'Your selected product<br /> <a href="%s" target="_blank" title="Opens in new window">%s</a>', 'shoplocket' ), esc_url( $instance['url'] ), esc_html( $instance['url'] ) ); ?>
			</p>
		<?php endif; ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'code' ); ?>">
				<?php _e( 'Enter the embed, URL, or ID of your ShopLocket product', 'shoplocket' ); ?>
			</label>
			<br />
			<textarea id="<?php echo $this->get_field_id( 'code' ); ?>" name="<?php echo $this->get_field_name( 'code' ); ?>" class="widefat"><?php echo esc_textarea( $instance['id'] ); ?></textarea>
			<small><a href="<?php echo esc_url( ShopLocket::HELP_URL ); ?>" target="_blank" title="<?php esc_attr_e( 'Confused? Click through for instructions on finding your product emebed code.', 'shoplocket' ); ?>"><?php _e( 'What can you find this?', 'shoplocket' ); ?></a></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title', 'shoplocket' ); ?>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'width' ); ?>">
				<?php esc_html_e( 'Width', 'shoplocket' ); ?>
				<input size="3" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" type="text" value="<?php echo esc_attr( $instance['width'] ); ?>" />
			</label>

			<label for="<?php echo $this->get_field_id( 'height' ); ?>">
				<?php esc_html_e( 'Height', 'shoplocket' ); ?>
				<input size="3" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php echo esc_attr( $instance['height'] ); ?>" />
			</label>
		</p>
		<?php
	}

	function register() {
		register_widget( __CLASS__ );
	}
}

add_action( 'widgets_init', array( 'ShopLocket_Widget', 'register' ) );