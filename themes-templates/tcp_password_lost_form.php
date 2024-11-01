<div id="password-lost-form" class="widecolumn">
	<?php if ( $attributes['show_title'] ) : ?>
		<h3><?php _e( 'Forgot Your Password?', 'tcp' ); ?></h3>
	<?php endif; ?>

	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<?php foreach ( $attributes['errors'] as $error ) : ?>
			<p>
				<?php echo $error; ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>

	<p>
		<?php
			_e(	"Enter your email address and we'll send you a link you can use to pick a new password.", 'tcp' );
		?>
	</p>

	<form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
		<p class="form-row">
			<label for="user_login"><?php _e( 'Email', 'tcp' ); ?>
			<input type="text" name="user_login" id="user_login">
		</p>
		
		<?php if ( isset( $attributes['redirect_to'] ) ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $attributes['redirect_to'] ); ?>" />
		<?php endif; ?>

		<?php do_action( 'lostpassword_form' ); ?>

		<p class="lostpassword-submit">
			<input type="submit" name="submit" class="lostpassword-button btn btn-default" value="<?php _e( 'Reset Password', 'tcp' ); ?>"/>
		</p>
	</form>
</div>