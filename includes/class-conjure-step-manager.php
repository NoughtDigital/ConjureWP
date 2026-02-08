<?php
/**
 * Step Manager class
 *
 * Handles wizard step state and navigation.
 *
 * @package   Conjure WP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Nought Digital
 * @license   Licensed GPLv3 for Open Source Use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure Step Manager class.
 */
class Conjure_Step_Manager {

	/**
	 * Reference to main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
	}

	/**
	 * Get individual step completion state.
	 *
	 * @param string $step_key The step key to check.
	 * @return bool|int False if not completed, timestamp if completed.
	 */
	public function get_step_completion_state( $step_key ) {
		$step_states = get_option( 'conjure_' . $this->conjure->slug . '_step_completion', array() );
		return isset( $step_states[ $step_key ] ) && $step_states[ $step_key ];
	}

	/**
	 * Mark a step as completed.
	 *
	 * @param string $step_key The step key to mark as completed.
	 */
	public function mark_step_completed( $step_key ) {
		$step_states = get_option( 'conjure_' . $this->conjure->slug . '_step_completion', array() );
		$step_states[ $step_key ] = time();
		update_option( 'conjure_' . $this->conjure->slug . '_step_completion', $step_states );

		$this->logger->info(
			sprintf( __( 'Step "%s" marked as completed', 'conjurewp' ), $step_key ),
			array( 'step' => $step_key )
		);
	}

	/**
	 * Reset a specific step's completion state.
	 *
	 * @param string $step_key The step key to reset.
	 */
	public function reset_step( $step_key ) {
		$step_states = get_option( 'conjure_' . $this->conjure->slug . '_step_completion', array() );
		
		if ( isset( $step_states[ $step_key ] ) ) {
			unset( $step_states[ $step_key ] );
			update_option( 'conjure_' . $this->conjure->slug . '_step_completion', $step_states );

			$this->logger->info(
				sprintf( __( 'Step "%s" reset for rerunning', 'conjurewp' ), $step_key ),
				array( 'step' => $step_key )
			);
		}
	}

	/**
	 * Reset all step completion states.
	 */
	public function reset_all_steps() {
		delete_option( 'conjure_' . $this->conjure->slug . '_step_completion' );
		delete_option( 'conjure_' . $this->conjure->slug . '_completed' );

		$this->logger->info( __( 'All Conjure steps have been reset', 'conjurewp' ) );
	}

	/**
	 * Add admin bar menu for rerunning steps (power users only).
	 *
	 * Note: This only appears when CONJURE_TOOLS_ENABLED constant is defined.
	 * Add this to wp-config.php to enable:
	 * define( 'CONJURE_TOOLS_ENABLED', true );
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public function add_admin_bar_rerun_menu( $wp_admin_bar ) {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add parent menu item.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'conjure-rerun',
				'title' => '<span class="ab-icon dashicons-update"></span><span class="ab-label">' . esc_html__( 'Conjure WP', 'conjurewp' ) . '</span>',
				'href'  => admin_url( 'themes.php?page=' . $this->conjure->conjure_url ),
				'meta'  => array(
					'title' => esc_html__( 'Rerun Conjure WP steps', 'conjurewp' ),
				),
			)
		);

		// Get step completion states.
		$step_states = get_option( 'conjure_' . $this->conjure->slug . '_step_completion', array() );

		// Define available steps for rerunning.
		$rerun_steps = array(
			'child'   => esc_html__( 'Child Theme', 'conjurewp' ),
			'license' => esc_html__( 'License Activation', 'conjurewp' ),
			'plugins' => esc_html__( 'Plugins', 'conjurewp' ),
			'content' => esc_html__( 'Content Import', 'conjurewp' ),
		);

		$rerun_steps = apply_filters( $this->conjure->theme->template . '_conjure_rerun_steps', $rerun_steps, $this->conjure->steps );

		// Add individual step reset options.
		foreach ( $rerun_steps as $step_key => $step_label ) {
			// Skip license step if not enabled.
			if ( 'license' === $step_key && ! $this->conjure->license_step_enabled ) {
				continue;
			}

			$is_completed = isset( $step_states[ $step_key ] ) && $step_states[ $step_key ];
			$status_icon = $is_completed ? '✓' : '○';

			$wp_admin_bar->add_node(
				array(
					'parent' => 'conjure-rerun',
					'id'     => 'conjure-rerun-' . $step_key,
					'title'  => $status_icon . ' ' . $step_label,
					'href'   => wp_nonce_url(
						admin_url( '?conjure_reset_step=' . $step_key ),
						'conjure_reset_step_' . $step_key,
						'_conjure_nonce'
					),
					'meta'   => array(
						'title' => sprintf(
							/* translators: %s: step name */
							esc_html__( 'Reset and rerun: %s', 'conjurewp' ),
							$step_label
						),
					),
				)
			);
		}

		// Add divider.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-rerun-divider',
				'title'  => '<hr style="margin: 5px 0; border: none; border-top: 1px solid rgba(255,255,255,0.2);">',
				'href'   => false,
				'meta'   => array(
					'html' => '<hr style="margin: 5px 0; border: none; border-top: 1px solid rgba(255,255,255,0.2);">',
				),
			)
		);

		// Add "Reset All" option.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-reset-all',
				'title'  => '↻ ' . esc_html__( 'Reset All Steps', 'conjurewp' ),
				'href'   => wp_nonce_url(
					admin_url( '?conjure_reset_step=all' ),
					'conjure_reset_step_all',
					'_conjure_nonce'
				),
				'meta'   => array(
					'title' => esc_html__( 'Reset all steps and rerun complete onboarding', 'conjurewp' ),
				),
			)
		);

		// Add "Open Wizard" option.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-open-wizard',
				'title'  => '→ ' . esc_html__( 'Open Wizard', 'conjurewp' ),
				'href'   => admin_url( 'themes.php?page=' . $this->conjure->conjure_url ),
				'meta'   => array(
					'title' => esc_html__( 'Open Conjure WP setup wizard', 'conjurewp' ),
				),
			)
		);
	}

	/**
	 * Handle step reset requests from admin bar.
	 */
	public function handle_step_reset() {
		// Check if this is a reset request.
		if ( ! isset( $_GET['conjure_reset_step'] ) || ! isset( $_GET['_conjure_nonce'] ) ) {
			return;
		}

		$step = sanitize_key( $_GET['conjure_reset_step'] );

		// Verify nonce.
		if ( ! wp_verify_nonce( $_GET['_conjure_nonce'], 'conjure_reset_step_' . $step ) ) {
			wp_die( esc_html__( 'Security check failed.', 'conjurewp' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ) );
		}

		// Handle reset.
		if ( 'all' === $step ) {
			$this->reset_all_steps();
			$redirect_url = admin_url( 'themes.php?page=' . $this->conjure->conjure_url );
			$message = __( 'All steps have been reset. You can now rerun the complete onboarding.', 'conjurewp' );
		} else {
			$this->reset_step( $step );
			$redirect_url = admin_url( 'themes.php?page=' . $this->conjure->conjure_url . '&step=' . $step );
			$message = sprintf(
				/* translators: %s: step name */
				__( 'Step "%s" has been reset. You can now rerun this step.', 'conjurewp' ),
				ucfirst( str_replace( '_', ' ', $step ) )
			);
		}

		// Set admin notice.
		set_transient( 'conjure_admin_notice', $message, 30 );

		// Redirect to the wizard.
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display admin notices for step resets.
	 */
	public function display_admin_notices() {
		$message = get_transient( 'conjure_admin_notice' );

		if ( $message ) {
			delete_transient( 'conjure_admin_notice' );
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php echo esc_html__( 'Conjure WP:', 'conjurewp' ); ?></strong> <?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}
}

