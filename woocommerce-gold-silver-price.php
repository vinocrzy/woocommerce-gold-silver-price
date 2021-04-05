<?php

/**
 * Plugin Name: WooCommerce Gold and Silver and Platinam Price
 * Description: Adds a Gold Price for  22K Gold, 18k Gold and Silver,Platinam products making easy to update their prices
 * Version: 1.0
 * Author: Vino Crazy
 * Author URI: https://vinocrazy.com/
 * Requires at least: 3.5
 * Tested up to: 5.4.2
 *
 * Requires PHP: 5.2
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.2
 *
 * Text Domain: woocommerce-gold-silver-price
 * Domain Path: /languages/
 *
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('plugins_loaded', 'woocommerce_gold_price', 20);

function woocommerce_gold_price()
{

	if (!class_exists('woocommerce')) {  // Exit if WooCommerce isn't available
		return false;
	}

	// Display a Settings link on the main WP Plugins page
	add_filter('plugin_action_links', 'woocommerce_gold_price_action_links', 10, 2);

	// our admin functions
	$role = get_role('shop_manager');
	$role->add_cap('manage_options');
	add_action('admin_init', 'woocommerce_gold_price_admin_init');
	add_action('admin_menu', 'woocommerce_gold_price_admin_menu', 10);


	// i18n
	load_plugin_textdomain('woocommerce-gold-price', false, '/woocommerce-gold-price/languages');


	function woocommerce_gold_price_weight_description()
	{

		$weight_unit             = get_option('woocommerce_weight_unit');
		$weight_unit_description = array(
			'kg'  => __('kg', 'woocommerce-gold-price'),
			'g'   => __('g', 'woocommerce-gold-price'),
			'lbs' => __('lbs', 'woocommerce-gold-price'),
			'oz'  => __('oz', 'woocommerce-gold-price'),
		);

		return $weight_unit_description[$weight_unit];
	}


	function woocommerce_gold_price_admin_init()
	{

		// register_setting( $option_group, $option_name, $sanitize_callback );

		register_setting(
			'woocommerce_gold_price_options',
			'woocommerce_gold_price_options',
			'woocommerce_gold_price_validate_options'
		);

		// add_settings_section( $id, $title, $callback, $page );

		add_settings_section(
			'woocommerce_gold_price_plugin_options_section',
			__('Gold/Silver/Platinum Price Values', 'woocommerce-gold-price'),
			'woocommerce_gold_price_fields',
			'woocommerce_gold_price'
		);

		// add_settings_field( $id, $title, $callback, $page, $section, $args );

		add_settings_field(
			'woocommerce_gold_price_options',
			__('Gold/Silver/Platinum Price Values', 'woocommerce-gold-price'),
			'woocommerce_gold_price_fields',
			'woocommerce_gold_price_plugin_options_section',
			'woocommerce_gold_price'
		);

		add_action('woocommerce_product_options_pricing',     'woocommerce_gold_price_product_settings');
		add_action('woocommerce_process_product_meta_simple', 'woocommerce_gold_price_process_simple_settings');
	}


	function woocommerce_gold_price_admin_menu()
	{

		if (current_user_can('manage_woocommerce')) {

			/*
				//  add_submenu_page( $parent_slug,
						$page_title,
						$menu_title,
						$capability,
						$menu_slug,
						$function
			*/

			add_submenu_page(
				'woocommerce',
				__('Gold Prices and Gold Products', 'woocommerce-gold-price'),
				__('Gold Prices', 'woocommerce-gold-price'),
				'manage_woocommerce',
				'woocommerce_gold_price',
				'woocommerce_gold_price_page'
			);
		}
	}


	function woocommerce_gold_price_page()
	{

		do_action('admin_enqueue_scripts');

		$tab = 'config';

		if (isset($_GET['page']) && $_GET['page'] == 'woocommerce_gold_price') {

			if (isset($_GET['tab'])) {

				if (in_array($_GET['tab'], array(

					'config',
					'log',

				))) {

					$tab = esc_attr($_GET['tab']);
				}
			}
		}

?>

		<div class="wrap woocommerce">
			<div id="icon-woocommerce" class="icon32 icon32-woocommerce-settings"></div>
			<h2 class="nav-tab-wrapper">

				<a href="<?php echo admin_url('admin.php?page=woocommerce_gold_price&tab=config'); ?>" class="nav-tab <?php if ($tab == 'config') echo 'nav-tab-active'; ?>">
					<?php esc_html_e('Settings', 'woocommerce-gold-price'); ?></a>

				<a href="<?php echo admin_url('admin.php?page=woocommerce_gold_price&tab=log'); ?>" class="nav-tab <?php if ($tab == 'log') echo 'nav-tab-active'; ?>"><?php esc_html_e('Log', 'woocommerce-gold-price'); ?></a>

			</h2>
			<?php

			switch ($tab) {

				case 'config':
					woocommerce_gold_price_display_config_tab();
					break;

				case 'log':
					woocommerce_gold_price_display_log_tab();
					break;
			}

			?>
		</div>
		<?php
	}


	function woocommerce_gold_price_display_config_tab()
	{

		if (!isset($_REQUEST['settings-updated'])) {

			$_REQUEST['settings-updated'] = false;
		}

		if (false !== $_REQUEST['settings-updated']) {

		?>
			<div id="message" class="updated notice">
				<p><strong><?php esc_html_e('Your settings have been saved.', 'woocommerce-gold-price') ?></strong></p>
			</div>
		<?php

		}
		?>

		<h1><?php esc_html_e('Gold/Silver/Platinum Price Settings', 'woocommerce-gold-price') ?></h1>

		<form method="post" action="options.php">
			<?php
			settings_fields('woocommerce_gold_price_options');
			do_settings_sections('woocommerce_gold_price');
			?>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php esc_html_e('Save changes', 'woocommerce-gold-price') ?>" />
			</p>
		</form>
		<hr />

		<h1><?php esc_html_e('Gold/Silver/Platinum priced products', 'woocommerce-gold-price') ?></h1>

		<?php

		$karats = get_option('woocommerce_gold_price_options');

		if (!$karats) {

			$karats = array('22k Gold' => 0, '18k Gold' => 0, 'Silver' => 0,  'Platinum' => 0);
		}

		foreach ($karats as $key => $value) {

			$value     = floatval(str_replace(',', '', $value));

			$the_query = new WP_Query(array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_key'       => 'is_gold_price_product',
				'meta_value'     => 'yes',
				'meta_query'     => array(
					'key'     => 'gold_price_karats',
					'value'   =>  $key,
					'compare' => '=',
				),
			));

		?>

			<h2><?php echo $key ?></h2>

		<?php

			if (0 == $the_query->found_posts) {

				echo '<p>' . __('No products found.', 'woocommerce-gold-price') . '</p>';
			} else {

				echo '
					<ol>';

				// The Loop
				while ($the_query->have_posts()) {

					$the_query->the_post();

					$the_product = wc_get_product($the_query->post->ID);

					$edit_url    = admin_url('post.php?post=' . $the_product->get_id() . '&action=edit');
					$message     = '';

					$spread      = get_post_meta($the_product->get_id(), 'gold_price_product_spread', true);

					$fee         = get_post_meta($the_product->get_id(), 'gold_price_product_fee', true);
					$fee         = floatval(str_replace(',', '', $fee));
					$labourCharge         = get_post_meta($the_product->get_id(), 'gold_price_product_labourCharge', true);
					$labourCharge         = floatval(str_replace(',', '', $labourCharge));
					$makingCharge         = get_post_meta($the_product->get_id(), 'gold_price_product_makingCharge', true);
					$makingCharge         = floatval(str_replace(',', '', $makingCharge));
					$otherStoneCost         = get_post_meta($the_product->get_id(), 'gold_price_product_otherStoneCost', true);
					$otherStoneCost         = floatval(str_replace(',', '', $otherStoneCost));
					$diamondCost         = get_post_meta($the_product->get_id(), 'gold_price_product_diamondCost', true);
					$diamondCost         = floatval(str_replace(',', '', $diamondCost));
					$pearlCost         = get_post_meta($the_product->get_id(), 'gold_price_product_pearlCost', true);
					$pearlCost         = floatval(str_replace(',', '', $pearlCost));

					echo '
						<li><a href="' . $edit_url . '">' . get_the_title() . '</a>';

					if (!$the_product->has_weight()) {

						$message = __('Product has zero weight, can\'t calculate price based on weight.', 'woocommerce-gold-price');
					} else {

						if ($the_product->is_on_sale()) {

							$message = __('Product was on sale, can\'t calculate sale price.', 'woocommerce-gold-price');
						}

						$weight_price = $the_product->get_weight() * $value;
						$spread_price = $weight_price * (($spread / 100) + 1);
						// $gold_price   = $spread_price +  $fee + $labourCharge + $makingCharge + $otherStoneCost + $diamondCost + $pearlCost;
						$gold_price   = ((($value + (($labourCharge * $value) / 100)) + $makingCharge) * $the_product->get_weight()) + $diamondCost + $pearlCost + $otherStoneCost;

						$gst = (($gold_price * 3) / 100);
						$gold_price = $gold_price + $gst;

						// echo ': ' . $the_product->get_weight() . woocommerce_gold_price_weight_description()  . ' * ' .  wc_price(str_replace(',', '', $karats[$key]));


						// echo ' (' . wc_price($weight_price)  . ') ';

						// if ($spread) {

						// 	echo ' + ' . wc_price($spread_price - $weight_price) . ' (' . $spread . '%)';
						// }

						// if ($fee) {

						// 	echo ' + ' . wc_price($fee);
						// }
						// if ($labourCharge) {

						// 	echo ' + ' . wc_price($labourCharge);
						// }
						// if ($makingCharge) {

						// 	echo ' + ' . wc_price($makingCharge);
						// }
						// if ($otherStoneCost) {

						// 	echo ' + ' . wc_price($otherStoneCost);
						// }
						// if ($diamondCost) {

						// 	echo ' + ' . wc_price($diamondCost);
						// }
						// if ($pearlCost) {

						// 	echo ' + ' . wc_price($pearlCost);
						// }

						echo ':((' .  wc_price(str_replace(',', '', $karats[$key]));

						if ($labourCharge) {

							echo ' + (' . $labourCharge . '% Labour Charge) = ' . wc_price(($labourCharge * (str_replace(',', '', $karats[$key])) / 100));
						}
						if ($makingCharge) {

							echo ' + (Making Charge) = ' . wc_price($makingCharge);
						}

						echo ')*' . $the_product->get_weight() . woocommerce_gold_price_weight_description() . ')';

						if ($pearlCost) {

							echo ' + (Pearl Cost) = ' . wc_price($pearlCost);
						}


						if ($diamondCost) {

							echo ' + (Diamond Cost) = ' . wc_price($diamondCost);
						}

						if ($otherStoneCost) {

							echo ' + (Other Stone Cost) = ' . wc_price($otherStoneCost);
						}


						if ($fee) {

							echo ' + (Extra fees) = ' . wc_price($fee);
						}


						echo ' + (GST 3%) = ' . wc_price($gst);


						echo ' = <strong>' . wc_price($gold_price) . '</strong>';

						if (false === $_REQUEST['settings-updated']) {

							if (wc_price($gold_price) != wc_price($the_product->get_regular_price())) {

								$message .= ' | ' . sprintf(__('Warning! This product price (%s) is not based on Gold Price, press the "Save changes" button to update it.', 'woocommerce-gold-price'), wc_price($the_product->get_regular_price()));
							}
						} else {

							$the_product->set_price($gold_price);
							$the_product->set_regular_price($gold_price);
							$the_product->set_sale_price('');
							$the_product->set_date_on_sale_from('');
							$the_product->set_date_on_sale_to('');

							$the_product->save();

							update_post_meta($the_product->get_id(), '_price',         $gold_price);
							update_post_meta($the_product->get_id(), '_regular_price', $gold_price);
							update_post_meta($the_product->get_id(), '_sale_price', '');
							update_post_meta($the_product->get_id(), '_sale_price_dates_from', '');
							update_post_meta($the_product->get_id(), '_sale_price_dates_to', '');

							$log_message = sprintf(__('Updated price for %1$s', 'woocommerce-gold-price'), $the_product->get_title());

							woocommerce_gold_price_log($log_message);
						}
					}

					echo ' ' . $message . '</li>';
				}

				echo '
					</ol>';
			}

			// Restore original Query & Post Data
			wp_reset_query();
			wp_reset_postdata();
		}
	}


	function woocommerce_gold_price_display_log_tab()
	{

		$class = '';

		if (isset($_GET['clear_log'])	&& 1 == $_GET['clear_log']  && check_admin_referer('clear_log')) {

			woocommerce_gold_price_delete_log();
		}

		?>

		<div class="panel woocommerce_options_panel">

			<h3><?php _e('Logged events', 'woocommerce-gold-price'); ?> <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=woocommerce_gold_price&tab=log&clear_log=1'), 'clear_log'); ?>" class="button-primary right"> <?php _e('Clear Log', 'woocommerce-gold-price') ?> </a></h3>

			<table class="widefat">
				<thead>
					<tr>
						<th style="width: 150px"><?php _e('Timestamp', 'woocommerce-gold-price') ?></th>
						<th><?php _e('Event', 'woocommerce-gold-price') ?></th>
						<th><?php _e('User', 'woocommerce-gold-price') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php

					foreach (woocommerce_gold_price_get_log() as $event) {

						if (!$event[2]) {

							$display_name = '&#9889;';
						} else {

							$user_data = get_userdata($event[2]);
							$display_name = $user_data->display_name;
						}

					?>

						<tr <?php echo $class ?>>
							<td><?php echo woocommerce_gold_price_nice_time($event[0]); ?></td>
							<td><?php echo $event[1]; ?></td>
							<td><?php echo $display_name; ?></td>
						</tr>

					<?php

						if (empty($class)) {

							$class = ' class="alternate"';
						} else {

							$class = '';
						}
					}
					?>

				</tbody>
			</table>
		</div>

	<?php
	}


	function woocommerce_gold_price_better_human_time_diff($from, $to = '', $limit = 3)
	{

		// Since all months/years aren't the same, these values are what Google's calculator says
		$units = apply_filters('time_units', array(
			31556926 => array(__('%s year'),  __('%s years')),
			2629744  => array(__('%s month'), __('%s months')),
			604800   => array(__('%s week'),  __('%s weeks')),
			86400    => array(__('%s day'),   __('%s days')),
			3600     => array(__('%s hour'),  __('%s hours')),
			60       => array(__('%s min'),   __('%s mins')),
			1        => array(__('%s sec'),   __('%s secs')),
		));

		if (empty($to)) {
			$to = time();
		}

		$from = (int) $from;
		$to   = (int) $to;

		$t_diff = $to - $from;

		$diff = (int) abs($to - $from);

		$items = 0;
		$output = array();

		foreach ($units as $unitsec => $unitnames) {

			if ($items >= $limit) {
				break;
			}

			if ($diff < $unitsec) {
				continue;
			}

			$numthisunits = floor($diff / $unitsec);
			$diff         = $diff - ($numthisunits * $unitsec);
			$items++;

			if ($numthisunits > 0) {
				$output[] = sprintf(_n($unitnames[0], $unitnames[1], $numthisunits), $numthisunits);
			}
		}

		// translators: The separator for human_time_diff() which seperates the years, months, etc.
		$separator = _x(', ', 'human_time_diff');

		if (!empty($output)) {

			$human_time = implode($separator, $output);
		} else {

			$smallest   = array_pop($units);
			$human_time = sprintf($smallest[0], 1);
		}

		if ($t_diff < 0) {

			return sprintf(__('in %s'), $human_time);
		} else {

			return '<strong>' . sprintf(__('is %s late'), $human_time) . '</strong>';
		}
	}


	function woocommerce_gold_price_fields()
	{

		$karats          = get_option('woocommerce_gold_price_options');
		$currency_pos    = get_option('woocommerce_currency_pos');
		$currency_symbol = get_woocommerce_currency_symbol();

	?>

		<table class="form-table widefat">
			<thead>
				<tr valign="top">
					<th scope="col" style="padding-left: 1em;"><?php esc_html_e('Metal and Purity', 'woocommerce-gold-price') ?></th>
					<th scope="col"><?php esc_html_e('Price', 'woocommerce-gold-price') ?></td>
					<th scope="col"><?php esc_html_e('Weight Unit', 'woocommerce-gold-price') ?></td>
				</tr>
			</thead>

			<tr valign="top">
				<th scope="row" style="padding-left: 1em;"><label for="woocommerce_gold_price_options_24">22k Gold</label></th>
				<td>

					<?php

					$input = ' <input style="vertical-align: baseline; text-align: right;" id="woocommerce_gold_price_options_24" name="woocommerce_gold_price_options[22k Gold]" size="10" type="text" value="' . $karats['22k Gold'] . '" /> ';

					switch ($currency_pos) {
						case 'left':
							echo $currency_symbol . $input;
							break;
						case 'right':
							echo $input . $currency_symbol;
							break;
						case 'left_space':
							echo $currency_symbol . '&nbsp;' . $input;
							break;
						case 'right_space':
							echo $input . '&nbsp;' . $currency_symbol;
							break;
					}

					?>
				</td>
				<td> / <?php echo woocommerce_gold_price_weight_description(); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row" style="padding-left: 1em;"><label for="woocommerce_gold_price_options_18">18k Gold</label></th>
				<td>
					<?php

					$input = '<input style="vertical-align: baseline; text-align: right;" id="woocommerce_gold_price_options_18" name="woocommerce_gold_price_options[18k Gold]" size="10" type="text" value="' . $karats['18k Gold'] . '" />';

					switch ($currency_pos) {
						case 'left':
							echo $currency_symbol . $input;
							break;
						case 'right':
							echo $input . $currency_symbol;
							break;
						case 'left_space':
							echo $currency_symbol . '&nbsp;' . $input;
							break;
						case 'right_space':
							echo $input . '&nbsp;' . $currency_symbol;
							break;
					}
					?>
				</td>
				<td> / <?php echo woocommerce_gold_price_weight_description(); ?></td>
			</tr>
			<tr valign="top" class="alternate">
				<th scope="row" style="padding-left: 1em;"><label for="woocommerce_gold_price_options_22">Silver</label></th>
				<td>

					<?php

					$input = '<input style="vertical-align: baseline; text-align: right;" id="woocommerce_gold_price_options_22" name="woocommerce_gold_price_options[Silver]" size="10" type="text" value="' . $karats['Silver'] . '" />';

					switch ($currency_pos) {
						case 'left':
							echo $currency_symbol . $input;
							break;
						case 'right':
							echo $input . $currency_symbol;
							break;
						case 'left_space':
							echo $currency_symbol . '&nbsp;' . $input;
							break;
						case 'right_space':
							echo $input . '&nbsp;' . $currency_symbol;
							break;
					}

					?>
				</td>
				<td> / <?php echo woocommerce_gold_price_weight_description(); ?></td>
			</tr>

			<tr valign="top" class="alternate">
				<th scope="row" style="padding-left: 1em;"><label for="woocommerce_gold_price_options_14">Platinum</label></th>
				<td>
					<?php

					$input = '<input style="vertical-align: baseline; text-align: right;" id="woocommerce_gold_price_options_14" name="woocommerce_gold_price_options[Platinum]" size="10" type="text" value="' . $karats['Platinum'] . '" />';

					switch ($currency_pos) {
						case 'left':
							echo $currency_symbol . $input;
							break;
						case 'right':
							echo $input . $currency_symbol;
							break;
						case 'left_space':
							echo $currency_symbol . '&nbsp;' . $input;
							break;
						case 'right_space':
							echo $input . '&nbsp;' . $currency_symbol;
							break;
					}
					?>
				</td>
				<td> / <?php echo woocommerce_gold_price_weight_description(); ?></td>
			</tr>
		</table>
	<?php
	}


	function woocommerce_gold_price_validate_options($input)
	{
		foreach ($input as $key => $value) {
			$input[$key] =  wp_filter_nohtml_kses($value);
		}
		return $input;
	}


	function woocommerce_gold_price_product_settings()
	{

		global $thepostid;

		$is_gold_price_product = get_post_meta($thepostid, 'is_gold_price_product', true);

		$karats  = get_post_meta($thepostid, 'gold_price_karats', true);
		$spread  = get_post_meta($thepostid, 'gold_price_product_spread', true);
		$fee     = get_post_meta($thepostid, 'gold_price_product_fee', true);
		$labourCharge     = get_post_meta($thepostid, 'gold_price_product_labourCharge', true);
		$makingCharge     = get_post_meta($thepostid, 'gold_price_product_makingCharge', true);
		$otherStoneCost     = get_post_meta($thepostid, 'gold_price_product_otherStoneCost', true);
		$diamondCost     = get_post_meta($thepostid, 'gold_price_product_diamondCost', true);
		$pearlCost     = get_post_meta($thepostid, 'gold_price_product_pearlCost', true);

		// easy access to weight
		$product        = wc_get_product($thepostid);
		$product_weight = $product->get_weight();

	?>
		</div>
		<div class="options_group gold_price show_if_simple show_if_external hidden">
			<p class="form-field">
				<label for="is_gold_price_product"><?php esc_html_e('Gold/Silver/Platinum product', 'woocommerce-gold-price') ?></label>
				<input type="checkbox" class="checkbox" id="is_gold_price_product" name="is_gold_price_product" <?php checked($is_gold_price_product, 'yes'); ?> />
			</p>

			<p class="form-field">
				<label for="karats">
					<?php esc_html_e('Metal and Purity', 'woocommerce-gold-price') ?></label>
				<select name="karats" id='karats' style="float: none;">
					<option value="22k Gold" <?php selected('22k Gold', $karats); ?>><?php esc_html_e('22k Gold', 'woocommerce-gold-price') ?></option>
					<option value="18k Gold" <?php selected('18k Gold', $karats); ?>><?php esc_html_e('18k Gold', 'woocommerce-gold-price') ?></option>
					<option value="Silver" <?php selected('Silver', $karats); ?>><?php esc_html_e('Silver', 'woocommerce-gold-price') ?></option>
					<option value="Platinum" <?php selected('Platinum', $karats); ?>><?php esc_html_e('Platinum', 'woocommerce-gold-price') ?></option>
				</select>

			</p>

			<p class="form-field">
				<label for="product_weight"><?php esc_html_e('Product weight', 'woocommerce-gold-price');
											echo ' (' . get_option('woocommerce_weight_unit') . ')' ?></label>
				<input type="text" class="short" id="product_weight" name="product_weight" value="<?php echo $product_weight; ?>" />
			</p>

			<p class="form-field">
				<label for="spread"><?php esc_html_e('Spread (%)', 'woocommerce-gold-price') ?></label>
				<input type="text" class="short" id="spread" name="spread" value="<?php echo $spread; ?>" />
			</p>

			<p class="form-field">
				<label for="fee"><?php esc_html_e('Extra fee', 'woocommerce-gold-price');
									echo ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
				<input type="text" class="short" id="fee" name="fee" value="<?php echo $fee; ?>" />
			</p>
			<p class="form-field">
				<label for="labourCharge"><?php esc_html_e('Labour Charge', 'woocommerce-gold-price');
											echo ' (%)'; ?></label>
				<input type="text" class="short" id="labourCharge" name="labourCharge" value="<?php echo $labourCharge; ?>" />
			</p>
			<p class="form-field">
				<label for="makingCharge"><?php esc_html_e('Making Charge', 'woocommerce-gold-price');
											echo ' (' . get_woocommerce_currency_symbol() . 'per gram)'; ?></label>
				<input type="text" class="short" id="makingCharge" name="makingCharge" value="<?php echo $makingCharge; ?>" />
			</p>
			<p class="form-field">
				<label for="otherStoneCost"><?php esc_html_e('Other StoneCost', 'woocommerce-gold-price');
											echo ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
				<input type="text" class="short" id="otherStoneCost" name="otherStoneCost" value="<?php echo $otherStoneCost; ?>" />
			</p>
			<p class="form-field">
				<label for="diamondCost"><?php esc_html_e('Diamond Cost', 'woocommerce-gold-price');
											echo ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
				<input type="text" class="short" id="diamondCost" name="diamondCost" value="<?php echo $diamondCost; ?>" />
			</p>
			<p class="form-field">
				<label for="pearlCost"><?php esc_html_e('Pearl Cost', 'woocommerce-gold-price');
										echo ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
				<input type="text" class="short" id="pearlCost" name="pearlCost" value="<?php echo $pearlCost; ?>" />
			</p>
	<?php
	}


	function woocommerce_gold_price_process_simple_settings($post_id)
	{

		$message      = '';
		$gold_product = get_post_meta($post_id, 'is_gold_price_product', true);

		// is gold product ?
		$is_gold_price_product = isset($_POST['is_gold_price_product']) ? 'yes' : 'no';
		$changed_gold_status   = update_post_meta($post_id, 'is_gold_price_product', $is_gold_price_product);

		update_post_meta($post_id, 'gold_price_karats', wc_clean($_POST['karats']));

		// spread % and fee
		update_post_meta($post_id, 'gold_price_product_spread', wc_clean($_POST['spread']));
		update_post_meta($post_id, 'gold_price_product_fee',    wc_clean($_POST['fee']));
		update_post_meta($post_id, 'gold_price_product_labourCharge',    wc_clean($_POST['labourCharge']));
		update_post_meta($post_id, 'gold_price_product_makingCharge',    wc_clean($_POST['makingCharge']));
		update_post_meta($post_id, 'gold_price_product_otherStoneCost',    wc_clean($_POST['otherStoneCost']));
		update_post_meta($post_id, 'gold_price_product_diamondCost',    wc_clean($_POST['diamondCost']));
		update_post_meta($post_id, 'gold_price_product_pearlCost',    wc_clean($_POST['pearlCost']));

		// easy access to weight
		$product = wc_get_product($post_id);
		$product->set_weight(wc_clean($_POST['product_weight']));
		$product->save();

		if ('no' == $gold_product) {

			if ($changed_gold_status) {

				$message = sprintf(__('Checked <strong>%1$s</strong> | Purity: %2$s | Spread: %3$s%% | Fee: %4$s%% | labourCharge: %5$s', 'woocommerce-gold-price'), $product->get_title(), $_POST['karats'], $_POST['spread'], wc_price($_POST['fee']), wc_price($_POST['labourCharge']));
			}
		} else {

			if ($changed_gold_status) {

				$message = sprintf(__('Unchecked <strong>%1$s</strong>, no longer a gold product.', 'woocommerce-gold-price'), $product->get_title());
			} else {

				$message = sprintf(__('Updated <strong>%1$s</strong> | Purity: %2$s | Spread: %3$s%% | Fee: %4$s%% | labourCharge: %5$s', 'woocommerce-gold-price'), $product->get_title(), $_POST['karats'], $_POST['spread'], wc_price($_POST['fee']), wc_price($_POST['labourCharge']));
			}
		}

		if ($message) {
			woocommerce_gold_price_log($message);
		}
	}


	// Display a Settings link on the main Plugins page for easy access
	function woocommerce_gold_price_action_links($links, $file)
	{

		if (plugin_basename(__FILE__) == $file) {

			$woocommerce_gold_price_settings_link = '<a href="' . get_admin_url() . 'admin.php?page=woocommerce_gold_price&tab=config">' . __('Settings', 'woocommerce-gold-price') . '</a>';

			// make the 'Settings' link appear first
			array_unshift($links, $woocommerce_gold_price_settings_link);
		}

		return $links;
	}


	// logging functionality //

	function woocommerce_gold_price_nice_time($time, $args = false)
	{

		$defaults = array('format' => 'date_and_time');
		extract(wp_parse_args($args, $defaults), EXTR_SKIP);

		if (!$time)
			return false;

		if ($format == 'date')
			return date(get_option('date_format'), $time);

		if ($format == 'time')
			return date(get_option('time_format'), $time);

		if ($format == 'date_and_time') //get_option( 'time_format' )
			return date(get_option('date_format'), $time) . " " . date('H:i:s', $time);

		return false;
	}


	function woocommerce_gold_price_log($event)
	{

		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;

		$log = get_option('woocommerce_gold_price_log');

		$time_difference = get_option('gmt_offset') * 3600;
		$time            = time() + $time_difference;

		if (!is_array($log)) {
			$log = array();
			array_push($log, array($time, __('Log Started.', 'woocommerce-gold-price'), $current_user_id));
		}

		array_push($log, array($time, $event, $current_user_id));
		return update_option('woocommerce_gold_price_log', $log);
	}


	function woocommerce_gold_price_get_log()
	{

		$log = get_option('woocommerce_gold_price_log');

		// If no log created yet, create one
		if (!is_array($log)) {
			$current_user    = wp_get_current_user();
			$current_user_id = $current_user->ID;
			$log             = array();
			$time_difference = get_option('gmt_offset') * 3600;
			$time            = time() + $time_difference;
			array_push($log, array($time, __('Log Started.', 'woocommerce-gold-price'), $current_user_id));
			update_option('woocommerce_gold_price_log', $log);
		}

		return array_reverse(get_option('woocommerce_gold_price_log'));
	}


	function woocommerce_gold_price_delete_log()
	{

		$current_user    = wp_get_current_user();
		$current_user_id = $current_user->ID;
		$log             = array();
		$time_difference = get_option('gmt_offset') * 3600;
		$time            = time() + $time_difference;

		array_push($log, array($time, __('Log cleared.', 'woocommerce-gold-price'), $current_user_id));

		update_option('woocommerce_gold_price_log', $log);
	}
}
