<?php

namespace NLP_Tools;

class Hooks {

	public static function init() {
		add_action( 'admin_init', [ Settings::instance(), 'wporg_settings_init' ] );
		add_action( 'admin_menu', [ Settings::instance(), 'wporg_options_page' ] );
	}
}
