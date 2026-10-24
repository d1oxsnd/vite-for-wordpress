<?php
/**
 * Vite Integration for WordPress Theme
 *
 * Handles Vite Dev Server (HMR) and Production Manifest Enqueueing
 * for Frontend and Gutenberg / ACF Block Editor.
 *
 * @package Starter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Starter_Vite {
	/**
	 * Default Vite Dev Server URL
	 */
	const DEV_SERVER_URL = 'http://localhost:5173';

	/**
	 * Cached dev server status
	 *
	 * @var bool|null
	 */
	private static $is_dev_running = null;

	/**
	 * Cached manifest content
	 *
	 * @var array|null
	 */
	private static $manifest = null;

	/**
	 * Initialize hooks
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ), 20 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_type_attribute' ), 10, 3 );
	}

	/**
	 * Check if Vite Dev Server is running
	 *
	 * @return bool
	 */
	public static function is_dev(): bool {
		if ( null !== self::$is_dev_running ) {
			return self::$is_dev_running;
		}

		// Explicit override via wp-config.php constant if set
		if ( defined( 'VITE_DEVELOPMENT' ) ) {
			self::$is_dev_running = (bool) VITE_DEVELOPMENT;
			return self::$is_dev_running;
		}

		// Fast socket check for dev server port (0.05s timeout)
		$connection = @fsockopen( '127.0.0.1', 5173, $errno, $errstr, 0.05 );
		if ( is_resource( $connection ) ) {
			fclose( $connection );
			self::$is_dev_running = true;
		} else {
			self::$is_dev_running = false;
		}

		return self::$is_dev_running;
	}

	/**
	 * Load Vite manifest.json in production
	 *
	 * @return array
	 */
	private static function get_manifest(): array {
		if ( null !== self::$manifest ) {
			return self::$manifest;
		}

		$manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );

		// Fallback for older Vite versions
		if ( ! file_exists( $manifest_path ) ) {
			$manifest_path = get_theme_file_path( 'dist/manifest.json' );
		}

		if ( file_exists( $manifest_path ) ) {
			$content = file_get_contents( $manifest_path );
			self::$manifest = json_decode( $content, true ) ?: array();
		} else {
			self::$manifest = array();
		}

		return self::$manifest;
	}

	/**
	 * Enqueue Frontend Assets
	 */
	public static function enqueue_frontend_assets() {
		if ( self::is_dev() ) {
			// Enqueue Vite HMR client
			wp_enqueue_script(
				'vite-client',
				self::DEV_SERVER_URL . '/@vite/client',
				array(),
				null,
				false
			);

			// Enqueue main entrypoint (TS + SCSS)
			wp_enqueue_script(
				'starter-vite-main',
				self::DEV_SERVER_URL . '/src/main.ts',
				array( 'vite-client' ),
				null,
				true
			);
		} else {
			$manifest = self::get_manifest();
			$entry = 'src/main.ts';

			if ( ! empty( $manifest[ $entry ] ) ) {
				$data = $manifest[ $entry ];

				// Main JS
				if ( ! empty( $data['file'] ) ) {
					wp_enqueue_script(
						'starter-vite-main',
						get_theme_file_uri( 'dist/' . $data['file'] ),
						array(),
						null,
						true
					);
				}

				// Compiled CSS
				if ( ! empty( $data['css'] ) && is_array( $data['css'] ) ) {
					foreach ( $data['css'] as $i => $css_file ) {
						wp_enqueue_style(
							'starter-vite-main-' . $i,
							get_theme_file_uri( 'dist/' . $css_file ),
							array(),
							null
						);
					}
				}
			}
		}
	}

	/**
	 * Enqueue Gutenberg & ACF Block Editor Assets
	 */
	public static function enqueue_editor_assets() {
		if ( self::is_dev() ) {
			// Enqueue Vite HMR client if not already enqueued
			if ( ! wp_script_is( 'vite-client', 'enqueued' ) ) {
				wp_enqueue_script(
					'vite-client',
					self::DEV_SERVER_URL . '/@vite/client',
					array(),
					null,
					false
				);
			}

			// Enqueue editor entrypoint (TS + SCSS for Gutenberg/ACF)
			wp_enqueue_script(
				'starter-vite-editor',
				self::DEV_SERVER_URL . '/src/editor.ts',
				array( 'vite-client', 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
				null,
				true
			);
		} else {
			$manifest = self::get_manifest();
			$entry = 'src/editor.ts';

			if ( ! empty( $manifest[ $entry ] ) ) {
				$data = $manifest[ $entry ];

				// Editor JS
				if ( ! empty( $data['file'] ) ) {
					wp_enqueue_script(
						'starter-vite-editor',
						get_theme_file_uri( 'dist/' . $data['file'] ),
						array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
						null,
						true
					);
				}

				// Editor CSS
				if ( ! empty( $data['css'] ) && is_array( $data['css'] ) ) {
					foreach ( $data['css'] as $i => $css_file ) {
						wp_enqueue_style(
							'starter-vite-editor-' . $i,
							get_theme_file_uri( 'dist/' . $css_file ),
							array( 'wp-edit-blocks' ),
							null
						);
					}
				}
			}
		}
	}

	/**
	 * Filter script tag to add type="module" for Vite scripts
	 *
	 * @param string $tag Script HTML tag.
	 * @param string $handle Script handle.
	 * @param string $src Script URL.
	 * @return string
	 */
	public static function add_type_attribute( string $tag, string $handle, string $src ): string {
		$module_handles = array(
			'vite-client',
			'starter-vite-main',
			'starter-vite-editor',
		);

		if ( in_array( $handle, $module_handles, true ) ) {
			return sprintf(
				'<script type="module" src="%s" id="%s-js"></script>' . "\n",
				esc_url( $src ),
				esc_attr( $handle )
			);
		}

		return $tag;
	}

	/**
	 * Get resolved URL for an asset (supports Dev Server and Production manifest)
	 *
	 * @param string $path Path relative to theme, e.g. 'src/images/sample.png'.
	 * @return string
	 */
	public static function asset_url( string $path ): string {
		$clean_path = ltrim( $path, '/' );

		// In development: serve from Vite dev server
		if ( self::is_dev() ) {
			return self::DEV_SERVER_URL . '/' . $clean_path;
		}

		// In production: check manifest for hashed filename
		$manifest = self::get_manifest();
		if ( ! empty( $manifest[ $clean_path ]['file'] ) ) {
			return get_theme_file_uri( 'dist/' . $manifest[ $clean_path ]['file'] );
		}

		// Fallback to theme relative file
		return get_theme_file_uri( $clean_path );
	}
}

Starter_Vite::init();

/**
 * Global helper to get Vite asset URL in PHP templates (header.php, single.php, etc.)
 *
 * Example:
 * <img src="<?php echo esc_url( vite_asset( 'src/images/sample.png' ) ); ?>" alt="Sample">
 *
 * @param string $path
 * @return string
 */
if ( ! function_exists( 'vite_asset' ) ) {
	function vite_asset( string $path ): string {
		return Starter_Vite::asset_url( $path );
	}
}
