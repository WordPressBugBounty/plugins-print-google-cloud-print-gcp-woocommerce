<?php
namespace Zprint\Support;

use WP_Error;
use Zprint\Support\Model\Link;

class Support extends Page {
	public const KEY = Setup::KEY;

	public const DELETE_AFTER_DAYS_KEY     = Setup::KEY . '_delete_user_after_days';
	public const DELETE_AFTER_DAYS_DEFAULT = 7;
	public const ID_KEY                    = Setup::KEY . '_user_id';
	public const ID_DEFAULT                = 0;
	public const LOGIN_KEY                 = Setup::KEY . '_user_login';
	public const LOGIN_DEFAULT             = '';
	public const CREATED_AT_KEY            = Setup::KEY . '_user_created_at';
	public const CREATED_AT_DEFAULT        = 0;
	public const EMAIL_KEY                 = Setup::KEY . '_user_email';
	public const EMAIL_DEFAULT             = '';

	protected const SETTING_KEYS = array(
		self::DELETE_AFTER_DAYS_KEY,
	);

	public const CREATE_QUERY_KEY           = Setup::KEY . '_create_user';
	public const DELETE_QUERY_KEY           = Setup::KEY . '_delete_user';
	public const RECREATE_QUERY_KEY         = Setup::KEY . '_recreate_user';
	public const UPDATE_CREATE_AT_QUERY_KEY = Setup::KEY . '_update_create_user';

	public function __construct() {
		parent::__construct();
		ActionQuery::add( static::CREATE_QUERY_KEY, array( $this, 'handle_create_user' ) );
		ActionQuery::add( static::DELETE_QUERY_KEY, array( $this, 'handle_delete_user' ) );
		ActionQuery::add( static::RECREATE_QUERY_KEY, array( $this, 'handle_recreate_user' ) );
		ActionQuery::add( static::UPDATE_CREATE_AT_QUERY_KEY, array( $this, 'handle_update_create_at' ) );
		add_action( 'deleted_user', array( $this, 'delete_data_when_user_deleted' ) );
		add_action( 'admin_init', array( $this, 'delete_user_after_days' ) );
	}

	public function add_page(): void {
		$page_title = __( 'BizSwoop Support', 'Print-Google-Cloud-Print-GCP-WooCommerce' );

		add_submenu_page(
			'tools.php',
			$page_title,
			$page_title,
			'administrator',
			static::KEY,
			array( $this, 'render_page' ),
			0
		);
	}

	public function add_sections(): void {
		$this->add_general_section( static::KEY . '_general' );
		$this->add_user_data_section( static::KEY . '_user_data' );
	}

	protected function add_general_section( string $section_key ): void {
		$this->add_section(
			$section_key,
			__( 'Support User', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
			function(): void {
				?>
				<div class="da-setting-section__description">
					<?php echo wp_kses( __( 'This section allows you to quickly create a user with administrator role that can be used by the support team to access the website for debugging purposes.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), array( 'b' => array() ) ); ?>
				</div>
				<?php
			}
		);
		$this->add_setting(
			$section_key,
			static::DELETE_AFTER_DAYS_KEY,
			__( 'Delete user after days', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
			Control\Text::class,
			static::DELETE_AFTER_DAYS_DEFAULT,
			array(
				'type'        => 'number',
				'min'         => 0,
				'step'        => 1,
				'description' => __( 'Set to 0 to disable automatic deletion.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
			)
		);
	}

	protected function add_user_data_section( string $section_key ): void {
		$this->add_section(
			$section_key,
			'',
			array( $this, 'render_user_data_section' )
		);
	}

	public function render_user_data_section(): void {
		?>
		<div class="da-support-user">
			<?php
			if ( 0 === get_option( static::ID_KEY, self::ID_DEFAULT ) ) {
				$this->render_user_data_section_empty();

				return;
			}
			?>
			<ul class="da-support-user__credentials" id="da-support-user-credentials">
				<li style="display: none;"><?php echo esc_html( wp_login_url() ); ?></li>
				<li>
					<b><?php echo esc_html__( 'Username', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>:</b> <span><?php echo esc_html( get_option( static::LOGIN_KEY, static::LOGIN_DEFAULT ) ); ?></span>
				</li>
			</ul>
			<?php echo wp_kses_post( static::get_details( 'da-support-user__details' ) ); ?>
			<ul class="da-support-user__controls">
				<?php if ( static::is_allowed_continue_existence() ) { ?>
					<li>
						<a
							href="<?php echo esc_url( ActionQuery::get_url( static::UPDATE_CREATE_AT_QUERY_KEY ) ); ?>"
						>
							<?php echo esc_html__( 'Continue existence', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
						</a>
					</li>
				<?php } ?>
				<li>
					<?php
					( new Link(
						__( 'Recreate user', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
						ActionQuery::get_url( static::RECREATE_QUERY_KEY ),
						static::get_recreation_confirmation_massage()
					) )->render();
					?>
				</li>
				<li>
					<?php
					( new Link(
						__( 'Delete user', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
						ActionQuery::get_url( static::DELETE_QUERY_KEY ),
						static::get_deletion_confirmation_massage(),
						false,
						'da-support-user__link-danger'
					) )->render();
					?>
				</li>
			</ul>
		</div>
		<?php
	}

	public static function get_details( string $classname, bool $display_email = true ): string {
		ob_start();

		$email          = get_option( static::EMAIL_KEY, static::EMAIL_DEFAULT );
		$is_auto_delete = 0 < intval( get_option( static::DELETE_AFTER_DAYS_KEY, static::DELETE_AFTER_DAYS_DEFAULT ) );

		if ( $email || $is_auto_delete ) {
			?>
			<ul class="<?php echo esc_attr( $classname ); ?>">
				<?php if ( $email && $display_email ) { ?>
					<li>
						<b><?php echo esc_html__( 'Email', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>:</b> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
					</li>
					<?php
				}

				if ( $is_auto_delete ) {
					?>
					<li>
						<b><?php echo esc_html__( 'Will be auto-deleted', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>:</b>
						<?php
						echo sprintf(
							esc_html__( 'after less than %s days', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
							esc_html( static::get_days_for_auto_delete() )
						);
						?>
					</li>
				<?php } ?>
			</ul>
			<?php
		}

		return ob_get_clean();
	}

	public static function get_recreation_confirmation_massage(): string {
		return __( 'Are you sure to recreate the support user? This will cause loss of access for those providing support, you will need to reshare this to keep their access.', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
	}

	public static function get_deletion_confirmation_massage(): string {
		return __( 'Are you sure to delete the support user? This will cause loss of access for those providing support.', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
	}

	protected function render_user_data_section_empty(): void {
		?>
		<div class="da-support-user__description">
			<?php echo esc_html__( 'Support user not yet created.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
		</div>
		<div class="da-support-user__notice">
			<b><?php echo esc_html__( 'Note', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>:</b> <?php echo esc_html__( 'A temporary Development Assistant plugin is added to create the support user. It appears under Plugins and will be automatically removed along with the support user when the session ends.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
		</div>
		<ul class="da-support-user__controls">
			<li>
				<a href="<?php echo esc_url( ActionQuery::get_url( static::CREATE_QUERY_KEY ) ); ?>">
					<?php echo esc_html__( 'Create user in one click', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
				</a>
			</li>
		</ul>
		<?php
	}

	public static function is_allowed_continue_existence(): bool {
		return 1 <= intval( get_option( static::DELETE_AFTER_DAYS_KEY, static::DELETE_AFTER_DAYS_DEFAULT ) ) &&
		       1 === static::get_days_for_auto_delete();
	}

	protected static function get_days_for_auto_delete(): int {
		$created_at   = intval( get_option( static::CREATED_AT_KEY, static::CREATED_AT_DEFAULT ) );
		$delete_after = intval( get_option( static::DELETE_AFTER_DAYS_KEY, static::DELETE_AFTER_DAYS_DEFAULT ) );
		$result       = ceil( ( $created_at + $delete_after * DAY_IN_SECONDS - time() ) / DAY_IN_SECONDS );

		return 0 < $result ? $result : 1;
	}

	public function handle_create_user(): void {
		static::create_user();
		Notice::add_transient( __( 'Support user created.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), 'success' );
		$this->render_dev_assist_installed_notice();
	}

	public function handle_delete_user(): void {
		static::delete_user();
		Notice::add_transient( __( 'Support user deleted.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), 'success' );
	}

	public function handle_recreate_user(): void {
		static::delete_user();
		static::create_user();
		Notice::add_transient( __( 'Support user recreated.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), 'success' );
		$this->render_dev_assist_installed_notice();
	}

	public function render_dev_assist_installed_notice(): void {
		Notice::add_transient(
			__('Development Assistant was automatically installed to provide support from BizSwoop. It will be automatically deleted along with the support user.', 'zpos-wp-api'),
			'default'
		);
	}

	protected static function share_to_email( string $login, string $password ): void {
		$email = 'support@bizswoop.com';

		$subject = sprintf(
			__( 'Support access to %s', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
			str_replace( array( 'http://', 'https://' ), '', home_url() )
		);
		$content = static::get_share_to_email_content( $login, $password );
		$user    = wp_get_current_user();
		$from    = $user->user_email ?
			'From: ' . $user->display_name . ' <' . $user->user_email . '>' :
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8', $from );

		if ( ! wp_mail( $email, $subject, $content, $headers ) ) {
			Notice::add_transient( __( 'An error occurred while trying to send the email.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), 'error' );

			return;
		}

		wp_update_user(
			array(
				'ID'         => get_option( static::ID_KEY, static::ID_DEFAULT ),
				'user_email' => $email,
			)
		);
		update_option( static::EMAIL_KEY, $email );
	}

	protected static function get_share_to_email_content( string $login, string $password ): string {
		$home_url  = home_url();
		$login_url = wp_login_url();

		ob_start();

		echo sprintf(
			esc_html__( 'You have been requested for support and granted administrative access to %s.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ),
			'<a href="' . esc_url( $home_url ) . '">' . esc_html( $home_url ) . '</a>'
		);

		if ( 0 < intval( get_option( static::DELETE_AFTER_DAYS_KEY, static::DELETE_AFTER_DAYS_DEFAULT ) ) ) {
			?>
			<br><br>
			<b><?php echo esc_html__( 'Note!', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?></b> <?php echo sprintf( esc_html__( 'User will be auto-deleted after less than %s days.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), esc_html( static::get_days_for_auto_delete() ) ); ?>
		<?php } ?>
		<br><br>
		<a href="<?php echo esc_url( $login_url ); ?>"><?php echo esc_html( $login_url ); ?></a>
		<br>
		<b><?php echo esc_html__( 'Username', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>:</b> <?php echo esc_html( $login ); ?>
		<br>
		<b><?php echo esc_html__( 'Password', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>:</b> <?php echo esc_html( $password ); ?>
		<?php
		return ob_get_clean();
	}

	protected static function create_user(): void {
		$time     = time();
		$login    = 'bizswoop_' . $time;
		$password = wp_generate_password();

		$user_id = wp_insert_user(
			array(
				'user_login' => $login,
				'user_pass'  => $password,
				'role'       => 'administrator',
			)
		);

		if ( $user_id instanceof WP_Error ) {
			Notice::add_transient( $user_id->get_error_message(), 'error' );

			return;
		}

		update_option( static::ID_KEY, $user_id );
		update_option( static::LOGIN_KEY, $login );
		update_option( static::CREATED_AT_KEY, $time );
		static::share_to_email( $login, $password );
		DevAssist::install();
	}

	public function handle_update_create_at(): void {
		update_option( static::CREATED_AT_KEY, time() );
		Notice::add_transient( __( 'Support user existence extended.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ), 'success' );
	}

	protected static function delete_user(): void {
		$user_id = get_option( static::ID_KEY, static::ID_DEFAULT );

		if ( 0 === $user_id ) {
			return;
		}

		wp_delete_user( $user_id );
	}

	public function delete_user_after_days(): void {
		$created_at   = intval( get_option( static::CREATED_AT_KEY, static::CREATED_AT_DEFAULT ) );
		$delete_after = intval( get_option( static::DELETE_AFTER_DAYS_KEY, static::DELETE_AFTER_DAYS_DEFAULT ) );

		if ( 0 === $created_at || 0 === $delete_after ) {
			return;
		}

		$delete_time = $created_at + $delete_after * DAY_IN_SECONDS;

		if ( $delete_time < time() ) {
			static::delete_user();
		}
	}

	public function delete_data_when_user_deleted( int $user_id ): void {
		if ( intval( get_option( static::ID_KEY, static::ID_DEFAULT ) ) !== $user_id ) {
			return;
		}

		static::delete_user_data();
		DevAssist::uninstall();
	}

	protected static function delete_user_data(): void {
		delete_option( static::ID_KEY );
		delete_option( static::LOGIN_KEY );
		delete_option( static::CREATED_AT_KEY );
		delete_option( static::EMAIL_KEY );
	}

	public static function reset(): void {
		static::delete_user();
		parent::reset();
	}
}
