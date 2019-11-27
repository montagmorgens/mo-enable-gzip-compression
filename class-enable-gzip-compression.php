<?php
/**
 * Enable GZIP compression
 *
 * @package     Enable_GZIP_Compression
 * @author      MONTAGMORGENS GmbH
 * @copyright   2019 MONTAGMORGENS GmbH
 *
 * @wordpress-plugin
 * Plugin Name: MONTAGMORGENS Enable GZIP compression
 * Description: Dieses Plugin aktiviert GZIP-Komprimierung auf Apache-Webservern.
 * Version:     1.0.0
 * Author:      MONTAGMORGENS GmbH
 * Author URI:  https://www.montagmorgens.com/
 * License:     GNU General Public License v.2
 * Text Domain: mo-gzip
 * GitHub Plugin URI: montagmorgens/mo-enable-gzip-compression
 */

namespace Mo\Gzip;

// Don't call this file directly.
defined( 'ABSPATH' ) || die();

// Bail if not on admin screen.
if ( ! is_admin() ) {
	return;
}

// Register hooks
\register_deactivation_hook( __FILE__, '\Mo\Gzip\Enable_GZIP_Compression::on_deactivation' );

// Init plugin instance.
\add_action( 'plugins_loaded', '\Mo\Gzip\Enable_GZIP_Compression::get_instance' );

/**
 * Plugin code.
 *
 * @var object|null $instance The plugin singleton.
 */
final class Enable_GZIP_Compression {
	const PLUGIN_VERSION = '1.0.0';
	protected static $instance = null;

	private $htaccess_file = false;
	private $has_code = false;
	private $uid_start = 'MO_ENABLE_GZIP_COMPRESSION_BEGIN';
	private $uid_end = 'MO_ENABLE_GZIP_COMPRESSION_END';

	/**
	 * Gets a singelton instance of our plugin.
	 *
	 * @return Enable_GZIP_Compression
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {

		// Get .htaccess file path
		$this->htaccess_file = $this->get_htaccess_file();

		// .htaccess file exist.
		if ( $this->htaccess_file && ! $this->has_code ) {

			// Add compression code to .htaccess file.
			$htaccess_content = file_get_contents( $this->htaccess_file );
			$htaccess_content = $htaccess_content . $this->get_compression_code();

			file_put_contents( $this->htaccess_file, $htaccess_content );
		}

	}

	private function get_htaccess_file() {
		// Get .htaccess file path
		$htaccess_file = wp_normalize_path( ABSPATH . '.htaccess' );

		// .htaccess file exist.
		if ( file_exists( $htaccess_file ) ) {

			// .htaccess file is readable and writable.
			if ( is_readable( $htaccess_file ) && is_writable( $htaccess_file ) ) {

				// Check if identifier is already present in .htaccess file.
				$htaccess_content = file_get_contents( $htaccess_file );

				// Identifier was found in .htaccess.
				if ( strpos( $htaccess_content, $this->uid_start ) !== false ) {
					$this->has_code = true;
				}

				return $htaccess_file;
			}

			// .htaccess file is not readable or writable.
			else {
				add_action( 'admin_notices', array( $this, 'notice_htaccess_unwritable' ) );
			}
		}

		// .htaccess doesn't exist.
		else {
			add_action( 'admin_notices', array( $this, 'notice_htaccess_missing' ) );
		}

		return false;
	}

	/**
	 * Codes to be add.
	 */
	private function get_compression_code() {
		$code  = "\n";
		$code .= '# ' . $this->uid_start . "\n";
		$code .= '# Enable GZIP Compression' . "\n";
		$code .= '<IfModule mod_deflate.c>' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/javascript' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/rss+xml' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/vnd.ms-fontobject' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/x-font' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/x-font-opentype' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/x-font-otf' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/x-font-truetype' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/x-font-ttf' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/x-javascript' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/xhtml+xml' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE application/xml' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE font/opentype' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE font/otf' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE font/ttf' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE image/svg+xml' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE image/x-icon' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE text/css' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE text/html' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE text/javascript' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE text/plain' . "\n";
		$code .= 'AddOutputFilterByType DEFLATE text/xml' . "\n";
		$code .= '</IfModule>' . "\n";
		$code .= '# ' . $this->uid_end . "\n";

		return $code;
	}

	/**
	 * Runs on plugin deactivation
	 */
	public static function on_deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Proceed only if compression code is found to be present.
		if ( self::$instance->htaccess_file && self::$instance->has_code ) {

			// Remove compression code from .htaccess file.
			$htaccess_content = file_get_contents( self::$instance->htaccess_file );
			$pattern = '/#\s?' . self::$instance->uid_start . '.*?' . self::$instance->uid_end . '/s';
			$htaccess_content = preg_replace( $pattern, '', $htaccess_content );
			$htaccess_content = preg_replace( "/\n+/", "\n", $htaccess_content );
			file_put_contents( self::$instance->htaccess_file, $htaccess_content );
		}
	}

	/**
	 * If htaccess is not exists.
	 */
	public function notice_htaccess_missing() {
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong><br>%s</p></div>',
			\esc_html( __( 'Plugin MONTAGMORGENS Enable GZIP compression', 'mo-gzip' ) ),
			\esc_html( __( 'Die .htaccess-Datei konnte nicht gefunden werden. Dieses Plugin funktioniert nur auf Apache-Servern. Wenn Sie Apache benutzen, legen Sie die .htaccess-Datei bitte neu an.', 'mo-gzip' ) )
		);
	}

	/**
	 * If htaccess is not access able.
	 */
	public function notice_htaccess_unwritable() {
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong><br>%s</p></div>',
			\esc_html( __( 'Plugin MONTAGMORGENS Enable GZIP compression', 'mo-gzip' ) ),
			\esc_html( __( 'Die .htaccess-Datei ist nicht beschreibar. Bitte setzen Sie die korrekten Dateiberechtigungen.', 'mo-gzip' ) )
		);
	}

}
