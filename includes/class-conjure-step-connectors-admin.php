<?php
/**
 * Admin screen for step connectors.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure step connectors admin class.
 */
class Conjure_Step_Connectors_Admin {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'ConjureWP-steps';

	/**
	 * Main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Connector manager instance.
	 *
	 * @var Conjure_Step_Connector_Manager
	 */
	protected $connector_manager;

	/**
	 * Constructor.
	 *
	 * @param Conjure                       $conjure           Main Conjure instance.
	 * @param Conjure_Step_Connector_Manager $connector_manager Connector manager.
	 */
	public function __construct( $conjure, $connector_manager ) {
		$this->conjure           = $conjure;
		$this->connector_manager = $connector_manager;

		add_action( 'admin_menu', array( $this, 'add_page' ), 98 );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_conjure_save_connector_setting', array( $this, 'ajax_save_connector_setting' ) );
		add_action( 'wp_ajax_conjure_save_step_order', array( $this, 'ajax_save_step_order' ) );
		add_action( 'wp_ajax_conjure_get_wizard_order', array( $this, 'ajax_get_wizard_order' ) );
	}

	/**
	 * Add the admin page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'tools.php',
			__( 'ConjureWP Steps', 'ConjureWP' ),
			__( 'ConjureWP Steps', 'ConjureWP' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets for the steps admin page.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		$css_file = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/css/conjure-admin.min.css';
		$js_file  = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/js/conjure-admin.min.js';

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'conjure-admin',
				trailingslashit( $this->conjure->base_url ) . $this->conjure->directory . '/assets/css/conjure-admin.min.css',
				array(),
				(string) filemtime( $css_file )
			);
		}

		wp_enqueue_script( 'jquery-ui-sortable' );

		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'conjure-admin',
				trailingslashit( $this->conjure->base_url ) . $this->conjure->directory . '/assets/js/conjure-admin.min.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				(string) filemtime( $js_file ),
				true
			);

			wp_localize_script(
				'conjure-admin',
				'conjureAdminConnectors',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'conjurewp_save_connectors' ),
					'action'  => 'conjure_save_connector_setting',
					'strings' => array(
						'saveFailed'       => __( 'Could not save connector setting. Please try again.', 'ConjureWP' ),
						'orderSaving'      => __( 'Saving order...', 'ConjureWP' ),
						'orderSaveFailed'  => __( 'Could not save wizard order. Please try again.', 'ConjureWP' ),
						'orderSaved'       => __( 'Wizard order saved.', 'ConjureWP' ),
					),
				)
			);
		}
	}

	/**
	 * Save a single connector setting via AJAX.
	 *
	 * @return void
	 */
	public function ajax_save_connector_setting() {
		check_ajax_referer( 'conjurewp_save_connectors', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to change connector settings.', 'ConjureWP' ),
				),
				403
			);
		}

		$connector_id = isset( $_POST['connector_id'] ) ? sanitize_key( wp_unslash( $_POST['connector_id'] ) ) : '';
		$field        = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
		$feature_id   = isset( $_POST['feature_id'] ) ? sanitize_key( wp_unslash( $_POST['feature_id'] ) ) : '';
		$value        = ! empty( $_POST['value'] );

		if ( empty( $connector_id ) || ! in_array( $field, array( 'enabled', 'feature' ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid connector setting request.', 'ConjureWP' ),
				),
				400
			);
		}

		if ( 'feature' === $field && empty( $feature_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid connector setting request.', 'ConjureWP' ),
				),
				400
			);
		}

		$connectors = $this->connector_manager->get_connectors();

		if ( ! isset( $connectors[ $connector_id ] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unknown connector.', 'ConjureWP' ),
				),
				404
			);
		}

		$all_settings = $this->connector_manager->get_all_settings();

		if ( 'enabled' === $field ) {
			$all_settings[ $connector_id ]['enabled'] = $value;
		} else {
			if ( ! isset( $all_settings[ $connector_id ]['features'] ) || ! is_array( $all_settings[ $connector_id ]['features'] ) ) {
				$all_settings[ $connector_id ]['features'] = array();
			}

			$all_settings[ $connector_id ]['features'][ $feature_id ] = $value;
		}

		$saved_settings = $this->connector_manager->save_settings( $all_settings );

		wp_send_json_success(
			array(
				'message'          => __( 'Saved.', 'ConjureWP' ),
				'settings'         => isset( $saved_settings[ $connector_id ] ) ? $saved_settings[ $connector_id ] : array(),
				'wizard_order_html' => $this->get_wizard_order_list_html(),
				'wizard_step_count' => $this->get_wizard_order_step_count(),
			)
		);
	}

	/**
	 * Save wizard step order via AJAX.
	 *
	 * @return void
	 */
	public function ajax_save_step_order() {
		check_ajax_referer( 'conjurewp_save_connectors', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to change wizard order.', 'ConjureWP' ),
				),
				403
			);
		}

		$submitted_step_order = isset( $_POST['step_order'] ) && is_array( $_POST['step_order'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['step_order'] ) )
			: array();

		if ( empty( $submitted_step_order ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No wizard steps were submitted.', 'ConjureWP' ),
				),
				400
			);
		}

		$saved_order = $this->connector_manager->save_step_order( $submitted_step_order );

		wp_send_json_success(
			array(
				'message'    => __( 'Wizard order saved.', 'ConjureWP' ),
				'step_order' => $saved_order,
			)
		);
	}

	/**
	 * Return refreshed wizard order markup via AJAX.
	 *
	 * @return void
	 */
	public function ajax_get_wizard_order() {
		check_ajax_referer( 'conjurewp_save_connectors', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to view wizard order.', 'ConjureWP' ),
				),
				403
			);
		}

		wp_send_json_success(
			array(
				'html'              => $this->get_wizard_order_list_html(),
				'wizard_step_count' => $this->get_wizard_order_step_count(),
			)
		);
	}

	/**
	 * Handle admin actions.
	 *
	 * @return void
	 */
	public function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->is_steps_admin_request() ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'export' === $action ) {
			check_admin_referer( 'conjurewp_export_steps' );
			$this->export_json();
		}

		if ( ! empty( $_POST['conjurewp_preview_connectors'] ) ) {
			check_admin_referer( 'conjurewp_save_connectors' );
			$this->preview_connector_settings();
		}

		if ( ! empty( $_POST['conjurewp_save_connectors'] ) ) {
			check_admin_referer( 'conjurewp_save_connectors' );
			$this->save_connector_settings();
		}

		if ( ! empty( $_POST['conjurewp_import_steps'] ) ) {
			check_admin_referer( 'conjurewp_import_steps' );
			$this->import_json();
		}
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		$notice = get_transient( 'conjurewp_steps_admin_notice' );

		if ( empty( $notice ) || ! is_array( $notice ) ) {
			return;
		}

		delete_transient( 'conjurewp_steps_admin_notice' );
		$type = ! empty( $notice['type'] ) ? sanitize_html_class( $notice['type'] ) : 'success';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		$wizard_order_steps      = $this->get_wizard_order_steps();
		$connectors              = $this->connector_manager->get_admin_connector_data();
		$reconciliation          = $this->connector_manager->get_connector_reconciliation();
		$connectors_require_pro  = ! empty( $reconciliation['connectors_require_pro'] );
		$has_pro_plugin_access   = ! empty( $reconciliation['has_pro_plugin_access'] );
		$pro_plugin_price_label  = ! empty( $reconciliation['pro_plugin_price_label'] ) ? $reconciliation['pro_plugin_price_label'] : '';
		$pro_upgrade_url         = class_exists( 'Conjure_Premium_Features' ) ? Conjure_Premium_Features::get_upgrade_url() : '';
		$steps                   = $wizard_order_steps;
		$connector_count         = count( $connectors );
		$active_connector_count  = 0;
		$total_feature_count     = 0;
		$active_feature_count    = 0;
		$current_step_count      = count( $steps );
		$conjure_version         = defined( 'CONJURE_VERSION' ) ? CONJURE_VERSION : '1.0.0';

		foreach ( $connectors as $connector ) {
			if ( ! empty( $connector['settings']['enabled'] ) ) {
				++$active_connector_count;
			}

			if ( empty( $connector['features'] ) ) {
				continue;
			}

			foreach ( $connector['features'] as $feature ) {
				++$total_feature_count;

				if ( ! empty( $feature['saved_enabled'] ) ) {
					++$active_feature_count;
				}
			}
		}

		$initial_active_tab = $this->get_initial_active_tab();
		$form_action        = admin_url( 'tools.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap conjure-admin-page">
			<div class="conjure-admin-shell">
				<aside class="conjure-admin-sidebar-nav">
					<div class="conjure-admin-brand">
						<div class="conjure-admin-brand-icon">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
						</div>
						<div>
							<div class="conjure-admin-brand-name"><?php esc_html_e( 'ConjureWP', 'ConjureWP' ); ?></div>
							<span class="conjure-admin-brand-version"><?php echo esc_html( 'v' . $conjure_version ); ?></span>
						</div>
					</div>

					<nav class="conjure-admin-nav" aria-label="<?php esc_attr_e( 'Page sections', 'ConjureWP' ); ?>" role="tablist" aria-orientation="vertical">
						<button type="button" id="conjure-tab-overview" class="conjure-admin-nav-link <?php echo $this->is_admin_tab_active( 'conjure-overview', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-nav-link" data-panel="conjure-overview" role="tab" aria-selected="<?php echo $this->is_admin_tab_active( 'conjure-overview', $initial_active_tab ) ? 'true' : 'false'; ?>" aria-controls="conjure-overview">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
							<span><?php esc_html_e( 'Dashboard', 'ConjureWP' ); ?></span>
						</button>

						<button type="button" id="conjure-tab-connectors" class="conjure-admin-nav-link <?php echo $this->is_admin_tab_active( 'conjure-connectors', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-nav-link" data-panel="conjure-connectors" role="tab" aria-selected="<?php echo $this->is_admin_tab_active( 'conjure-connectors', $initial_active_tab ) ? 'true' : 'false'; ?>" aria-controls="conjure-connectors">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
							<span><?php esc_html_e( 'Connectors', 'ConjureWP' ); ?></span>
						</button>

						<button type="button" id="conjure-tab-steps" class="conjure-admin-nav-link <?php echo $this->is_admin_tab_active( 'conjure-steps', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-nav-link" data-panel="conjure-steps" role="tab" aria-selected="<?php echo $this->is_admin_tab_active( 'conjure-steps', $initial_active_tab ) ? 'true' : 'false'; ?>" aria-controls="conjure-steps">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>
							<span><?php esc_html_e( 'Wizard Order', 'ConjureWP' ); ?></span>
						</button>

						<div class="conjure-admin-nav-divider"></div>

						<button type="button" id="conjure-tab-import" class="conjure-admin-nav-link <?php echo $this->is_admin_tab_active( 'conjure-import', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-nav-link" data-panel="conjure-import" role="tab" aria-selected="<?php echo $this->is_admin_tab_active( 'conjure-import', $initial_active_tab ) ? 'true' : 'false'; ?>" aria-controls="conjure-import">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
							<span><?php esc_html_e( 'Import / Export', 'ConjureWP' ); ?></span>
						</button>

						<button type="button" id="conjure-tab-save" class="conjure-admin-nav-link <?php echo $this->is_admin_tab_active( 'conjure-save', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-nav-link" data-panel="conjure-save" role="tab" aria-selected="<?php echo $this->is_admin_tab_active( 'conjure-save', $initial_active_tab ) ? 'true' : 'false'; ?>" aria-controls="conjure-save">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
							<span><?php esc_html_e( 'Settings', 'ConjureWP' ); ?></span>
						</button>
					</nav>

					<div class="conjure-admin-sidebar-footer">
						<a href="<?php echo esc_url( $this->conjure->get_wizard_url() ); ?>" class="conjure-admin-button conjure-admin-button--primary conjure-admin-button--full">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
							<?php esc_html_e( 'Open Wizard', 'ConjureWP' ); ?>
						</a>
					</div>
				</aside>

				<div class="conjure-admin-content">
					<section class="conjure-admin-tab-panel <?php echo $this->is_admin_tab_active( 'conjure-overview', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-panel" id="conjure-overview" role="tabpanel" aria-labelledby="conjure-tab-overview"<?php echo $this->is_admin_tab_active( 'conjure-overview', $initial_active_tab ) ? '' : ' hidden'; ?>>
						<header class="conjure-admin-page-header">
							<p class="conjure-admin-kicker"><?php esc_html_e( 'Connector Control Centre', 'ConjureWP' ); ?></p>
							<h1 class="conjure-admin-title"><?php esc_html_e( 'ConjureWP Steps', 'ConjureWP' ); ?></h1>
							<p class="conjure-admin-subtitle"><?php esc_html_e( 'Manage connector steps, choose exactly which features appear in the wizard, and drag steps into the order you want users to follow.', 'ConjureWP' ); ?></p>
						</header>

						<div class="conjure-admin-stats">
							<div class="conjure-admin-stat-card">
								<span class="conjure-admin-stat-label"><?php esc_html_e( 'Detected Connectors', 'ConjureWP' ); ?></span>
								<strong class="conjure-admin-stat-value"><?php echo esc_html( (string) $connector_count ); ?></strong>
								<p class="conjure-admin-stat-copy"><?php esc_html_e( 'Connectors currently available in the admin.', 'ConjureWP' ); ?></p>
							</div>
							<div class="conjure-admin-stat-card">
								<span class="conjure-admin-stat-label"><?php esc_html_e( 'Active Features', 'ConjureWP' ); ?></span>
								<strong class="conjure-admin-stat-value"><?php echo esc_html( $active_feature_count . ' / ' . $total_feature_count ); ?></strong>
								<p class="conjure-admin-stat-copy"><?php esc_html_e( 'Connector feature toggles currently enabled.', 'ConjureWP' ); ?></p>
							</div>
							<div class="conjure-admin-stat-card">
								<span class="conjure-admin-stat-label"><?php esc_html_e( 'Current Wizard Steps', 'ConjureWP' ); ?></span>
								<strong class="conjure-admin-stat-value js-conjure-wizard-step-count"><?php echo esc_html( (string) $current_step_count ); ?></strong>
								<p class="conjure-admin-stat-copy">
									<?php
									printf(
										esc_html(
											/* translators: %s: number of active connectors */
											__( '%s connectors are currently enabled.', 'ConjureWP' )
										),
										esc_html( number_format_i18n( $active_connector_count ) )
									);
									?>
								</p>
							</div>
						</div>
					</section>

					<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="conjure-admin-form" id="conjure-admin-settings-form">
						<?php wp_nonce_field( 'conjurewp_save_connectors' ); ?>
						<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
						<input type="hidden" name="conjurewp_steps_page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
						<input type="hidden" name="conjurewp_save_connectors" value="1">
						<input type="hidden" name="conjurewp_active_tab" id="conjurewp-active-tab" value="<?php echo esc_attr( $initial_active_tab ); ?>">

						<section class="conjure-admin-section conjure-admin-tab-panel <?php echo $this->is_admin_tab_active( 'conjure-connectors', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-panel" id="conjure-connectors" role="tabpanel" aria-labelledby="conjure-tab-connectors"<?php echo $this->is_admin_tab_active( 'conjure-connectors', $initial_active_tab ) ? '' : ' hidden'; ?>>
							<div class="conjure-admin-panel">
								<div class="conjure-admin-panel-header">
									<div>
										<h2 class="conjure-admin-panel-title"><?php esc_html_e( 'Connectors', 'ConjureWP' ); ?></h2>
										<p class="conjure-admin-panel-copy"><?php esc_html_e( 'Activate only the connectors you need — each activated connector appears in Wizard Order. Use the feature toggles to control what each live wizard step includes.', 'ConjureWP' ); ?></p>
										<?php if ( $connectors_require_pro ) : ?>
											<p class="conjure-admin-panel-copy conjure-admin-panel-copy--pro">
												<?php
												printf(
													/* translators: %s: ConjureWP Pro annual price */
													esc_html__( 'Connectors are included with ConjureWP Pro (%s per site). You can configure them here anytime; they join the wizard once your Pro licence is active.', 'ConjureWP' ),
													esc_html( $pro_plugin_price_label )
												);
												?>
											</p>
										<?php endif; ?>
									</div>
									<?php if ( $connectors_require_pro && ! $has_pro_plugin_access && $pro_upgrade_url ) : ?>
										<a href="<?php echo esc_url( $pro_upgrade_url ); ?>" class="conjure-admin-button conjure-admin-button--primary">
											<?php esc_html_e( 'Upgrade to ConjureWP Pro', 'ConjureWP' ); ?>
										</a>
									<?php endif; ?>
								</div>

								<?php if ( $connectors_require_pro && ! $has_pro_plugin_access ) : ?>
									<div class="conjure-admin-notice conjure-admin-notice--warning">
										<p>
											<?php
											printf(
												/* translators: %s: ConjureWP Pro annual price */
												esc_html__( 'You can activate and deactivate connectors below to plan your setup. Activated connectors appear in Wizard Order immediately; ConjureWP Pro (%s) is required before they join the live wizard.', 'ConjureWP' ),
												esc_html( $pro_plugin_price_label )
											);
											?>
										</p>
									</div>
								<?php endif; ?>

								<?php if ( empty( $connectors ) ) : ?>
									<div class="conjure-admin-empty-state">
										<p><?php esc_html_e( 'No step connectors are currently available.', 'ConjureWP' ); ?></p>
									</div>
								<?php else : ?>
									<div class="conjure-admin-connector-grid">
										<?php foreach ( $connectors as $connector ) : ?>
											<div class="conjure-admin-connector-card" data-connector-id="<?php echo esc_attr( $connector['id'] ); ?>">
												<div class="conjure-admin-connector-header">
													<div class="conjure-admin-connector-copy">
														<div class="conjure-admin-card-heading-row">
															<h3 class="conjure-admin-card-title"><?php echo esc_html( $connector['name'] ); ?></h3>
															<span class="conjure-admin-badge <?php echo esc_attr( $this->get_readiness_badge_class( $connector['readiness'] ) ); ?>">
																<?php echo esc_html( $connector['readiness']['label'] ); ?>
															</span>
														</div>
														<?php if ( ! empty( $connector['readiness']['tier_label'] ) ) : ?>
															<p class="conjure-admin-card-meta">
																<span class="conjure-admin-badge conjure-admin-badge--muted">
																	<?php echo esc_html( $connector['readiness']['tier_label'] ); ?>
																</span>
																<span class="conjure-admin-badge <?php echo esc_attr( $this->get_plugin_badge_class( $connector['plugin_status'] ) ); ?>">
																	<?php echo esc_html( $connector['plugin_status']['label'] ); ?>
																</span>
															</p>
														<?php endif; ?>
														<?php if ( ! empty( $connector['description'] ) ) : ?>
															<p class="conjure-admin-card-copy"><?php echo esc_html( $connector['description'] ); ?></p>
														<?php endif; ?>
														<?php if ( ! $connector['readiness']['native_sync_ready'] && 'preferences' === $connector['integration_tier'] ) : ?>
															<p class="conjure-admin-card-warning">
																<?php esc_html_e( 'Not offered as a full-setup product yet — native sync is still in progress.', 'ConjureWP' ); ?>
															</p>
														<?php endif; ?>
													</div>
													<div class="conjure-admin-connector-actions">
														<?php $this->render_connector_activation_control( $connector ); ?>
													</div>
												</div>

												<?php if ( empty( $connector['features'] ) ) : ?>
													<div class="conjure-admin-empty-state">
														<p><?php esc_html_e( 'No configurable features are available for this connector.', 'ConjureWP' ); ?></p>
													</div>
												<?php else : ?>
													<div class="conjure-admin-setting-list">
														<?php foreach ( $connector['features'] as $feature_id => $feature ) : ?>
															<?php $tooltip_id = 'conjure-feature-tooltip-' . md5( $connector['id'] . '-' . $feature_id ); ?>
															<div class="conjure-admin-setting-row <?php echo ! empty( $feature['locked'] ) ? 'is-locked' : ''; ?>">
																<div class="conjure-admin-setting-copy">
																	<div class="conjure-admin-feature-heading">
																		<h4 class="conjure-admin-feature-title"><?php echo esc_html( $feature['label'] ); ?></h4>
																		<?php if ( ! empty( $feature['description'] ) ) : ?>
																			<span class="conjure-admin-feature-tooltip-wrap">
																				<button type="button" class="conjure-admin-feature-tooltip" aria-describedby="<?php echo esc_attr( $tooltip_id ); ?>" aria-label="<?php echo esc_attr( $feature['description'] ); ?>" title="<?php echo esc_attr( $feature['description'] ); ?>">i</button>
																				<span class="conjure-admin-feature-tooltip-panel" id="<?php echo esc_attr( $tooltip_id ); ?>" role="tooltip"><?php echo esc_html( $feature['description'] ); ?></span>
																			</span>
																		<?php endif; ?>
																	</div>
																	<?php if ( ! empty( $feature['locked'] ) ) : ?>
																		<p class="conjure-admin-feature-lock"><?php esc_html_e( 'This feature has been forced off by theme code or a filter override.', 'ConjureWP' ); ?></p>
																	<?php endif; ?>
																</div>
																<div class="conjure-admin-toggle-wrap">
																	<?php
																	$this->render_toggle_control(
																		'connector_features[' . $connector['id'] . '][' . $feature_id . ']',
																		! empty( $feature['saved_enabled'] ),
																		$feature['label'],
																		! empty( $feature['locked'] )
																	);
																	?>
																</div>
															</div>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</section>

						<section class="conjure-admin-section conjure-admin-tab-panel <?php echo $this->is_admin_tab_active( 'conjure-steps', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-panel" id="conjure-steps" role="tabpanel" aria-labelledby="conjure-tab-steps"<?php echo $this->is_admin_tab_active( 'conjure-steps', $initial_active_tab ) ? '' : ' hidden'; ?>>
							<div class="conjure-admin-panel">
								<div class="conjure-admin-panel-header">
									<div>
										<h2 class="conjure-admin-panel-title"><?php esc_html_e( 'Wizard Order', 'ConjureWP' ); ?></h2>
										<p class="conjure-admin-panel-copy"><?php esc_html_e( 'Drag and drop these steps to change the order used by the wizard. Order saves automatically when you move a step.', 'ConjureWP' ); ?></p>
										<p class="conjure-admin-panel-copy conjure-admin-panel-copy--autosave js-conjure-order-save-status" aria-live="polite"></p>
									</div>
									<button type="submit" name="conjurewp_preview_connectors" value="1" class="conjure-admin-button conjure-admin-button--secondary" formtarget="_blank">
										<?php esc_html_e( 'Preview Wizard', 'ConjureWP' ); ?>
									</button>
								</div>

								<ul class="conjure-step-sortable js-conjure-step-sortable" id="conjure-wizard-order-list">
									<?php echo $this->get_wizard_order_list_html( $steps ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</ul>
							</div>
						</section>

						<section class="conjure-admin-section conjure-admin-tab-panel <?php echo $this->is_admin_tab_active( 'conjure-import', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-panel" id="conjure-import" role="tabpanel" aria-labelledby="conjure-tab-import"<?php echo $this->is_admin_tab_active( 'conjure-import', $initial_active_tab ) ? '' : ' hidden'; ?>>
							<div class="conjure-admin-panel">
								<div class="conjure-admin-panel-header">
									<div>
										<h2 class="conjure-admin-panel-title"><?php esc_html_e( 'Import / Export', 'ConjureWP' ); ?></h2>
										<p class="conjure-admin-panel-copy"><?php esc_html_e( 'Export your connector settings and step order, or import them into another site.', 'ConjureWP' ); ?></p>
									</div>
								</div>

								<div class="conjure-admin-import-grid">
									<div class="conjure-admin-import-card">
										<h3 class="conjure-admin-import-title"><?php esc_html_e( 'Export Settings', 'ConjureWP' ); ?></h3>
										<p class="conjure-admin-import-copy"><?php esc_html_e( 'Download the current step order, completion state, and connector settings as a JSON file.', 'ConjureWP' ); ?></p>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=' . self::PAGE_SLUG . '&action=export' ), 'conjurewp_export_steps' ) ); ?>" class="conjure-admin-button conjure-admin-button--ghost">
											<?php esc_html_e( 'Export Steps JSON', 'ConjureWP' ); ?>
										</a>
									</div>

									<div class="conjure-admin-import-card">
										<h3 class="conjure-admin-import-title"><?php esc_html_e( 'Import Settings', 'ConjureWP' ); ?></h3>
										<p class="conjure-admin-import-copy"><?php esc_html_e( 'Restore a previously exported JSON file. This will replace the saved connector configuration on this site.', 'ConjureWP' ); ?></p>
										<div class="conjure-admin-import-form">
											<label class="conjure-admin-file-input">
												<span><?php esc_html_e( 'Choose JSON file', 'ConjureWP' ); ?></span>
												<input type="file" name="connector_import_file" accept=".json,application/json" form="conjure-admin-import-form">
											</label>
											<button type="submit" form="conjure-admin-import-form" class="conjure-admin-button conjure-admin-button--secondary">
												<?php esc_html_e( 'Import Steps JSON', 'ConjureWP' ); ?>
											</button>
										</div>
									</div>
								</div>
							</div>
						</section>

						<section class="conjure-admin-section conjure-admin-tab-panel <?php echo $this->is_admin_tab_active( 'conjure-save', $initial_active_tab ) ? 'is-active' : ''; ?> js-conjure-admin-panel" id="conjure-save" role="tabpanel" aria-labelledby="conjure-tab-save"<?php echo $this->is_admin_tab_active( 'conjure-save', $initial_active_tab ) ? '' : ' hidden'; ?>>
							<div class="conjure-admin-panel">
								<div class="conjure-admin-panel-header">
									<div>
										<h2 class="conjure-admin-panel-title"><?php esc_html_e( 'Settings', 'ConjureWP' ); ?></h2>
										<p class="conjure-admin-panel-copy"><?php esc_html_e( 'Save connector toggles, feature toggles, and the current wizard order in one go.', 'ConjureWP' ); ?></p>
									</div>
								</div>
								<div class="conjure-admin-settings-actions">
									<button type="submit" class="conjure-admin-button conjure-admin-button--primary">
										<?php esc_html_e( 'Save Connector Settings', 'ConjureWP' ); ?>
									</button>
								</div>
							</div>
						</section>
					</form>

					<form method="post" enctype="multipart/form-data" id="conjure-admin-import-form" class="hidden">
						<?php wp_nonce_field( 'conjurewp_import_steps' ); ?>
						<input type="hidden" name="conjurewp_import_steps" value="1">
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Build connector settings payload from the submitted admin form.
	 *
	 * @return array
	 */
	protected function get_submitted_connector_payload() {
		$enabled_connectors   = isset( $_POST['connector_enabled'] ) && is_array( $_POST['connector_enabled'] ) ? $_POST['connector_enabled'] : array();
		$submitted_features   = isset( $_POST['connector_features'] ) && is_array( $_POST['connector_features'] ) ? $_POST['connector_features'] : array();
		$submitted_step_order = isset( $_POST['step_order'] ) && is_array( $_POST['step_order'] ) ? $_POST['step_order'] : array();
		$submitted_settings   = array();

		foreach ( $this->connector_manager->get_connectors() as $connector_id => $connector ) {
			$defaults          = $connector->get_default_settings();
			$existing_settings = $this->connector_manager->get_connector_settings( $connector_id );
			$features          = array();

			foreach ( $defaults['features'] as $feature_id => $default_enabled ) {
				if ( isset( $submitted_features[ $connector_id ][ $feature_id ] ) ) {
					$features[ $feature_id ] = true;
				} elseif ( isset( $submitted_features[ $connector_id ] ) ) {
					$features[ $feature_id ] = false;
				} elseif ( isset( $existing_settings['features'][ $feature_id ] ) ) {
					$features[ $feature_id ] = (bool) $existing_settings['features'][ $feature_id ];
				} else {
					$features[ $feature_id ] = (bool) $default_enabled;
				}
			}

			$submitted_settings[ $connector_id ] = array(
				'enabled'  => isset( $enabled_connectors[ $connector_id ] ),
				'features' => $features,
			);
		}

		return array(
			'settings'   => $submitted_settings,
			'step_order' => $submitted_step_order,
		);
	}

	/**
	 * Open a read-only preview of the submitted connector state.
	 *
	 * @return void
	 */
	protected function preview_connector_settings() {
		$payload = $this->get_submitted_connector_payload();

		wp_safe_redirect(
			$this->connector_manager->get_preview_url(
				$payload['settings'],
				$payload['step_order']
			)
		);
		exit;
	}

	/**
	 * Save connector settings from the admin form.
	 *
	 * @return void
	 */
	protected function save_connector_settings() {
		$payload = $this->get_submitted_connector_payload();

		$this->connector_manager->save_settings( $payload['settings'] );
		$this->connector_manager->save_step_order( $payload['step_order'] );
		$this->set_notice( __( 'Connector settings saved.', 'ConjureWP' ) );

		$active_tab = isset( $_POST['conjurewp_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['conjurewp_active_tab'] ) ) : 'conjure-connectors';

		if ( ! in_array( $active_tab, $this->get_allowed_tab_ids(), true ) ) {
			$active_tab = 'conjure-connectors';
		}

		$this->redirect_to_page( $active_tab );
	}

	/**
	 * Export JSON payload for step settings.
	 *
	 * @return void
	 */
	protected function export_json() {
		$this->conjure->steps();
		$step_completion = get_option( 'conjure_' . $this->conjure->slug . '_step_completion', array() );
		$wizard_completed = get_option( 'conjure_' . $this->conjure->slug . '_completed', false );
		$steps = array();

		foreach ( $this->conjure->steps as $step_key => $step ) {
			$steps[] = array(
				'key'          => $step_key,
				'name'         => isset( $step['name'] ) ? $step['name'] : $step_key,
				'source'       => $this->connector_manager->get_step_source( $step_key ),
				'connector_id' => $this->connector_manager->get_step_connector_id( $step_key ),
			);
		}

		$connector_settings   = $this->connector_manager->get_export_data();
		$connector_activation = array();

		foreach ( $connector_settings as $connector_id => $connector_setting ) {
			$connector_activation[ $connector_id ] = ! empty( $connector_setting['enabled'] );
		}

		$payload = array(
			'version'              => defined( 'CONJURE_VERSION' ) ? CONJURE_VERSION : '1.0.0',
			'theme_slug'           => method_exists( $this->conjure->theme, 'get_stylesheet' ) ? $this->conjure->theme->get_stylesheet() : '',
			'runtime_mode'         => function_exists( 'conjurewp_get_runtime_mode' ) ? conjurewp_get_runtime_mode() : 'plugin',
			'steps'                => $steps,
			'step_order'           => $this->connector_manager->get_current_step_order( $this->conjure->steps ),
			'step_completion'      => $step_completion,
			'wizard_completed'     => $wizard_completed,
			'connector_activation' => $connector_activation,
			'connector_settings'   => $connector_settings,
		);

		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload, JSON_PRETTY_PRINT ) : json_encode( $payload, JSON_PRETTY_PRINT );

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="conjurewp-steps-' . gmdate( 'Ymd-His' ) . '.json"' );
		header( 'Content-Length: ' . (string) strlen( $json ) );

		echo $json;
		exit;
	}

	/**
	 * Import a JSON payload.
	 *
	 * @return void
	 */
	protected function import_json() {
		if ( empty( $_FILES['connector_import_file']['tmp_name'] ) ) {
			$this->set_notice( __( 'Please choose a JSON file to import.', 'ConjureWP' ), 'error' );
			$this->redirect_to_page();
		}

		$file_path     = sanitize_text_field( wp_unslash( $_FILES['connector_import_file']['tmp_name'] ) );
		$file_contents = file_get_contents( $file_path );

		if ( false === $file_contents ) {
			$this->set_notice( __( 'The selected JSON file could not be read.', 'ConjureWP' ), 'error' );
			$this->redirect_to_page();
		}

		$payload = function_exists( 'conjurewp_json_decode' )
			? conjurewp_json_decode( $file_contents, true )
			: json_decode( $file_contents, true );

		if ( ! is_array( $payload ) ) {
			$this->set_notice( __( 'The imported file is not valid JSON.', 'ConjureWP' ), 'error' );
			$this->redirect_to_page();
		}

		if ( isset( $payload['step_completion'] ) && is_array( $payload['step_completion'] ) ) {
			$step_completion = array();
			foreach ( $payload['step_completion'] as $step_key => $timestamp ) {
				$step_key = sanitize_key( $step_key );
				if ( empty( $step_key ) ) {
					continue;
				}

				$step_completion[ $step_key ] = absint( $timestamp );
			}

			update_option( 'conjure_' . $this->conjure->slug . '_step_completion', $step_completion );
		}

		if ( isset( $payload['wizard_completed'] ) ) {
			$wizard_completed = $payload['wizard_completed'];

			if ( 'ignored' === $wizard_completed ) {
				update_option( 'conjure_' . $this->conjure->slug . '_completed', 'ignored' );
			} elseif ( empty( $wizard_completed ) ) {
				delete_option( 'conjure_' . $this->conjure->slug . '_completed' );
			} else {
				update_option( 'conjure_' . $this->conjure->slug . '_completed', absint( $wizard_completed ) );
			}
		}

		$this->connector_manager->import_settings( $payload );

		$this->set_notice( __( 'Step settings imported.', 'ConjureWP' ) );
		$this->redirect_to_page();
	}

	/**
	 * Store a notice for the next page load.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type.
	 * @return void
	 */
	protected function set_notice( $message, $type = 'success' ) {
		set_transient(
			'conjurewp_steps_admin_notice',
			array(
				'message' => $message,
				'type'    => $type,
			),
			30
		);
	}

	/**
	 * Redirect back to the admin page.
	 *
	 * @return void
	 */
	protected function redirect_to_page( $tab = '' ) {
		$url = admin_url( 'tools.php?page=' . self::PAGE_SLUG );

		if ( ! empty( $tab ) && in_array( $tab, $this->get_allowed_tab_ids(), true ) ) {
			$url = add_query_arg( 'conjurewp_tab', $tab, $url );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Determine whether the current request targets the steps admin screen.
	 *
	 * @return bool
	 */
	protected function is_steps_admin_request() {
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';

		if ( self::PAGE_SLUG === $page ) {
			return true;
		}

		return ! empty( $_POST['conjurewp_steps_page'] )
			&& self::PAGE_SLUG === sanitize_text_field( wp_unslash( $_POST['conjurewp_steps_page'] ) );
	}

	/**
	 * Allowed admin tab panel IDs.
	 *
	 * @return string[]
	 */
	protected function get_allowed_tab_ids() {
		return array(
			'conjure-overview',
			'conjure-connectors',
			'conjure-steps',
			'conjure-import',
			'conjure-save',
		);
	}

	/**
	 * Resolve the tab that should be active on the current request.
	 *
	 * @return string
	 */
	protected function get_initial_active_tab() {
		$tab = isset( $_GET['conjurewp_tab'] ) ? sanitize_key( wp_unslash( $_GET['conjurewp_tab'] ) ) : '';

		if ( in_array( $tab, $this->get_allowed_tab_ids(), true ) ) {
			return $tab;
		}

		return 'conjure-overview';
	}

	/**
	 * Check whether a tab should render as active.
	 *
	 * @param string $tab_id      Tab panel ID.
	 * @param string $active_tab  Active tab ID.
	 * @return bool
	 */
	protected function is_admin_tab_active( $tab_id, $active_tab ) {
		return $tab_id === $active_tab;
	}

	/**
	 * Render a toggle control.
	 *
	 * @param string $name     Field name.
	 * @param bool   $checked  Checked state.
	 * @param string $label    Accessible label.
	 * @param bool   $disabled Disabled state.
	 * @return void
	 */
	protected function render_toggle_control( $name, $checked, $label, $disabled = false ) {
		?>
		<label class="conjure-admin-toggle">
			<input
				type="checkbox"
				class="conjure-admin-toggle__input"
				name="<?php echo esc_attr( $name ); ?>"
				value="1"
				<?php checked( $checked ); ?>
				<?php disabled( $disabled ); ?>
			>
			<span class="conjure-admin-toggle__track">
				<span class="conjure-admin-toggle__knob"></span>
			</span>
			<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render connector activation UI and wizard state copy.
	 *
	 * @param array $connector Connector admin payload.
	 * @return void
	 */
	protected function render_connector_activation_control( $connector ) {
		$enabled = ! empty( $connector['settings']['enabled'] );

		$this->render_activation_button(
			'connector_enabled[' . $connector['id'] . ']',
			$enabled,
			__( 'Activate', 'ConjureWP' ),
			__( 'Deactivate', 'ConjureWP' )
		);
	}

	/**
	 * Render an activation button backed by a checkbox field.
	 *
	 * @param string $name             Field name.
	 * @param bool   $checked          Checked state.
	 * @param string $activate_label   Activate label.
	 * @param string $deactivate_label Deactivate label.
	 * @param bool   $disabled         Disabled state.
	 * @return void
	 */
	protected function render_activation_button( $name, $checked, $activate_label, $deactivate_label, $disabled = false ) {
		$field_id = 'conjure-activation-' . md5( $name );
		?>
		<div class="conjure-admin-activation-control">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $field_id ); ?>"
				class="conjure-admin-activation-checkbox"
				name="<?php echo esc_attr( $name ); ?>"
				value="1"
				<?php checked( $checked ); ?>
				<?php disabled( $disabled ); ?>
			>
			<button
				type="button"
				class="conjure-admin-button conjure-admin-activation-button js-conjure-activation-button <?php echo esc_attr( $checked ? 'is-active' : 'is-inactive' ); ?>"
				data-target="<?php echo esc_attr( $field_id ); ?>"
				data-activate-label="<?php echo esc_attr( $activate_label ); ?>"
				data-deactivate-label="<?php echo esc_attr( $deactivate_label ); ?>"
				aria-pressed="<?php echo esc_attr( $checked ? 'true' : 'false' ); ?>"
				<?php disabled( $disabled ); ?>
			>
				<?php echo esc_html( $checked ? $deactivate_label : $activate_label ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Get plugin status badge class.
	 *
	 * @param array $plugin_status Plugin status data.
	 * @return string
	 */
	protected function get_plugin_badge_class( $plugin_status ) {
		if ( ! empty( $plugin_status['active'] ) ) {
			return 'conjure-admin-badge--success';
		}

		if ( ! empty( $plugin_status['installed'] ) ) {
			return 'conjure-admin-badge--warning';
		}

		return 'conjure-admin-badge--danger';
	}

	/**
	 * Get readiness badge class.
	 *
	 * @param array $readiness Readiness payload.
	 * @return string
	 */
	protected function get_readiness_badge_class( $readiness ) {
		if ( empty( $readiness['code'] ) ) {
			return 'conjure-admin-badge--muted';
		}

		switch ( $readiness['code'] ) {
			case 'ready':
				return 'conjure-admin-badge--success';
			case 'active':
				return 'conjure-admin-badge--info';
			case 'pending_pro':
			case 'license_required':
				return 'conjure-admin-badge--warning';
			case 'plugin_inactive':
			case 'plugin_missing':
				return 'conjure-admin-badge--danger';
			default:
				return 'conjure-admin-badge--muted';
		}
	}

	/**
	 * Get source badge class.
	 *
	 * @param string $source Step source.
	 * @return string
	 */
	protected function get_source_badge_class( $source ) {
		if ( 'core' === $source ) {
			return 'conjure-admin-badge--muted';
		}

		if ( 'steps_dir' === $source ) {
			return 'conjure-admin-badge--info';
		}

		return 'conjure-admin-badge--warning';
	}

	/**
	 * Get wizard order steps for the admin screen.
	 *
	 * @return array
	 */
	protected function get_wizard_order_steps() {
		$this->conjure->steps();

		return $this->connector_manager->build_admin_wizard_order_steps( $this->conjure->steps );
	}

	/**
	 * Count wizard order steps for overview stats.
	 *
	 * @return int
	 */
	protected function get_wizard_order_step_count() {
		return count( $this->get_wizard_order_steps() );
	}

	/**
	 * Render wizard order list items.
	 *
	 * @param array|null $steps Optional pre-built steps array.
	 * @return string
	 */
	protected function get_wizard_order_list_html( $steps = null ) {
		if ( null === $steps ) {
			$steps = $this->get_wizard_order_steps();
		}

		ob_start();

		foreach ( $steps as $step_key => $step ) {
			echo $this->render_wizard_order_list_item( $step_key, $step ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return (string) ob_get_clean();
	}

	/**
	 * Render a single wizard order list item.
	 *
	 * @param string $step_key Step key.
	 * @param array  $step     Step definition.
	 * @return string
	 */
	protected function render_wizard_order_list_item( $step_key, $step ) {
		$completed            = $this->conjure->get_step_completion_state( $step_key );
		$step_source          = $this->connector_manager->get_step_source( $step_key );
		$connector_is_active  = $this->connector_manager->is_connector_step_active( $step_key );
		$connector_is_planned = $this->connector_manager->is_connector_step_planned( $step_key );
		$status_badge_class   = 'conjure-admin-badge--muted';
		$status_label         = __( 'Pending', 'ConjureWP' );
		$item_classes         = array( 'conjure-step-sortable__item', 'js-conjure-step-item' );

		if ( $connector_is_active ) {
			$status_badge_class = 'conjure-admin-badge--success';
			$status_label       = __( 'Active', 'ConjureWP' );
			$item_classes[]     = 'is-connector-active';
		} elseif ( $connector_is_planned ) {
			$status_badge_class = 'conjure-admin-badge--warning';
			$status_label       = __( 'Planned', 'ConjureWP' );
			$item_classes[]     = 'is-connector-planned';
		} elseif ( $completed ) {
			$status_badge_class = 'conjure-admin-badge--success';
			$status_label       = __( 'Completed', 'ConjureWP' );
		}

		ob_start();
		?>
		<li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>">
			<input type="hidden" name="step_order[]" value="<?php echo esc_attr( $step_key ); ?>">
			<div class="conjure-step-sortable__handle js-conjure-step-handle" aria-hidden="true">
				<span></span><span></span><span></span>
			</div>
			<div class="conjure-step-sortable__content">
				<div class="conjure-step-sortable__content-top">
					<div>
						<p class="conjure-step-sortable__label"><?php echo esc_html( isset( $step['name'] ) ? $step['name'] : $step_key ); ?></p>
						<p class="conjure-step-sortable__key"><code><?php echo esc_html( $step_key ); ?></code></p>
					</div>
					<div class="conjure-step-sortable__meta">
						<span class="conjure-admin-badge <?php echo esc_attr( $this->get_source_badge_class( $step_source ) ); ?>">
							<?php echo esc_html( $this->connector_manager->get_step_source_label( $step_key ) ); ?>
						</span>
						<span class="conjure-admin-badge <?php echo esc_attr( $status_badge_class ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</div>
				</div>
			</div>
		</li>
		<?php

		return (string) ob_get_clean();
	}
}
