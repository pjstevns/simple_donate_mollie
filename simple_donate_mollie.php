<?php
/*
Plugin Name: Simple Donate - mollie
Plugin URI: http://github.com/pjstevns/simple_donate_mollie
Description: A real simple donation widget for iDeal
Version: 0.1
Author: Paul J Stevens
Author URI: http://github.com/pjstevns/
License: GPLv3
*/

require_once 'Payment.php';

class Simple_Donate_Mollie extends WP_Widget {
	function __construct() 
	{
		parent::__construct(
			'simple_donate_mollie',
			'Simple_Donate_Mollie',
			array('description' => __('A Simple Donation Widget', 'simple_donate'), )
		);
	}

	private function handle_return($args, $instance)
	{
		extract($args);
		$iDeal = new Mollie_iDEAL_Payment($instance['partner_id']);
		$iDeal->checkPayment($_GET['transaction_id']);
		if ($iDeal->getPaidStatus())
			echo "<p>" . $instance['thanks'] . "</p>";
		else
			echo "<p>" . $instance['sorry'] . "</p>";


	}
	public function widget($args, $instance)
	{
		extract($args);

		if (!isset($instance['partner_id']) or empty($instance['partner_id'])) {
			return;
		}

		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if (! empty($title))
			echo $before_title . $title . $after_title;

		if ($_GET['transaction_id']) {
			$this->handle_return($args, $instance);
			echo $after_widget;
			return;
		}

		$iDeal = new Mollie_iDEAL_Payment($instance['partner_id']);
		if ($instance['debug'])
			$iDeal->setTestMode();

		if (isset($_POST['bank']) and ! empty($_POST['bank'])) {
			$amount = (int)((float)($_POST['amount']) * 100);
			if ($iDeal->createPayment(
				$_POST['bank'],
				$amount,
				$instance['description'],
				$_SERVER['HTTP_REFERER'],
				$instance['report_url']))
			{
				wp_redirect($iDeal->getBankURL());
				exit;
			} else {
				echo "<p>Betaling kon niet worden aangemaakt.</p>";
				echo "<p>" . htmlspecialchars($iDeal->getErrorMessage()) . "</p>";
				exit;
			}

		} else if (isset($_POST['amount'])) {
			$banks = $iDeal->getBanks();
			if ($banks == false) {
				echo "<p>Er is een fout opgetreden</p>";
				echo $after_widget;
				return;
			}
?>
		<form method="post">
		<input type="hidden" name="amount" value="<?php echo $_POST['amount']; ?>"/>
		<select name="bank">
		<option value="">Kies uw bank</option>
<?php
			foreach($banks as $id=>$name) {
?>
				<option value="<?php echo htmlspecialchars($id) ?>"><?php echo htmlspecialchars($name) ?></option>
<?
			}
?>
		</select>
		<input type="submit" value="Betalen"?>
		</form>
<?
		} else {
?>
		<form method="post">
		<label for="amount">&euro;</label>
		<input class="widefat" id="simple_donate_amount" name="amount"
			type="text" value="10.00" />
		<input type="submit" value="<? echo __('Doneer!', 'simple_donate'); ?>"/>
		</form>
<?
		}
		
		echo $after_widget;
	}

	public function form($instance)
	{
		if (isset($instance['title'])) {
			$title = $instance['title'];
		} else {
			$title = __('New title', 'simple_donate');
		}
		if (isset($instance['partner_id'])) {
			$partner_id = $instance['partner_id'];
		} else {
			$partner_id = __('Your Mollie partner ID', 'simple_donate');
		}
		if (isset($instance['description'])) {
			$description = $instance['description'];
		} else {
			$description = __('Description transaction', 'simple_donate');
		}
		if (isset($instance['report_url'])) {
			$report_url = $instance['report_url'];
		} else {
			$report_url = __('Report URL', 'simple_donate');
		}
		if (isset($instance['thanks'])) {
			$thanks = $instance['thanks'];
		} else {
			$thanks = __('"Thank you" message', 'simple_donate');
		}
		if (isset($instance['sorry'])) {
			$sorry = $instance['sorry'];
		} else {
			$sorry = __('"Sorry" message', 'simple_donate');
		}
		if (isset($instance['debug'])) {
			$debug = $instance['debug'];
		} else {
			$debug = true;
		}


?>
	<p>
	<label for="<?php echo $this->get_field_name('title'); ?>"><?php _e('Title:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title'); ?>"
		type="text" value="<?php echo esc_attr($title); ?>" />
	</p>

	<p>
	<label for="<?php echo $this->get_field_name('partner_id'); ?>"><?php _e('Partner ID:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('partner_id');?>" name="<?php echo $this->get_field_name('partner_id'); ?>"
		type="text" value="<?php echo esc_attr($partner_id); ?>" />
	</p>

	<p>
	<label for="<?php echo $this->get_field_name('description'); ?>"><?php _e('Description transaction:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('description');?>" name="<?php echo $this->get_field_name('description'); ?>"
		type="text" value="<?php echo esc_attr($description); ?>" />
	</p>

	<p>
	<label for="<?php echo $this->get_field_name('report_url'); ?>"><?php _e('Report URL:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('report_url');?>" name="<?php echo $this->get_field_name('report_url'); ?>"
		type="text" value="<?php echo esc_attr($report_url); ?>" />
	</p>

	<p>
	<label for="<?php echo $this->get_field_name('thanks'); ?>"><?php _e('"Thank you" message:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('thanks');?>" name="<?php echo $this->get_field_name('thanks'); ?>"
		type="text" value="<?php echo esc_attr($thanks); ?>" />
	</p>

	<p>
	<label for="<?php echo $this->get_field_name('sorry'); ?>"><?php _e('"Sorry" message:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('sorry');?>" name="<?php echo $this->get_field_name('sorry'); ?>"
		type="text" value="<?php echo esc_attr($sorry); ?>" />
	</p>

	<p>
	<label for="<?php echo $this->get_field_name('debug'); ?>"><?php _e('Test mode'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('debug');?>" name="<?php echo $this->get_field_name('debug'); ?>"
		type="checkbox" <?php echo $debug?"checked":"" ?> />
	</p>


<?
	}

	public function update($new_instance, $old_instance)
	{
		$instance = array();
		$instance['title'] = (! empty($new_instance['title'])) ? strip_tags($new_instance['title']): '';
		$instance['partner_id'] = (! empty($new_instance['partner_id'])) ? strip_tags($new_instance['partner_id']): '';
		$instance['report_url'] = (! empty($new_instance['report_url'])) ? strip_tags($new_instance['report_url']): '';
		$instance['description'] = (! empty($new_instance['description'])) ? strip_tags($new_instance['description']): '';
		$instance['thanks'] = (! empty($new_instance['thanks'])) ? strip_tags($new_instance['thanks']): '';
		$instance['sorry'] = (! empty($new_instance['sorry'])) ? strip_tags($new_instance['sorry']): '';
		$instance['debug'] = (! empty($new_instance['debug'])) ? true : false;
		return $instance;
	}
}

function add_ob_start() {
	ob_start();
}

add_action('init', 'add_ob_start');
add_action('widgets_init', function() {register_widget('Simple_Donate_Mollie'); } );


?>
