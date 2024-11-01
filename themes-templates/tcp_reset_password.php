<?php
/**
 * Reset password email
 * 
 * @package TheCartPress
 * @subpackage Themes Templates
 * @see modules/CustomLogin.class.php
 */
 
/**
 * This file is part of TheCartPress.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
?>

<p>
	<?php printf( __( 'Hi, %s', 'tcp' ), get_user_meta( $user_data->ID, 'first_name', true ) ); ?>
</p>

<p>
	<?php printf( __( 'It looks like you need to reset your password on the site. If this is correct, simply click the link below. If you were not the one responsible for this request, ignore this email and nothing will happen.', 'tcp' ) ); ?>
</p>

<p>
	<?php printf( __( '<a href="%s">Reset Your Password</a>', 'tcp' ), $reset_url ); ?>
</p>