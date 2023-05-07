<?php

namespace NLP_Tools;

/**
 * WordPress Dashboard Settings
 *
 * @package NLP_Tools
 */
class Settings {

	const SLUG = 'nlp_tools';

	public static $instance;

	/**
	 * Singleton instantiator
	 *
	 * @return Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			$instance = new Settings();
		}

		return $instance;
	}

	private function get_option_name() {
		return static::SLUG . '_settings';
	}


	public function init() {
		register_setting( static::SLUG, $this->get_option_name() );

		add_settings_section(
			'features',
			__( 'Plugin Features', \NLP_Tools::TEXTDOMAIN ), [ $this, 'nlp_tools_section_developers_callback' ],
			static::SLUG
		);

		// Register a new field in the "nlp_tools_section_developers" section, inside the "wporg" page.
		add_settings_field(
			'nlp_tools_field_pill', // As of WP 4.6 this value is used only internally.
			// Use $args' label_for to populate the id inside the callback.
			__( 'Pill', \NLP_Tools::TEXTDOMAIN  ),
			[ $this, 'nlp_tools_field_pill_cb' ],
			static::SLUG,
			'features',
			[
				'label_for'         => 'nlp_tools_field_pill',
				'class'             => 'nlp_tools_row',
				'nlp_tools_custom_data' => 'custom',
			]
		);
	}

	public function nlp_tools_section_developers_callback( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Follow the white rabbit.', \NLP_Tools::TEXTDOMAIN ); ?></p>
		<?php
	}

	/**
	 * Pill field callbakc function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args
	 */
	public function nlp_tools_field_pill_cb( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$options = get_option( $this->get_option_name() );
		?>
		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['nlp_tools_custom_data'] ); ?>"
			name="nlp_tools_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
			<option
				value="red" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'red', false ) ) : ( '' ); ?>>
				<?php esc_html_e( 'red pill', \NLP_Tools::TEXTDOMAIN ); ?>
			</option>
			<option
				value="blue" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'blue', false ) ) : ( '' ); ?>>
				<?php esc_html_e( 'blue pill', \NLP_Tools::TEXTDOMAIN ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'You take the blue pill and the story ends. You wake in your bed and you believe whatever you want to believe.', \NLP_Tools::TEXTDOMAIN ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'You take the red pill and you stay in Wonderland and I show you how deep the rabbit-hole goes.', \NLP_Tools::TEXTDOMAIN ); ?>
		</p>
		<?php
	}

	/**
	 * Add the top level menu page.
	 */
	public function nlp_tools_options_page() {
		add_menu_page(
			'Natural Language Processing Tools Settings',
			'NLP Tools',
			'manage_options',
			static::SLUG,
			[ $this, 'nlp_tools_options_page_html' ]
		);
	}


	/**
	 * Top level menu callback function
	 */
	public function nlp_tools_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'nlp_tools_messages', 'nlp_tools_message', __( 'Settings Saved', \NLP_Tools::TEXTDOMAIN ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'nlp_tools_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "wporg"
				settings_fields( static::SLUG );
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				do_settings_sections( static::SLUG );
				// output save settings button
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

}