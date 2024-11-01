<?php
/*
 Plugin Name: Website Rank Tracker
 Plugin URI: http://www.WebsiteRankTracker.com
 Description: Tracks keywords and your website's rank for each in Google
 Author: WebsiteRankTracker.com
 Author URI: http://www.WebsiteRankTracker.com
 Tags: page rank, website rank, google, google rank, google position
 Version: 1.2
 */

/**

Copyright 2010  Summit Media Concepts LLC (email : info@SummitMediaConcepts.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

function WRT_init()
{
	if (strlen(get_option('WebsiteRankTracker_api_key')) != 32)
	{
		echo "<div id='websiteranktracker-warning' class='updated fade'><p><strong>".__('WebsiteRankTracker is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your API Key</a> for it to work.'), "admin.php?page=websiteranktracker")."</p></div>";
	}
}

function WRT_addAdminMenu()
{
	add_action('admin_init', 'WRT_registerSettings');

	if (function_exists('add_menu_page'))
	{
		add_menu_page('Rank Tracker', 'Rank Tracker', 'administrator', 'websiteranktracker', 'WRT_displayKeywordSummary');
		add_submenu_page('websiteranktracker', 'Website Rank Tracker - Settings', 'Settings', 'administrator', 'websiteranktracker_settings', 'WRT_displaySettingsPage');
		add_submenu_page('websiteranktracker', 'Website Rank Tracker - Clear Cache', 'Clear Cache', 'administrator', 'websiteranktracker_clear_cache', 'WRT_displayClearCachePage');
	}
}

function WRT_registerSettings()
{
	register_setting('wrt-settings-group', 'WebsiteRankTracker_api_key');
	register_setting('wrt-settings-group', 'WebsiteRankTracker_show_dashboard_widget');
}

function WRT_displaySettingsPage()
{
	?>
<div class="wrap">
<h2>WebsiteRankTracker Settings</h2>
<form method="post" action="options.php"><?php settings_fields('wrt-settings-group'); ?>
<table class="form-table">
	<tr valign="top">
		<th scope="row">API Key</th>
		<td>
			<input type="text" name="WebsiteRankTracker_api_key" value="<?php echo get_option('WebsiteRankTracker_api_key'); ?>" size="50" maxlength="32" />
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">Dashboard Widget</th>
		<td>
			<select name="WebsiteRankTracker_show_dashboard_widget">
				<option value="0"<?php if (get_option('WebsiteRankTracker_show_dashboard_widget', 1) == 0){ echo ' selected'; } ?>>Do not show</option>
				<option value="1"<?php if (get_option('WebsiteRankTracker_show_dashboard_widget', 1) == 1){ echo ' selected'; } ?>>Show all</option>
				<option value="2"<?php if (get_option('WebsiteRankTracker_show_dashboard_widget', 1) == 2){ echo ' selected'; } ?>>Show only ranked keywords</option>
			</select>
		</td>
	</tr>
</table>
<input type="hidden" name="action" value="update" /> <input type="hidden" name="page_options" value="WebsiteRankTracker_intext_class,WebsiteRankTracker_nofollow" />
<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
</form>
<p>
<strong>Instructions</strong>
<ol>
	<li>Sign-up at <a href="http://www.WebsiteRankTracker.com/" target="_blank">http://www.WebsiteRankTracker.com</a> for your free API Key</li>
	<li>Log-in to <a href="http://www.WebsiteRankTracker.com/members/" target="_blank">http://www.WebsiteRankTracker.com/members/</a> after your password is emailed to you</li>
	<li>Use the Keyword Research tool to pick the keywords you would like to track</li>
	<li>Log-in to your Wordpress admin area</li>
	<li>Put your API Key in the field above and click "Save Changes"</li>
	<li>Within 24 hours you will see stats appearing in your Wordpress dashboard</li>
</ol>
</p>
</div>
	<?php
}

function WRT_displayClearCachePage()
{
	update_option('WebsiteRankTracker_summary_cache', array('last_updated' => 0));
	echo '<h3>Website Rank Tracker cache has been cleared</h3>';
}

function WRT_displayDashboardWidget()
{
	WRT_displayKeywordSummary(get_option('WebsiteRankTracker_show_dashboard_widget', 1));
}

function WRT_displayKeywordSummary($show = 1)
{
	if ($show == NULL)
	{
		$show = 1;
	}
	
	$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));

	$result = get_option('WebsiteRankTracker_summary_cache');

	if (!is_array($result) || intval($result['last_updated']) < intval($today))
	{
		$url = 'http://www.websiteranktracker.com/api.php?method=getPositionData&v=1.1&key='.get_option('WebsiteRankTracker_api_key');
		$result = WRT_callApi($url);

		if (intval($result['last_updated']))
		{
			update_option('WebsiteRankTracker_summary_cache', $result);
		}
	}

	$row = 0;

	if (get_option('WebsiteRankTracker_api_key'))
	{
		echo '<div style="clear: both;">';
		echo '<a href="http://www.websiteranktracker.com/members" target="_blank" class="wrt_config_link"><img src="images/generic.png"> Detailed reports &amp; configuration</a>';
		echo "<table cellspacing=\"1\" class=\"wrt_dashboard_table\">\n";
		echo "<tr class=\"header\">\n";
		echo "	<th>Keyword</th>\n";
		echo "	<th>Rank</th>\n";
		echo "	<th>Page</th>\n";
		echo "	<th>Chg</th>\n";
		echo "</tr>\n";

		if (is_array($result['data']) && count($result['data']))
		{
			foreach ($result['data'] as $keyword => $rank_info)
			{
				if ($show == 1 || ($show == 2 && intval($rank_info['rank'])))
				{
					$row++;

					$class = '';
					if (($row + 1) % 2)
					{
						$class = ' class="alt_row"';
					}

					$page = '-';
					$change = '-';

					if ($rank_info['rank'] != 'NR')
					{
						if ($rank_info['rank'] > $rank_info['last_rank'])
						{
							$change = '+'.($rank_info['rank'] - $rank_info['last_rank']);
						}
						elseif ($rank_info['rank'] < $rank_info['last_rank'])
						{
							$change = '-'.($rank_info['last_rank'] - $rank_info['rank']);
						}

						$page = ceil($rank_info['rank'] / 10);
					}

					echo "<tr$class>\n";
					echo "	<th>$keyword</th>\n";
					echo "	<td>".$rank_info['rank']."</td>\n";
					echo "	<td>".$page."</td>\n";
					echo "	<td>".$change."</td>\n";
					echo "</tr>\n";
				}
			}
				
			if (!$row)
			{
				echo "<tr>\n";
				echo "	<td colspan=\"4\"><center><br />No keywords are ranked at this time<br /><br /></center></td>\n";
				echo "</tr>\n";
			}
		}
		else
		{
			echo "<tr>\n";
			echo "	<td colspan=\"4\"><center><br />No keywords have been chosen<br /><a href=\"http://www.websiteranktracker.com/members\" target=\"_blank\">Click here to get started...</a><br /><br /></center></td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo '</div>';
	}
	else
	{
		echo "<center><a href=\"admin.php?page=websiteranktracker\" target=\"_blank\">Click here to get started...</a></center>\n";
	}
}

function WRT_addDashboardWidget()
{
	wp_add_dashboard_widget('WebsiteRankTracker_dashboard_widget', 'Website Rank Tracker', 'WRT_displayDashboardWidget');
}

function WRT_registerAdminHead()
{
	$siteurl = get_option('siteurl');
	$url = $siteurl . '/wp-content/plugins/'.basename(dirname(__FILE__)).'/style.css';
	echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
}

function WRT_callApi($url)
{
	$url_info = parse_url($url);

	$senddata = '';

	// open in secure socket layer or not
	if ($url_info['scheme'] == 'https')
	{
		$fp = fsockopen('ssl://'.$url_info['host'], 443, $errno, $errstr, 60);
	}
	else
	{
		$fp = fsockopen($url_info['host'], 80, $errno, $errstr, 60);
	}
	
	// make sure opened ok
	if (!$fp)
	{
		return FALSE;
	}

	$senddata = '';

	// HTTP POST headers
	$out = 'POST '.(isset($url_info['path'])?$url_info['path']:'/').(isset($url_info['query'])?'?'.$url_info['query']:'').' HTTP/1.0'."\r\n";
	$out .= 'Host: '.$url_info['host']."\r\n";
	$out .= 'User-Agent: '. $useragent."\r\n";
	$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$out .= 'Content-Length: '.strlen( $senddata )."\r\n";
	$out .= 'Connection: Close'."\r\n\r\n";
	$out .= $senddata;

	fwrite($fp, $out);

	$contents = '';
	
	$t_error = error_reporting(0);
	while (!feof($fp))
	{
		$contents .= fgets($fp, 1024);
	}
	error_reporting($t_error);

	list($headers, $content) = explode("\r\n\r\n", $contents, 2);

	return json_decode(trim($content), TRUE);
}

add_action('admin_head', 'WRT_registerAdminHead');

if (intval(get_option('WebsiteRankTracker_show_dashboard_widget', 1)))
{
	add_action('wp_dashboard_setup', 'WRT_addDashboardWidget');
}

add_action('admin_menu', 'WRT_addAdminMenu');

add_action('admin_notices', 'WRT_init');