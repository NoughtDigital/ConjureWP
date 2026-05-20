<?php
/**
 * Wizard page flow and step views.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the setup wizard admin page and step rendering.
 */
class Conjure_Wizard_Controller {

	/**
	 * @var Conjure
	 */
	private $conjure;

	public function __construct( $conjure ) {
		$this->conjure = $conjure;
	}

	public function admin_page() {
		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Do not proceed if we're not on the right page.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( empty( $page ) || $this->conjure->conjure_url !== $page ) {
			return;
		}

		// Ensure steps are initialized first (needed for proper step handling).
		if ( empty( $this->conjure->steps ) ) {
			$this->steps();
		}

		// Check access: free for open-source themes, paid for commercial themes.
		// Only check if Freemius class exists, otherwise allow access (graceful degradation).
		if ( class_exists( 'Conjure_Freemius' ) ) {
			$freemius_access = Conjure_Freemius::has_free_access();

			if ( ! $freemius_access ) {
				if ( $this->conjure->license_step_enabled && isset( $this->conjure->steps['license'] ) ) {
					if ( $this->conjure->license_required ) {
						$this->conjure->license_gate_active = true;
					}
				} else {
					$this->show_upgrade_notice();
					return;
				}
			}
		}

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		// Get the current step, with fallback to first step if empty or invalid.
		$step_keys = array_keys( $this->conjure->steps );
		$default_step = ! empty( $step_keys ) ? $step_keys[0] : '';
		$this->conjure->step = isset( $_GET['step'] ) && ! empty( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : $default_step;

		// Validate step exists in steps array.
		if ( empty( $this->conjure->step ) || ! isset( $this->conjure->steps[ $this->conjure->step ] ) ) {
			$this->conjure->logger->warning( sprintf( 'Invalid step "%s" requested, falling back to default: %s', $this->conjure->step, $default_step ) );
			$this->conjure->step = $default_step;
		}

		// Force license step if gate is active.
		if ( $this->conjure->license_gate_active && 'license' !== $this->conjure->step ) {
			$this->conjure->step   = 'license';
			$_GET['step'] = 'license';
		}

		// Prevent deprecated function calls by removing hooks that use them.
		// This prevents warnings from print_emoji_styles and wp_admin_bar_header.
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_head', 'wp_admin_bar_header' );

		// Always use minified files built with Vite.
		$suffix = '.min';

		// Enqueue WordPress media uploader.
		wp_enqueue_media();

		// Enqueue emoji styles using the modern API (replaces deprecated print_emoji_styles).
		if ( function_exists( 'wp_enqueue_emoji_styles' ) ) {
			wp_enqueue_emoji_styles();
		}

		// Enqueue admin bar header styles using the modern API (replaces deprecated wp_admin_bar_header).
		if ( function_exists( 'wp_enqueue_admin_bar_header_styles' ) ) {
			wp_enqueue_admin_bar_header_styles();
		}

		// Enqueue styles.
		$css_file = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/css/conjure' . $suffix . '.css';
		$version = file_exists( $css_file ) ? filemtime( $css_file ) : CONJURE_VERSION;
		wp_enqueue_style( 'conjure', trailingslashit( $this->conjure->base_url ) . $this->conjure->directory . '/assets/css/conjure' . $suffix . '.css', array( 'wp-admin' ), $version );

		// Enqueue javascript.
		$js_file = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/js/conjure' . $suffix . '.js';
		$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : CONJURE_VERSION;
		wp_enqueue_script( 'conjure', trailingslashit( $this->conjure->base_url ) . $this->conjure->directory . '/assets/js/conjure' . $suffix . '.js', array( 'jquery-core', 'jquery-ui-core' ), $js_version, true );

		$texts = array(
			'something_went_wrong' => esc_html__( 'Something went wrong. Please refresh the page and try again!', 'ConjureWP' ),
		);

		// Localise the javascript.
		wp_localize_script(
			'conjure',
			'conjure_params',
			array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'wpnonce'              => wp_create_nonce( 'conjure_nonce' ),
				'texts'                => $texts,
				'use_custom_installer' => true,
			)
		);

		/**
		 * Start the actual page content.
		 * Note: We don't use output buffering here as the header and body output directly.
		 */
		$this->conjure->header(); ?>

		<div class="conjure__wrapper">

			<div class="conjure__content conjure__content--<?php echo esc_attr( ! empty( $this->conjure->step ) && isset( $this->conjure->steps[ $this->conjure->step ]['name'] ) ? strtolower( $this->conjure->steps[ $this->conjure->step ]['name'] ) : '' ); ?>">

			<?php
			// Content Handlers.
			$show_content = true;

			if ( ! empty( $_REQUEST['save_step'] ) && isset( $this->conjure->steps[ $this->conjure->step ]['handler'] ) ) {
				check_admin_referer( 'conjure' );
				$show_content = call_user_func( $this->conjure->steps[ $this->conjure->step ]['handler'] );
			}

			if ( $show_content ) {
				$this->conjure->body();
			}
			?>

			<?php $this->conjure->step_output(); ?>

			</div>

		<?php echo sprintf( '<a class="return-to-dashboard" href="%s" title="%s">%s</a>', esc_url( admin_url( 'tools.php?page=ConjureWP-steps' ) ), esc_attr( $strings['return-to-dashboard'] ), esc_html( $strings['return-to-dashboard'] ) ); ?>

		<?php $ignore_url = wp_nonce_url( admin_url( '?' . $this->conjure->ignore . '=true' ), 'ConjureWP-ignore-nonce' ); ?>

			<?php echo sprintf( '<a class="return-to-dashboard ignore" href="%s">%s</a>', esc_url( $ignore_url ), esc_html( $strings['ignore'] ) ); ?>

		</div>

		<?php $this->conjure->footer(); ?>

		<?php
		exit;
	}
	public function show_upgrade_notice() {
		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Get theme information.
		$theme_name = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::get_current_theme_name() : $this->conjure->theme->name;

		// Always use minified files built with Vite.
		$suffix = '.min';

		// Enqueue styles.
		$css_file = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/css/conjure' . $suffix . '.css';
		$version = file_exists( $css_file ) ? filemtime( $css_file ) : CONJURE_VERSION;
		wp_enqueue_style( 'conjure', trailingslashit( $this->conjure->base_url ) . $this->conjure->directory . '/assets/css/conjure' . $suffix . '.css', array( 'wp-admin' ), $version );

		ob_start();
		$this->conjure->header();
		?>

		<div class="conjure__wrapper">
			<div class="conjure__content conjure__content--upgrade">
				<div class="conjure__content--transition">
					<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'license' ) ), $this->conjure->svg_allowed_html() ); ?>
					
					<h1><?php esc_html_e( 'ConjureWP License Required', 'ConjureWP' ); ?></h1>
					
					<p>
						<?php
						printf(
							/* translators: %s: Theme name */
							esc_html__( 'ConjureWP is free for open-source themes, but requires a license for commercial/premium themes like %s.', 'ConjureWP' ),
							esc_html( $theme_name )
						);
						?>
					</p>
					
					<p>
						<?php esc_html_e( 'To use ConjureWP with your premium theme, please purchase a license or switch to an open-source theme.', 'ConjureWP' ); ?>
					</p>

				<?php if ( function_exists( 'con_fs' ) ) : ?>
					<?php $fs = con_fs(); ?>
						<?php if ( $fs ) : ?>
							<div class="conjure__upgrade-actions" style="margin-top: 30px;">
								<?php if ( ! $fs->is_registered() ) : ?>
									<a href="<?php echo esc_url( $fs->get_activation_url() ); ?>" class="conjure__button conjure__button--next">
										<?php esc_html_e( 'Get Started', 'ConjureWP' ); ?>
									</a>
								<?php elseif ( ! $fs->has_active_valid_license() ) : ?>
									<a href="<?php echo esc_url( $fs->get_upgrade_url() ); ?>" class="conjure__button conjure__button--next">
										<?php esc_html_e( 'Upgrade Now', 'ConjureWP' ); ?>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<p style="margin-top: 20px; color: #d63638;">
							<strong><?php esc_html_e( 'Note:', 'ConjureWP' ); ?></strong>
							<?php esc_html_e( 'Freemius SDK is not properly configured. Please contact the plugin developer.', 'ConjureWP' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<?php echo sprintf( '<a class="return-to-dashboard" href="%s" title="%s">%s</a>', esc_url( admin_url( 'tools.php?page=ConjureWP-steps' ) ), esc_attr( $strings['return-to-dashboard'] ), esc_html( $strings['return-to-dashboard'] ) ); ?>
		</div>

		<?php
		$this->conjure->footer();
		exit;
	}

	/**
	 * Register wizard steps on the main Conjure instance.
	 *
	 * @return void
	 */
	public function steps() {

		$this->conjure->steps = array(
			'welcome' => array(
				'name'    => esc_html__( 'Welcome', 'ConjureWP' ),
				'view'    => array( $this, 'welcome' ),
				'handler' => array( $this, 'welcome_handler' ),
			),
		);

		$this->conjure->steps['child'] = array(
			'name' => esc_html__( 'Child', 'ConjureWP' ),
			'view' => array( $this, 'child' ),
		);

		// Only add license step if enabled AND theme doesn't have lifetime integration.
		$has_lifetime_integration = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::has_lifetime_integration() : false;

		if ( $this->conjure->license_step_enabled && ! $has_lifetime_integration ) {
			$this->conjure->steps['license'] = array(
				'name' => esc_html__( 'License', 'ConjureWP' ),
				'view' => array( $this->conjure, 'license' ),
			);
		}

		// Automatic plugin installation step (premium feature).
		$can_auto_install = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::can_auto_install_plugins() : false;

		if ( $can_auto_install ) {
			$this->conjure->steps['plugins'] = array(
				'name' => esc_html__( 'Plugins', 'ConjureWP' ),
				'view' => array( $this, 'plugins' ),
			);
		}

		// Content importer step - either with pre-configured files or manual upload.
		$this->conjure->steps['content'] = array(
			'name' => esc_html__( 'Content', 'ConjureWP' ),
			'view' => array( $this, 'content' ),
		);

		$this->conjure->steps['ready'] = array(
			'name' => esc_html__( 'Ready', 'ConjureWP' ),
			'view' => array( $this, 'ready' ),
		);

		// Allow theme-specific customisation (backward compatibility).
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Legacy theme-specific dynamic hook for backward compatibility.
		$this->conjure->steps = apply_filters( $this->conjure->theme->template . '_conjure_steps', $this->conjure->steps );

		// Allow generic customisation for all themes (recommended for theme developers).
		$this->conjure->steps = apply_filters( 'conjure_steps', $this->conjure->steps );
	}

	public function welcome() {

		// Has this theme been setup yet? Compare this to the option set when you get to the last panel.
		$already_setup = get_option( 'conjure_' . $this->conjure->slug . '_completed' );

		// Theme Name.
		$theme = str_replace( ' Child', '', ucfirst( $this->conjure->theme->name ) );

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Text strings.
		$header    = ! $already_setup ? $strings['welcome-header%s'] : $strings['welcome-header-success%s'];
		$paragraph = ! $already_setup ? $strings['welcome%s'] : $strings['welcome-success%s'];
		$start     = $strings['btn-start'];
		$no        = $strings['btn-no'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'welcome' ) ), $this->conjure->svg_allowed_html() ); ?>

			<h1><?php echo esc_html( sprintf( $header, $theme ) ); ?></h1>

			<p><?php echo esc_html( sprintf( $paragraph, $theme ) ); ?></p>

		</div>

		<footer class="conjure__content__footer">
			<a href="<?php echo esc_url( wp_get_referer() && ! strpos( wp_get_referer(), 'update.php' ) ? wp_get_referer() : admin_url( '/' ) ); ?>" class="conjure__button conjure__button--skip"><?php echo esc_html( $no ); ?></a>
			<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $start ); ?></a>
			<?php wp_nonce_field( 'conjure' ); ?>
		</footer>

		<?php
		$this->conjure->logger->debug( __( 'The welcome step has been displayed', 'ConjureWP' ) );
	}

	/**
	 * Handles save button from welcome page.
	 * This is to perform tasks when the setup wizard has already been run.
	 */
	public function welcome_handler() {

		check_admin_referer( 'conjure' );

		return false;
	}

	/**
	 * Child theme generator.
	 */
	public function child() {

		// Variables.
		$child_theme_option    = get_option( 'conjure_' . $this->conjure->slug . '_child' );
		$conjure_created_child = ! empty( $child_theme_option );
		$theme                 = $child_theme_option ? wp_get_theme( $child_theme_option )->name : $this->conjure->theme->name . ' Child';
		$action_url            = $this->conjure->child_action_btn_url;

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Text strings.
		$header    = ! $conjure_created_child ? $strings['child-header'] : $strings['child-header-success'];
		$action    = $strings['child-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$paragraph = ! $conjure_created_child ? $strings['child'] : $strings['child-success%s'];
		$install   = $strings['btn-child-install'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'child' ) ), $this->conjure->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( $header ); ?></h1>

			<p id="child-theme-text"><?php echo esc_html( sprintf( $paragraph, $theme ) ); ?></p>

			<a class="conjure__button conjure__button--knockout conjure__button--no-chevron conjure__button--external" href="<?php echo esc_url( $action_url ); ?>" target="_blank"><?php echo esc_html( $action ); ?></a>

		</div>

		<footer class="conjure__content__footer">

			<?php if ( ! $conjure_created_child ) : ?>

				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>

				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next" data-callback="install_child">
					<span class="conjure__button--loading__text"><?php echo esc_html( $install ); ?></span>
					<?php echo wp_kses( $this->conjure->loading_spinner(), $this->conjure->loading_spinner_allowed_html() ); ?>
				</a>

			<?php else : ?>
				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $next ); ?></a>
			<?php endif; ?>
			<?php wp_nonce_field( 'conjure' ); ?>
		</footer>

		<?php
	}

	/**
	 * Theme plugins
	 */
	public function plugins() {

		// Variables.
		$url    = wp_nonce_url( add_query_arg( array( 'plugins' => 'go' ) ), 'conjure' );
		$method = '';

		// Sanitise POST keys for filesystem credentials.
		$fields = array();
		if ( ! empty( $_POST ) ) {
			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'conjure' ) ) {
				return true;
			}

			foreach ( $_POST as $key => $unused_value ) {
				$fields[] = sanitize_text_field( wp_unslash( $key ) );
			}
		}
		$creds = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, $fields );

		if ( false === $creds ) {
			return true;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields );
			return true;
		}

		// Check if we have a selected demo for demo-specific plugins.
		$selected_demo_index = get_transient( 'conjure_selected_demo_index' );

		// Validate the selected demo index exists.
		if ( false !== $selected_demo_index && ! isset( $this->conjure->import_files[ $selected_demo_index ] ) ) {
			delete_transient( 'conjure_selected_demo_index' );
			$selected_demo_index = false;
		}

		// If no demo selected yet and we have demos, auto-select the first one.
		if ( false === $selected_demo_index && ! empty( $this->conjure->import_files ) ) {
			$selected_demo_index = 0;
		}

		// Are there plugins that need installing/activating?
		$plugins             = $this->get_plugins( $selected_demo_index );
		$required_plugins    = array();
		$recommended_plugins = array();
		$count               = count( $plugins['all'] );
		$class               = $count ? null : 'no-plugins';

		// Split the plugins into required and recommended.
		foreach ( $plugins['all'] as $slug => $plugin ) {
			if ( ! empty( $plugin['required'] ) ) {
				$required_plugins[ $slug ] = $plugin;
			} else {
				$recommended_plugins[ $slug ] = $plugin;
			}
		}

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Text strings.
		$header    = $count ? $strings['plugins-header'] : $strings['plugins-header-success'];
		$paragraph = $count ? $strings['plugins'] : $strings['plugins-success%s'];
		$action    = $strings['plugins-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$install   = $strings['btn-plugins-install'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'plugins' ) ), $this->conjure->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

		<h1><?php echo esc_html( $header ); ?></h1>

		<p><?php echo esc_html( $paragraph ); ?></p>

		<?php
		// Show demo selector if demos are available and demo-specific plugins are enabled.
		$show_demo_selector = false;
		if ( $this->conjure->import_files && count( $this->conjure->import_files ) > 0 && $this->conjure->demo_plugin_manager ) {
			$show_demo_selector = $this->conjure->demo_plugin_manager->is_demo_specific_plugins_enabled();
		}

		if ( $show_demo_selector && count( $this->conjure->import_files ) > 1 ) :
			?>
			<div class="conjure__demo-selector">
				<h3 style="margin-bottom: 0.5em; font-weight: 600; font-size: 1.1em;">
					<?php echo esc_html__( 'Select your demo:', 'ConjureWP' ); ?>
				</h3>
				<p class="conjure__demo-grid-description">
					<?php echo esc_html__( 'Each demo has different plugin requirements. Choose your demo to see required and recommended plugins.', 'ConjureWP' ); ?>
				</p>
				<div class="conjure__demo-grid">
					<?php foreach ( $this->conjure->import_files as $index => $import_file ) : ?>
						<div class="conjure__demo-card js-conjure-demo-card-plugins <?php echo ( $selected_demo_index !== false && $selected_demo_index === $index ) ? 'is-selected' : ''; ?>" data-demo-index="<?php echo esc_attr( $index ); ?>">
							<div class="conjure__demo-card-image">
								<?php if ( ! empty( $import_file['import_preview_image_url'] ) ) : ?>
									<img src="<?php echo esc_url( $import_file['import_preview_image_url'] ); ?>" alt="<?php echo esc_attr( $import_file['import_file_name'] ); ?>" loading="lazy">
								<?php else : ?>
									<div class="conjure__demo-card-placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="conjure__demo-card-content">
								<h4 class="conjure__demo-card-title"><?php echo esc_html( $import_file['import_file_name'] ); ?></h4>
								<?php if ( ! empty( $import_file['import_notice'] ) ) : ?>
									<p class="conjure__demo-card-description"><?php echo esc_html( $import_file['import_notice'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $count ) { ?>
			<a id="conjure__drawer-trigger" class="conjure__button conjure__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>
		<?php } ?>

	</div>

	<form action="" method="post">

			<?php if ( $count ) : ?>

				<ul class="conjure__drawer conjure__drawer--install-plugins">

				<?php if ( ! empty( $required_plugins ) ) : ?>
		<li class="plugin-section-header plugin-section-header--required">
					<?php echo esc_html__( 'Required Plugins', 'ConjureWP' ); ?>
			<span class="plugin-section-header__subtitle">
					<?php echo esc_html__( 'These plugins are essential for the demo to work correctly', 'ConjureWP' ); ?>
			</span>
		</li>
					<?php
					foreach ( $required_plugins as $slug => $plugin ) :
						$is_active = ! empty( $plugin['is_active'] );
						?>
			<li data-slug="<?php echo esc_attr( $slug ); ?>" class="conjure-plugin-row conjure-plugin-row--required<?php echo $is_active ? ' plugin-active' : ''; ?>">
				<!-- Hidden input ensures it's always submitted (no checkbox for required) -->
				<input type="hidden" name="default_plugins[<?php echo esc_attr( $slug ); ?>]" value="1">

				<span class="conjure-plugin-row__indicator" aria-hidden="true">
						<?php echo $is_active ? '✓' : '▸'; ?>
				</span>

				<span class="conjure-plugin-row__title"><?php echo esc_html( $plugin['name'] ); ?></span>

						<?php if ( $is_active ) : ?>
					<span class="badge badge--success">
							<?php esc_html_e( 'Installed', 'ConjureWP' ); ?>
					</span>
				<?php endif; ?>
			</li>
					<?php endforeach; ?>
		<?php endif; ?>

				<?php if ( ! empty( $recommended_plugins ) ) : ?>
		<li class="plugin-section-header plugin-section-header--recommended">
					<?php echo esc_html__( 'Recommended Plugins', 'ConjureWP' ); ?>
			<span class="plugin-section-header__subtitle">
					<?php echo esc_html__( 'Optional plugins that enhance the demo (can be unchecked)', 'ConjureWP' ); ?>
			</span>
		</li>
					<?php
					foreach ( $recommended_plugins as $slug => $plugin ) :
						$is_active = ! empty( $plugin['is_active'] );
						?>
		<li data-slug="<?php echo esc_attr( $slug ); ?>" class="conjure-plugin-row<?php echo $is_active ? ' plugin-active conjure-plugin-row--installed' : ''; ?>">
						<?php if ( $is_active ) : ?>
				<input type="hidden" name="default_plugins[<?php echo esc_attr( $slug ); ?>]" value="1">
			<?php endif; ?>
			<input type="checkbox" name="default_plugins[<?php echo esc_attr( $slug ); ?>]" class="checkbox" id="default_plugins_<?php echo esc_attr( $slug ); ?>" value="1" <?php echo $is_active ? 'checked disabled="disabled"' : 'checked'; ?>>

			<label for="default_plugins_<?php echo esc_attr( $slug ); ?>" class="conjure-plugin-option<?php echo $is_active ? ' conjure-plugin-option--installed' : ''; ?>" <?php echo $is_active ? 'aria-disabled="true"' : ''; ?>>
				<i></i>
				<span class="conjure-plugin-row__title"><?php echo esc_html( $plugin['name'] ); ?></span>
				
						<?php if ( $is_active ) : ?>
					<span class="badge badge--success">
							<?php esc_html_e( 'Installed', 'ConjureWP' ); ?>
					</span>
				<?php endif; ?>
			</label>
		</li>
							<?php endforeach; ?>
				<?php endif; ?>

				</ul>

			<?php endif; ?>

			<footer class="conjure__content__footer <?php echo esc_attr( $class ); ?>">
				<?php if ( $count ) : ?>
					<a id="close" href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--closer conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>
					<a id="skip" href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>
					<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next" data-callback="install_plugins">
						<span class="conjure__button--loading__text"><?php echo esc_html( $install ); ?></span>
						<?php echo wp_kses( $this->conjure->loading_spinner(), $this->conjure->loading_spinner_allowed_html() ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $next ); ?></a>
				<?php endif; ?>
				<?php wp_nonce_field( 'conjure' ); ?>
			</footer>
		</form>

		<?php
		$this->conjure->logger->debug( __( 'The plugin installation step has been displayed', 'ConjureWP' ) );
	}

	/**
	 * Page setup
	 */
	public function content() {
		// Check if any demo files are registered.
		if ( empty( $this->conjure->import_files ) ) {
			$this->conjure->logger->error(
				'No demo import files are registered! The conjure_import_files filter returned empty.',
				array(
					'is_manual_upload_mode' => $this->conjure->is_manual_upload_mode(),
				)
			);
		}

		// Get the selected demo index from transient or default to 0.
		$selected_demo_index = get_transient( 'conjure_selected_demo_index' );

		// If no demo is selected or invalid, use the first demo.
		if ( false === $selected_demo_index || ! isset( $this->conjure->import_files[ $selected_demo_index ] ) ) {
			$selected_demo_index = 0;
			// Store it for consistency.
			if ( ! empty( $this->conjure->import_files ) ) {
				set_transient( 'conjure_selected_demo_index', $selected_demo_index, HOUR_IN_SECONDS );
			}
		}

		$this->conjure->logger->debug(
			'Content step loading',
			array(
				'selected_demo_index' => $selected_demo_index,
				'total_import_files' => count( $this->conjure->import_files ),
				'import_files_keys' => ! empty( $this->conjure->import_files ) ? array_keys( $this->conjure->import_files[0] ) : array(),
			)
		);

		$import_info = $this->conjure->get_import_data_info( $selected_demo_index );

		// If import info is false or empty, log error and provide fallback.
		if ( false === $import_info || empty( $import_info ) ) {
			$this->conjure->logger->error(
				'Failed to load import data info for content step',
				array(
					'selected_index' => $selected_demo_index,
					'import_files_count' => count( $this->conjure->import_files ),
					'import_files_empty' => empty( $this->conjure->import_files ),
					'is_manual_upload_mode' => $this->conjure->is_manual_upload_mode(),
				)
			);

			// Check if we're in manual upload mode.
			if ( ! $this->conjure->is_manual_upload_mode() ) {
				// Provide a minimal fallback to prevent blank screen.
				$import_info = array(
					'content' => true,
				);
			} else {
				$import_info = array();
			}
		}

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Text strings.
		$header    = $strings['import-header'];
		$paragraph = $strings['import'];
		$action    = $strings['import-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$import    = $strings['btn-import'];

		$multi_import = ( 1 < count( $this->conjure->import_files ) ) ? 'is-multi-import' : null;

		// Initialize server health checker.
		require_once trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/includes/class-conjure-server-health.php';
		$server_health = $this->conjure->get_server_health();
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'content' ) ), $this->conjure->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

		<h1><?php echo esc_html( $header ); ?></h1>

		<p><?php echo esc_html( $paragraph ); ?></p>
		
		<?php
		// @freemius:premium-start
		$can_auto_install = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::can_auto_install_plugins() : false;

		if ( ! $can_auto_install && function_exists( 'con_fs' ) && con_fs() ) :
			?>
			<div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0; border-radius: 4px;">
				<p style="margin: 0; font-size: 14px; line-height: 1.6;">
					<?php
					printf(
						/* translators: %s: Link to upgrade page */
						esc_html__( 'Want to save time? %s for automatic plugin installation.', 'ConjureWP' ),
						'<a href="' . esc_url( con_fs()->get_upgrade_url() ) . '" style="color: #0073aa; font-weight: 600; text-decoration: underline;">' . esc_html__( 'Upgrade to Premium', 'ConjureWP' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		endif;
		// @freemius:premium-end

		// Display server health check.
		echo wp_kses_post( $server_health->get_health_check_styles() );
		$server_health->render_complete(
			array(
				'show_title'       => true,
				'title'            => __( 'Server Health Check', 'ConjureWP' ),
				'requirements_url' => '',
				'theme_name'       => $this->conjure->theme->name,
			)
		);
		?>

		<?php if ( 1 < count( $this->conjure->import_files ) ) : ?>

			<div class="conjure__demo-selector">
				<h3 style="margin-bottom: 0.5em; font-weight: 600; font-size: 1.1em;">
					<?php echo esc_html__( 'Select your demo:', 'ConjureWP' ); ?>
				</h3>
				<p class="conjure__demo-grid-description">
					<?php echo esc_html__( 'Choose which demo content to import.', 'ConjureWP' ); ?>
				</p>
				<div class="conjure__demo-grid">
					<?php foreach ( $this->conjure->import_files as $index => $import_file ) : ?>
						<div class="conjure__demo-card js-conjure-demo-card-import <?php echo ( $selected_demo_index !== false && $selected_demo_index === $index ) ? 'is-selected' : ''; ?>" data-demo-index="<?php echo esc_attr( $index ); ?>">
							<div class="conjure__demo-card-image">
								<?php if ( ! empty( $import_file['import_preview_image_url'] ) ) : ?>
									<img src="<?php echo esc_url( $import_file['import_preview_image_url'] ); ?>" alt="<?php echo esc_attr( $import_file['import_file_name'] ); ?>" loading="lazy">
								<?php else : ?>
									<div class="conjure__demo-card-placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="conjure__demo-card-content">
								<h4 class="conjure__demo-card-title"><?php echo esc_html( $import_file['import_file_name'] ); ?></h4>
								<?php if ( ! empty( $import_file['import_notice'] ) ) : ?>
									<p class="conjure__demo-card-description"><?php echo esc_html( $import_file['import_notice'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<span class="js-conjure-select-spinner" style="display:none;">Loading...</span>
			</div>
		<?php endif; ?>

			<a id="conjure__drawer-trigger" class="conjure__button conjure__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>

		</div>

		<form action="" method="post" class="<?php echo esc_attr( $multi_import ); ?> <?php echo $this->conjure->is_manual_upload_mode() ? 'conjure-manual-upload-mode' : ''; ?>">

			<?php if ( $this->conjure->is_manual_upload_mode() ) : ?>

				<ul class="conjure__drawer conjure__drawer--import-content conjure__drawer--upload js-conjure-drawer-import-content">
					<?php
					/*
					 * Output developer-generated markup as-is. We don't wrap this in
					 * wp_kses_post() because it strips <input> and <button> along with
					 * data-* attributes that the JS relies on to toggle the upload
					 * accordion. All content is built from sanitised plugin options
					 * (no user input), so it's safe to emit directly.
					 */
					echo $this->conjure->get_manual_upload_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</ul>

			<?php else : ?>

			<ul class="conjure__drawer conjure__drawer--import-content conjure__drawer--upload js-conjure-drawer-import-content">
				<?php
				// Add health telemetry at the top of the drawer.
				// Hook at line 2574 area for extensibility via conjure_health_telemetry_in_drawer filter.
				$health_telemetry_html = '';
				if ( $server_health && $server_health->is_enabled() ) {
					$health_telemetry_html = $server_health->render_drawer_telemetry();
				}
				$health_telemetry_html = apply_filters( 'conjure_health_telemetry_in_drawer', $health_telemetry_html, $server_health, $this->conjure );
				if ( ! empty( $health_telemetry_html ) ) {
					echo wp_kses_post( $health_telemetry_html );
				}

				// Check if no demos are registered.
				if ( empty( $this->conjure->import_files ) && ! $this->conjure->is_manual_upload_mode() ) {
					?>
					<li class="conjure__drawer--import-content__list-item" style="color: #d63638; padding: 20px;">
						<strong>⚠️ No Demo Files Registered</strong><br><br>
						No demo import files have been registered. To use ConjureWP, you need to:<br><br>
						<ol style="margin-left: 20px;">
							<li>Add demo files to your theme (content.xml, widgets.json, etc.)</li>
							<li>Register them using the <code>conjure_import_files</code> filter</li>
						</ol>
						<br>
						See: <code>ConjureWP-config.php</code> or <code>examples/</code> directory for examples.
					</li>
					<?php
				} else {
					$import_steps_html = $this->conjure->get_import_steps_html( $import_info );

					// Debug: Log if HTML is empty.
					if ( empty( trim( $import_steps_html ) ) ) {
						$this->conjure->logger->error(
							'Import steps HTML is empty!',
							array(
								'import_info' => $import_info,
								'selected_demo_index' => $selected_demo_index,
								'import_files_count' => count( $this->conjure->import_files ),
								'import_files_data' => ! empty( $this->conjure->import_files[ $selected_demo_index ] ) ? array_keys( $this->conjure->import_files[ $selected_demo_index ] ) : 'index not set',
							)
						);
						?>
						<li class="conjure__drawer--import-content__list-item" style="color: #d63638; padding: 20px;">
							<strong>⚠️ No Import Options Found</strong><br><br>
							The selected demo has no import data configured.<br><br>
							<strong>Check:</strong>
							<ul style="margin-left: 20px;">
								<li>Demo files exist (content.xml, widgets.json, etc.)</li>
								<li>File paths are correct in your config</li>
								<li>Check logs at: <code>wp-content/uploads/conjure-logs/</code></li>
							</ul>
						</li>
						<?php
					} else {
						echo wp_kses_post( $import_steps_html );
					}
				}
				?>
			</ul>

			<?php endif; ?>

			<footer class="conjure__content__footer">

				<a id="close" href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--closer conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>

				<a id="skip" href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>

				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next" data-callback="install_content">
					<span class="conjure__button--loading__text"><?php echo esc_html( $import ); ?></span>

					<div class="conjure__progress-bar">
						<span class="js-conjure-progress-bar"></span>
					</div>

					<span class="js-conjure-progress-bar-percentage">0%</span>
				</a>

				<?php wp_nonce_field( 'conjure' ); ?>
			</footer>
		</form>

		<?php
		$this->conjure->logger->debug( __( 'The content import step has been displayed', 'ConjureWP' ) );
	}


	/**
	 * Final step
	 */
	public function ready() {

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Text strings.
		$header    = $strings['ready-header'];
		$paragraph = $strings['ready%s'];
		$action    = $strings['ready-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$big_btn   = $strings['ready-big-button'];

		// Links.
		$links = array();

		for ( $i = 1; $i < 4; $i++ ) {
			if ( ! empty( $strings[ "ready-link-$i" ] ) ) {
				$links[] = $strings[ "ready-link-$i" ];
			}
		}

		$links_class = empty( $links ) ? 'conjure__content__footer--nolinks' : null;

		$allowed_html_array = array(
			'a' => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
		);

		update_option( 'conjure_' . $this->conjure->slug . '_completed', time() );
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'done' ) ), $this->conjure->svg_allowed_html() ); ?>

			<h1><?php echo esc_html( $header ); ?></h1>

			<p><?php echo wp_kses( $paragraph, $allowed_html_array ); ?></p>

		</div>

		<footer class="conjure__content__footer conjure__content__footer--fullwidth <?php echo esc_attr( $links_class ); ?>">

			<a href="<?php echo esc_url( $this->conjure->ready_big_button_url ); ?>" class="conjure__button conjure__button--blue conjure__button--fullwidth conjure__button--popin"><?php echo esc_html( $big_btn ); ?></a>

			<?php if ( ! empty( $links ) ) : ?>
				<a id="conjure__drawer-trigger" class="conjure__button conjure__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>

				<ul class="conjure__drawer conjure__drawer--extras">

					<?php foreach ( $links as $link ) : ?>
						<li><?php echo wp_kses( $link, $allowed_html_array ); ?></li>
					<?php endforeach; ?>

				</ul>
			<?php endif; ?>

		</footer>

		<?php
		$this->conjure->logger->debug( __( 'The final step has been displayed', 'ConjureWP' ) );
	}

	/**
	 * Get plugins for installation
	 *
	 * Uses the custom plugin manager to get demo-specific plugins.
	 *
	 * @param int|string|null $demo_index Optional. Demo index or slug for demo-specific plugins.
	 * @return    array Array of plugins organized by status.
	 */
	public function get_plugins( $demo_index = null ) {
		// Default empty plugin array.
		$plugins = array(
			'all'      => array(), // All plugins which need action.
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);

		if ( ! $this->conjure->demo_plugin_manager ) {
			$this->conjure->logger->debug( 'Demo plugin manager not available' );
			return $plugins;
		}

		// Get demo-specific plugins if demo index provided.
		if ( null !== $demo_index ) {
			$demo_plugins = $this->conjure->demo_plugin_manager->get_demo_plugins_with_status( $demo_index, $this->conjure->import_files );

			if ( ! empty( $demo_plugins['all'] ) ) {
				$this->conjure->logger->info( 'Using demo-specific plugins for demo index: ' . $demo_index );
				return $demo_plugins;
			}

			$this->conjure->logger->debug( 'No demo-specific plugins found for demo index: ' . $demo_index );
		}

		return $plugins;
	}
}
