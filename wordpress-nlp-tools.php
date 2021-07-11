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
use ParagonIE\Sodium\Core\Curve25519\Ge\P1p1;

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
	public $models_to_map = array( 'dates', 'persons', 'locations' );

	public function __construct() {
	}

	public function init() {
		// core actions
		\add_action( 'activate_plugin', array( $this, 'activate' ) );
		\add_action( 'ep_after_bulk_index', array( $this, 'get_post_entities_from_es' ), 10, 3 );

		// elasticpress filters
		\add_filter( 'ep_index_request_path', array( $this, 'append_ingester_to_index_endpoint' ) );
		\add_filter( 'ep_bulk_index_request_path', array( $this, 'append_ingester_to_index_endpoint' ) );

		// custom actions
		\add_action( 'enlptools_configure_ingester', array( $this, 'configure_ingester' ) );
		// \add_action( 'enlptools_configure_mapping', array( $this, 'configure_mapping' ) );

		// custom filters
		\add_filter( 'enlptools_entity_sync_to', array( $this, 'sync_to' ), 10, 2 );

		$this->models_to_map = \apply_filters( 'enpltools_models_to_map', $this->models_to_map );
	}

	/**
	 * Example usage: maps any locations extracted using NLP to the Category taxonomy
	 *
	 * Extracted entities are saved in the `entities` key of the stored document in Elasticsearch
	 * so `entities.locations` contains all locations found in the document. However, this content
	 * only exists in Elasticsearch.
	 *
	 * With this method we are going to copy these locations to an existing taxonomy so they can be
	 * saved back to WordPress as categories.
	 *
	 * @param array $to
	 * @param string $entity
	 *
	 * @return mixed|string
	 */
	public function sync_to( array $to, string $entity ) {
		if ( 'locations' === $entity ) {
			return array(
				'taxonomy' => 'category',
			);
		}

		if ( 'persons' === $entity ) {
			return array(
				'meta_key' => 'persons',
			);
		}

		if ( 'dates' === $entity ) {
			return array(
				'meta_key' => 'dates',
			);
		}

		return $to;
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

		do_action( 'enlptools_configure_ingester' );
		// do_action( 'enlptools_configure_mapping' );
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
	 * Dynamically add the NLP tag fields to the Elasticsearch mapping without reindexing
	 *
	 * public function configure_mapping() {
	 * $ep    = Elasticsearch::factory();
	 * $index = Indexables::factory()->get( 'post' )->get_index_name();
	 * $query = $this->map_entities();
	 *
	 * \add_filter( 'ep_query_request_path', array( $this, 'mapping_request_path' ) );
	 * $ep->query( $index, null, $query, array() );
	 * \remove_filter( 'ep_query_request_path', array( $this, 'mapping_request_path' ) );
	 * }
	 *
	 */

	/**
	 * Map ingested entities for indexing
	 *
	 * @return mixed
	 */
	public function map_entities() {
		$entities        = array_flip( $this->models_to_map );
		$mapped_entities = array();

		foreach ( $entities as $entity ) {
			$map_to = \apply_filters( 'enlptools_entity_sync_to', array(), $entity );

			if ( $map_to ) {
				$mapped_entities[ $entity ] = $map_to;
			}
		}

		return $mapped_entities;
	}

	/**
	 * Prepares entities to be retrieved from Elasticsearch
	 *
	 * @return string
	 */
	public function prepare_entities_to_query() {

		$entities  = $this->get_mapped_entities();
		$ent_str[] = implode( ',', \array_keys( $entities ) );
		$ent_str[] = \implode( ',', \array_values( $entities ) );
		$ent_str   = \implode( ',', $ent_str );

		return $ent_str;

	}

	/**
	 * Retrieves a single document from Elasticsearch to save its entities to WP
	 *
	 * @param $document
	 * @param $return
	 */
	public function get_post_entities_from_es( $documents, $indexable, $return ) {
		$ep       = Elasticsearch::factory();
		$index    = Indexables::factory()->get( 'post' )->get_index_name();
		$entities = $this->prepare_entities_to_query();
		$body     = \json_encode( array( 'ids' => $documents ) );

		$request  = $ep->remote_request( $index . '/_mget?_source=' . $entities, $body );
		$response = \json_decode( $request['body'] );

		if ( ! empty( $response->docs ) ) {
			$this->save_entities_to_wp( $response->docs );
		}
	}

	/**
	 * Saves entities found in Elasticsearch back to WP
	 *
	 * @param $post_id
	 * @param $entities
	 */
	public function save_entities_to_wp( $docs ) {
		$entity_mapping = $this->get_mapped_entities();

		foreach ( $docs as $doc ) {
			if ( ! empty( $doc->_source->entities ) ) {
				$entities = $doc->_source->entities;

				foreach ( $entity_mapping as $entity => $mapping ) {
					$map = \explode( '.', $mapping );
					$ent = \explode( '.', $entity );

					if ( 'terms' === $map[0] && ! empty( $entities->{$ent[1]} ) ) {
						\wp_set_object_terms( $doc->_id, $entities->{$ent[1]}, $map[1], true );
					}
				}

			}
		}
	}

	public function get_mapped_entities() {
		$entities        = $this->models_to_map;
		$mapped_entities = array();

		foreach ( $entities as $key => $entity ) {
			if ( ! empty( $entity ) ) {
				$mapped_entities[ $key ] = $entity['sync_to'];
			}
		}

		return $mapped_entities;
	}

	/**
	 * Append our Custom Ingester to POST indexing
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
	 *
	 * public function mapping_request_path() {
	 * $index = Indexables::factory()->get( 'post' )->get_index_name();
	 *
	 * return $index . '/_mapping';
	 * }
	 *
	 */

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
		/*
		if ( \doing_action( 'enlptools_configure_mapping' ) ) {
			$args['method'] = 'GET';
		} else {
			$args['method'] = 'PUT';
		}
		*/
		$args['method'] = 'PUT';

		return $args;
	}
}

$plugin = new ENLPTools();
$plugin->init();