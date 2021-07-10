<?php
/**
 * Plugin Name:       ElasticPress Natural Language Processing Tools
 * Plugin URI:        https://github.com/moraleida/elasticpress-nlp-tools
 * Description:       A toolkit for using self-hosted Natural Language Processing in WordPress
 * Version:           0.0.1
 * Requires PHP:      7.0
 * Author:            Ricardo Moraleida
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       enlptag
 *
 * @package  elasticpress-nlp-tools
 */

namespace ENLPTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ENLPTOOLS_URL', plugin_dir_url( __FILE__ ) );
define( 'ENLPTOOLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ENLPTOOLS_VERSION', '0.0.1' );