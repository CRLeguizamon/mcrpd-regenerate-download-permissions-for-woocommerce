<?php
/**
 * Admin Class
 *
 * @package MCRPD_Regenerate_Download_Permissions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MCRPD_Admin
 */
class MCRPD_Admin {

	/**
	 * Initialize the admin.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page to WooCommerce.
	 */
	public static function add_menu_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			esc_html__( 'Download Permissions Regenerator', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
			esc_html__( 'Download Perms Regen', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
			'manage_woocommerce',
			'mcrpd-download-perms-regen',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ) );
		}

		// Save settings.
		if ( isset( $_POST['mcrpd_save_settings'] ) ) {
			check_admin_referer( 'mcrpd_save_settings_action', 'mcrpd_save_settings_nonce' );

			$product_ids_csv = isset( $_POST['product_ids_csv'] ) ? sanitize_text_field( wp_unslash( $_POST['product_ids_csv'] ) ) : '';
			// Handle array for order_statuses (multiselect).
			$raw_statuses = isset( $_POST['order_statuses'] ) ? (array) $_POST['order_statuses'] : array();
			
			// Sanitize each key in the array.
			$clean_statuses = array_map( 'sanitize_key', wp_unslash( $raw_statuses ) );
			$order_statuses = implode( ',', $clean_statuses );

			// Batch size — sanitize and clamp between 1 and 500.
			$batch_size = isset( $_POST['mcrpd_batch_size'] ) ? absint( wp_unslash( $_POST['mcrpd_batch_size'] ) ) : 20;
			$batch_size = max( 1, min( 500, $batch_size ) );

			$settings = array(
				'product_ids_csv' => $product_ids_csv,
				'order_statuses'  => $order_statuses,
				'batch_size'      => $batch_size,
			);

			update_option( mcrpd_option_key(), $settings );

			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ) . '</p></div>';
		}

		$settings   = mcrpd_get_settings();
		$batch_size = mcrpd_get_batch_size();
		
		// Get WC statuses.
		$wc_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		
		// Current selected statuses.
		$current_statuses = array_map( 'trim', explode( ',', $settings['order_statuses'] ) );
		?>
		<div class="wrap mcrpd-wrap">
			<h1><?php esc_html_e( 'Download Permissions Regenerator', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></h1>
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: Batch size */
						__( 'This tool scans orders and regenerates downloadable permissions in <strong>AJAX batches of %s orders</strong>. It only updates orders that contain any of the configured Product IDs.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
						esc_html( $batch_size )
					)
				);
				?>
			</p>

			<form method="post">
				<?php wp_nonce_field( 'mcrpd_save_settings_action', 'mcrpd_save_settings_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="product_ids_csv"><?php esc_html_e( 'Product IDs (comma separated)', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="product_ids_csv" name="product_ids_csv" value="<?php echo esc_attr( $settings['product_ids_csv'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="order_statuses"><?php esc_html_e( 'Order status', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></label></th>
						<td>
							
							<div class="mcrpd-status-group">
								<?php foreach ( $wc_statuses as $status_key => $status_label ) : ?>
									<?php $is_checked = in_array( $status_key, $current_statuses, true ); ?>
									<label class="mcrpd-status-option <?php echo $is_checked ? 'checked' : ''; ?>">
										<input type="checkbox" name="order_statuses[]" value="<?php echo esc_attr( $status_key ); ?>" <?php checked( $is_checked, true ); ?>>
										<?php echo esc_html( $status_label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
							
							<p class="description">
								<?php esc_html_e( 'Select the order statuses to process.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mcrpd_batch_size"><?php esc_html_e( 'Batch size', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></label></th>
						<td>
							<input type="number" id="mcrpd_batch_size" name="mcrpd_batch_size" value="<?php echo esc_attr( $batch_size ); ?>" min="1" max="500" step="1">
							<p class="description">
								<?php esc_html_e( 'Number of orders to process per AJAX request (1–500).', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?>
							</p>
							<div class="mcrpd-batch-warning">
								<span class="dashicons dashicons-warning"></span>
								<?php esc_html_e( 'The batch size depends on your server capacity. The recommended value is 20. Increasing it may cause timeouts or memory errors on shared hosting. If you experience errors during regeneration, try reducing the batch size.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?>
							</div>
						</td>
					</tr>
				</table>

				<p>
					<?php submit_button( __( 'Save settings', 'mcrpd-regenerate-download-permissions-for-woocommerce' ), 'secondary', 'mcrpd_save_settings', false ); ?>
				</p>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Run', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'First you must save changes before executing:', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?>
			</p>
			<p>
				<button id="mcrpd-start" class="button button-primary"><?php esc_html_e( 'Start regeneration', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></button>
				<button id="mcrpd-continue" class="button mcrpd-btn-continue mcrpd-continue-hidden"><?php esc_html_e( 'Continue', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></button>
				<button id="mcrpd-stop" class="button"><?php esc_html_e( 'Stop', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></button>
			</p>

			<p><strong><?php esc_html_e( 'Progress', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?></strong></p>
			<ul class="mcrpd-progress">
				<li><?php esc_html_e( 'Page:', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?> <span id="mcrpd-page">0</span> / <span id="mcrpd-max-pages">0</span></li>
				<li><?php esc_html_e( 'Orders scanned:', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?> <span id="mcrpd-scanned">0</span></li>
				<li><?php esc_html_e( 'Orders updated:', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?> <span id="mcrpd-updated">0</span></li>
			</ul>

			<div id="mcrpd-log"></div>

			<div class="mcrpd-footer-box">
				<p>
					<?php esc_html_e( 'This plugin is in constant evolution. If you have ideas or suggestions, do not hesitate to contact me at', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?>
					<a href="mailto:hola@devcristian.com">hola@devcristian.com</a>.
					<?php esc_html_e( 'Your collaboration is highly valued!', 'mcrpd-regenerate-download-permissions-for-woocommerce' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue_scripts( $hook ) {
		if ( 'woocommerce_page_mcrpd-download-perms-regen' !== $hook ) {
			return;
		}

		// Styles.
		wp_register_style( 'mcrpd-admin-css', MCRPD_URL . 'assets/css/mcrpd-admin.css', array(), MCRPD_VERSION );
		wp_enqueue_style( 'mcrpd-admin-css' );

		// Scripts.
		wp_register_script( 'mcrpd-admin-js', MCRPD_URL . 'assets/js/mcrpd-admin.js', array( 'jquery' ), MCRPD_VERSION, true );
		wp_enqueue_script( 'mcrpd-admin-js' );

		$vars = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mcrpd_regen_ajax_nonce' ),
			'strings' => array(
				'error'              => __( 'Error:', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'unknown'            => __( 'Unknown error', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'done'               => __( 'Done.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'starting'           => __( 'Starting...', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'stopped'            => __( 'Stopped.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'ajaxFailed'         => __( 'AJAX failed. Your progress has been saved — use the "Continue" button to resume.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'notRunning'         => __( 'Not running.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'continuing'         => __( 'Continuing from page %s...', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'continueNoProgress' => __( 'No saved progress found. Use "Start regeneration" instead.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'progressSaved'      => __( 'Progress saved. You can close this page and resume later using the "Continue" button.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
				'progressExpired'    => __( 'Saved progress has expired. Starting fresh.', 'mcrpd-regenerate-download-permissions-for-woocommerce' ),
			),
		);

		wp_add_inline_script(
			'mcrpd-admin-js',
			'window.mcrpdRegen=' . wp_json_encode( $vars ) . ';',
			'before'
		);
	}
}
