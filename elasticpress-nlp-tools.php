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

use \ElasticPress\Elasticsearch;
use \ElasticPress\Indexables;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ENLPTOOLS_URL', plugin_dir_url( __FILE__ ) );
define( 'ENLPTOOLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ENLPTOOLS_VERSION', '0.0.1' );

class ENLPTools {

	public $pipeline_name = 'wordpress_nlp_ingester';
	public $pipeline_description = 'A Natural Language Processing pipeline for WordPress taxonomies';
	public $field_to_ingest = 'post_content';

	public function __construct() {
	}

	public function init() {
		\add_action( 'activate_plugin', array( $this, 'activate' ) );
		\add_filter( 'ep_index_request_path', array( $this, 'append_ingester_to_index_endpoint' ) );
		\add_filter( 'ep_bulk_index_request_path', array( $this, 'append_ingester_to_index_endpoint' ) );
	}

	/**
	 * Actions to run when the plugin is activated
	 */
	public function activate() {
		/**
		 * TODO: Require ElasticPress
		 * TODO: Require OpenNLP Plugin
		 * TODO: Require OpenNLP Configs
		 */

		$this->configure_ingester();
		$this->configure_mapping();
	}

	/**
	 * Create the pipeline ingester in the Elasticsearch cluster
	 */
	public function configure_ingester() {
		$ep = Elasticsearch::factory();

		\add_filter( 'ep_query_request_path', array( $this, 'ingester_request_path' ) );
		\add_filter( 'http_request_args', array( $this, 'request_method' ) );

		$query = array(
			'description' => $this->pipeline_description,
			'processors'  => array(
				array(
					'opennlp' => array(
						'field' => $this->field_to_ingest,
					),
				),
			),
		);

		$index = Indexables::factory()->get( 'post' )->get_index_name();

		$ep->query( $index, null, $query, array() );

		\remove_filter( 'ep_query_request_path', array( $this, 'ingester_request_path' ) );
		\remove_filter( 'http_request_args', array( $this, 'request_method' ) );
	}

	/**
	 * Dynamically add the NLP tag fields to the Elasticsearch mapping
	 */
	public function configure_mapping() {
		$ep = Elasticsearch::factory();

		\add_filter( 'ep_query_request_path', array( $this, 'mapping_request_path' ) );
		\add_filter( 'http_request_args', array( $this, 'request_method' ) );

		$query = array(
			'properties' => array(
				'nlp_location' => array(
					'type' => 'text',
				),
			),
		);

		$index = Indexables::factory()->get( 'post' )->get_index_name();

		$ep->query( $index, null, $query, array() );

		\remove_filter( 'ep_query_request_path', array( $this, 'mapping_request_path' ) );
		\remove_filter( 'http_request_args', array( $this, 'request_method' ) );
	}

	/**
	 * Append Ingester to POST indexing
	 *
	 * @param string $path the indexing endpoint
	 *
	 * @return string
	 *
	 * @uses ep_bulk_index_request_path
	 * @uses ep_index_request_path
	 */
	public function append_ingester_to_index_endpoint( $path ) {
		return $path . '?pipeline=' . $this->pipeline_name;
	}

	// TODO: save tags back to WP

	/**
	 * Gets the ingester path formatter for ES requests
	 *
	 * @return string
	 *
	 * @uses ep_query_request_path
	 */
	public function ingester_request_path() {
		return '_ingest/pipeline/' . $this->pipeline_name;
	}

	/**
	 * Gets the mapping request path
	 *
	 * @return string
	 *
	 * @uses ep_query_request_path
	 */
	public function mapping_request_path() {
		return '_mapping';
	}

	/**
	 * Change the request method to PUT when configuring the ingest pipeline
	 *
	 * @param array $parsed_args An array of HTTP request arguments.
	 *
	 * @return array
	 *
	 * @uses http_request_args
	 */
	public function request_method( $args ) {
		$args['method'] = 'PUT';

		return $args;
	}
}

$plugin = new ENLPTools();
$plugin->init();