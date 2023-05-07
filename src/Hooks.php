<?php

namespace NLP_Tools;

class Hooks {

	public static function init() {
		add_action( 'admin_init', [ Settings::instance(), 'init' ] );
		add_action( 'admin_menu', [ Settings::instance(), 'nlp_tools_options_page' ] );
	}
}
