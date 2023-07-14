<?php
/**
 * Core class
 *
 * @package UM_WPCLI\Commands
 */

namespace UM_WPCLI\Commands;

if ( defined( 'UM_IS_EXTENDED' ) ) {
	define( 'UM_WPCLI_PLUGIN_DIR', UM_EXTENDED_PLUGIN_DIR . 'src/um-wpcli/src/' );
}

/**
 * Class Core to handle all plugin initialization.
 *
 * @since 1.0.0
 */
class Developer {

	/**
	 * Init
	 *
	 * @param string $file Filename.
	 */
	public function __construct( $file = null ) {

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'um dev scaffold', array( $this, 'scaffold' ) );
		}
	}

	/**
	 * Create Scaffold
	 * Command: wp um dev scaffold <namespace>
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associated arguments.
	 */
	public function scaffold( $args, $assoc_args ) {
		if ( ! isset( $args[0] ) ) {
			\WP_CLI::error( /* translators: Namespace is required. e.g user=123 */ __( 'Namespace is required for scaffold e.g. `wp um dev My_Namespace`', 'ultimate-member' ) );
			return;
		}
		$namespace = $args[0];
		if ( class_exists( $namespace . '\Core' ) ) {
			\WP_CLI::error( /* translators: Namespace is already in use */ __( 'Namespace is already in use', 'ultimate-member' ) );
			return;
		}

		$dir = str_replace( '_', '-', strtolower( $namespace ) );
		if ( strpos( $dir, 'um-' ) > -1 ) {
			$directory = 'src/' . $dir;
		} else {
			$directory = 'src/um-' . $dir;
		}

		$directory = str_replace( 'um-extended', 'um', $directory );

		mkdir( UM_EXTENDED_PLUGIN_DIR . $directory );
		$core_dir = UM_EXTENDED_PLUGIN_DIR . $directory . '/src';
		mkdir( $core_dir );

		// Update root composer.json file.
		$this->handle( $namespace, $directory );
		$this->create_core_file( $namespace, $core_dir );
		\WP_CLI::success( /* translators: Created new project succesfully  */ sprintf( __( 'Created new project succesfully. Please run `composer update` in `%s`', 'ultimate-member' ), UM_EXTENDED_PLUGIN_DIR ) );
	}

	/**
	 * Scaffold
	 *
	 * @param string $namespace Namespace.
	 * @param string $directory Directory source.
	 * @param string $output File output.
	 */
	public function handle( $namespace, $directory, $output = 'composer.json' ) {

		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$file = UM_EXTENDED_PLUGIN_DIR . 'composer.json';
		$data = json_decode( $wp_filesystem->get_contents( $file ), true );

		$data['autoload']['psr-4'][ $namespace . '\\' ] = $directory . '/src/';
		$wp_filesystem->put_contents( $file, wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
	}

	/**
	 * Create core file
	 *
	 * @param string $namespace Namespace.
	 * @param string $directory Directory path.
	 */
	public function create_core_file( $namespace, $directory ) {

		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$tmpl    = $wp_filesystem->get_contents( UM_WPCLI_PLUGIN_DIR . 'Templates/Developer/Core.txt' );
		$content = str_replace( '{namespace}', $namespace, $tmpl );
		if ( ! $wp_filesystem->put_contents( $directory . '/Core.php', $content, 0644 ) ) {
			return wp_die( esc_attr( 'Failed to create core files for namespace ' . $namespace ) );
		}
	}
}
