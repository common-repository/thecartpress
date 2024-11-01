<?php
/**
 * Login form template
 * 
 * @since TheCartPress 1.5.0
 */

if ( ! is_user_logged_in() ) : ?>
<div id="tcp_login">
	<?php if ( $attributes['show_title'] ) : ?>
		<h2><?php _e( 'Sign In', 'tcp' ); ?></h2>
	<?php endif; ?>

	<!-- Show errors if there are any -->
	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<?php foreach ( $attributes['errors'] as $error ) : ?>
			<p class="login-error">
				<?php echo $error; ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>

	<!-- Show logged out message if user just logged out -->
	<?php if ( $attributes['logged_out'] ) : ?>
		<p class="login-info">
			<?php _e( 'You have signed out. Would you like to sign in again?', 'tcp' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $attributes['registered'] ) : ?>
		<p class="login-info">
			<?php
				printf(
					__( 'You have successfully registered to <strong>%s</strong>. We have emailed your password to the email address you entered.', 'tcp' ),
					get_bloginfo( 'name' )
				);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $attributes['lost_password_sent'] ) : ?>
		<p class="login-info">
			<?php _e( 'Check your email for a link to reset your password.', 'tcp' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $attributes['password_updated'] ) : ?>
		<p class="login-info">
			<?php _e( 'Your password has been changed. You can sign in now.', 'tcp' ); ?>
		</p>
	<?php endif; ?>

	<?php echo apply_filters( 'login_form_top', '' ); ?>

	<?php $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';

	/**
	 * Fires when the login form is initialized.
	 *
	 * @since 3.2.0
	 */
	do_action( 'login_init' );
	?>

	<div class="panel panel-login-register">
		<div class="panel-body">
			<ul class="nav nav-tabs" role="tablist">
				<li class="active">
					<a href="#login-tab" data-toggle="tab"><?php _e( 'Login', 'tcp' ); ?></a>
				</li>
				<li>
					<a href="#register-tab" data-toggle="tab"><?php _e( 'Register', 'tcp' ); ?></a>
				</li>
			</ul>

			<div class="tab-content">
				<br/>
				<div id="login-tab" class="tab-pane fade in active">

				<?php $use_custom_login_ver_two = thecartpress()->get_setting( 'use_custom_login_ver_two', false );
				if ( !$use_custom_login_ver_two ) :
					tcp_login_form( array(
						'label_username'	=> __( 'User', 'tcp' ),
						'label_log_in'		=> __( 'Sign In', 'tcp' ),
						'redirect'			=> $attributes['redirect'],
					) );

				else : ?>

					<form id="login-form" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post" role="form" class="form-horizontal">

						<?php //echo apply_filters( 'login_form_top', '', $attributes ); ?>

						<div class="form-group">
							<div class="col-sm-4">
								<input type="text" name="log" id="log" tabindex="1" class="form-control" placeholder="<?php _e( 'Username', 'tcp' ); ?>" value="">
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-4">
								<input type="password" name="pwd" id="pwd" tabindex="2" class="form-control" placeholder="<?php _e( 'Password', 'tcp' ); ?>">
							</div>
						</div>

						<?php //echo apply_filters( 'login_form_middle', '', $attributes );
						do_action( 'login_form' ); ?>

						<div class="form-group text-center">
							<div class="col-sm-4">
								<input type="checkbox" tabindex="3" class="" name="rememberme" id="remember">
								<label for="remember"> <?php _e( 'Remember Me', 'tcp' ); ?></label>
							</div>
							<input type="hidden" name="redirect_to" value="<?php echo esc_url( $attributes['redirect'] ); ?>" />
						</div>

						<div class="form-group">
							<div class="col-sm-2 col-sm-offset-1">
								<button type="submit" name="login-submit" id="login-submit" tabindex="4" class="form-control btn btn-default"><?php _e( 'Log In', 'tcp' ); ?></button>
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-4">
								<div class="text-center">
									<a href="<?php echo wp_lostpassword_url(); ?>" tabindex="5" class="forgot-password"><?php _e( 'Forgot Password?', 'tcp' );?></a>
								</div>
							</div>
						</div>

						<?php //echo apply_filters( 'login_form_bottom', '', $attributes ); ?>
					</form>

				<?php endif; ?>

				</div>

				<div id="register-tab" class="tab-pane fade">
					<?php tcp_register_form(); ?>
				</div>

			</div><!-- .tab-content -->
		</div><!-- .panel-body -->
	</div><!-- .panel-login-register -->

	<?php do_action( 'login_footer' ); ?>
</div><!-- #tcp_login -->

<?php else:  ?>

<?php _e( 'You are already signed in', 'tcp' ); ?>

<?php endif;