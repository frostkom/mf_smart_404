<?php

class mf_smart_404
{
	function __construct(){}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			// Delete old unused redirects
			$wpdb->query("DELETE FROM ".$wpdb->base_prefix."redirect WHERE (redirectStatus IN ('ignore', 'publish') AND redirectUsedDate < DATE_SUB(NOW(), INTERVAL 1 YEAR)) OR (redirectStatus IN ('draft', 'search') AND redirectUsedDate < DATE_SUB(NOW(), INTERVAL 1 MONTH))");
		}

		$obj_cron->end();
	}

	function init()
	{
		load_plugin_textdomain('lang_smart_404', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");
	}

	function settings_smart_404()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();
		$arr_settings['setting_also_search'] = __("Search", 'lang_smart_404');
		$arr_settings['setting_redirects'] = __("Redirects", 'lang_smart_404');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_smart_404_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Smart 404", 'lang_smart_404'));
	}

	function setting_also_search_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_post_types = get_post_types_for_select(array('include' => array('types'), 'add_is' => false));

		echo "<ul>";

			foreach($arr_post_types as $group_key => $group)
			{
				echo "<li>
					<input type='checkbox' name='setting_also_search[]' value='".$group_key."' ".(is_array($option) && in_array($group_key, $option) ? "checked" : "").">&nbsp;<span>".$group."</span>"
				."</li>";
			}

		echo "</ul>";
	}

	function setting_redirects_callback()
	{
		global $wpdb;

		//$setting_key = get_setting_key(__FUNCTION__);
		//$option = get_option($setting_key);

		$plugin_include_url = plugin_dir_url(__FILE__);
		mf_enqueue_style('style_smart_404_settings', $plugin_include_url."style_settings.css");
		mf_enqueue_script('script_smart_404_settings', $plugin_include_url."script_settings.js", array('ajax_url' => admin_url('admin-ajax.php')));

		$result = $wpdb->get_results($wpdb->prepare("SELECT redirectID, redirectStatus, redirectFrom, redirectTo, redirectCreated, redirectUsedDate, redirectUsedAmount FROM ".$wpdb->base_prefix."redirect WHERE blogID = '%d' AND redirectStatus != %s ORDER BY redirectUsedAmount DESC, redirectUsedDate DESC, redirectCreated DESC LIMIT 0, 50", $wpdb->blogid, 'ignore'));

		if($wpdb->num_rows > 0)
		{
			$site_url = get_site_url();

			echo "<table".apply_filters('get_table_attr', "", ['class' => ['smart_404_list']]).">";

				/*$arr_header[] = __("Status", 'lang_smart_404');
				$arr_header[] = __("From", 'lang_smart_404');
				$arr_header[] = __("To", 'lang_smart_404');
				$arr_header[] = "";

				echo show_table_header($arr_header);*/

				echo "<tbody>";

					foreach($result as $r)
					{
						$redirect_id = $r->redirectID;
						$redirect_status = $r->redirectStatus;
						$redirect_from = $r->redirectFrom;
						$redirect_to = $r->redirectTo;
						$redirect_created = $r->redirectCreated;
						$redirect_used_date = $r->redirectUsedDate;
						$redirect_used_amount = $r->redirectUsedAmount;

						echo "<tr id='redirect_".$redirect_id."'>
							<td>";

								switch($redirect_status)
								{
									case 'publish':
										echo "<i class='fa fa-check green'></i>";
									break;

									case 'draft':
									case 'search':
										echo "<i class='far fa-edit grey'></i>";
									break;

									default:
										echo "<i class='fa fa-question-circle grey' title='".$redirect_status."'></i>";
									break;
								}

							echo "</td>
							<td>";

								switch($redirect_status)
								{
									case 'search':
										echo "<span class='grey'>".__("Search", 'lang_smart_404').": </span>".$redirect_from;
									break;

									default:
										echo "<a href='".$site_url."/".$redirect_from."'><span class='grey'>".$site_url."/</span>".$redirect_from."</a>";
									break;
								}

							echo "</td>
							<td>";

								if($redirect_to != '')
								{
									echo "->";
								}

							echo "</td>
							<td>";

								if($redirect_to != '')
								{
									if(substr($redirect_to, 0, 4) != "http")
									{
										echo "<a href='".$site_url."/".$redirect_to."'><span class='grey'>".$site_url."/</span>".$redirect_to."</a>";
									}

									else
									{									
										echo "<a href='".$redirect_from."'>".$redirect_to."</a>";
									}
								}

							echo "</td>
							<td>";

								if($redirect_used_amount > 0)
								{
									echo format_date($redirect_used_date);

									if($redirect_used_amount > 1)
									{
										echo " (".$redirect_used_amount.")";
									}
								}

								else if($redirect_created > DEFAULT_DATE)
								{
									echo "<span class='grey'>".format_date($redirect_created)."</span>";
								}

							echo "</td>
							<td class='nowrap'>";

								switch($redirect_status)
								{
									case 'publish':
										echo "<i class='fa fa-wrench' data-from='".$redirect_from."' data-to='".$redirect_to."' title='".__("Edit", 'lang_smart_404')."'></i>";
									break;

									case 'draft':
										echo "<i class='fa fa-wrench' data-from='".$redirect_from."' title='".__("Add", 'lang_smart_404')."'></i>";
									break;

									case 'search':
										echo "<i class='fa fa-wrench' data-from='".sanitize_title_with_dashes(sanitize_title($redirect_from))."' title='".__("Add", 'lang_smart_404')."'></i>";
									break;
								}

								echo "<i class='fa fa-trash red' title='".__("Delete", 'lang_smart_404')."' rel='api_smart_404_remove_redirect'></i>";

								switch($redirect_status)
								{
									case 'draft':
									case 'search':
										echo "<i class='fa fa-eye-slash grey' title='".__("Ignore", 'lang_smart_404')."' rel='api_smart_404_ignore_redirect'></i>";
									break;
								}

							echo "</td>
						</tr>";
					}

				echo "</tbody>
			</table>";
		}

		//@list($redirect_from, $redirect_to) = explode(" ", $option);
		$redirect_from = $redirect_to = "";

		echo "<div".apply_filters('get_form_attr', "").">
			<div".apply_filters('get_flex_flow', "").">"
				.show_textfield(array('value' => $redirect_from, 'placeholder' => __("from-url", 'lang_smart_404')))
				.show_textfield(array('value' => $redirect_to, 'placeholder' => __("to-url", 'lang_smart_404')))
			."</div>"
			.show_button(array('type' => 'button', 'name' => 'btnRedirectSave', 'text' => __("Save", 'lang_smart_404'), 'class' => 'button-secondary'))
			."<p id='redirect_debug'></p>
		</div>";
	}

	function pre_get_posts($query)
	{
		if($query->is_main_query() && $query->is_search())
		{
			add_action('wp_footer', function() use ($query)
			{
				global $wpdb;

				if($query->found_posts === 0)
				{
					$search_term = get_search_query();

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."redirect SET blogID = '%d', redirectStatus = %s, redirectFrom = %s, redirectTo = %s, redirectCreated = NOW(), redirectUsedDate = NOW(), redirectUsedAmount = '1'", $wpdb->blogid, 'search', $search_term, ""));
				}
			});
		}
	}

	function api_smart_404_save_redirect()
	{
		global $wpdb, $done_text, $error_text;

		$result = array(
			'success' => false,
		);

		$redirect_from = trim(check_var('redirect_from'), "/");
		$redirect_to = trim(check_var('redirect_to'), "/");

		if($redirect_from != '' && $redirect_to != '')
		{
			$site_url = get_site_url();

			$redirect_from = str_replace($site_url, "", $redirect_from);
			$redirect_to = str_replace($site_url, "", $redirect_to);

			$intRedirectID = $wpdb->get_var($wpdb->prepare("SELECT redirectID FROM ".$wpdb->base_prefix."redirect WHERE blogID = '%d' AND redirectFrom = '".$redirect_from."'", $wpdb->blogid));

			if($intRedirectID > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."redirect SET redirectStatus = %s, redirectTo = '".$redirect_to."' WHERE redirectID = '%d'", 'publish', $intRedirectID));

				$done_text = __("I successfully updated the rule for you", 'lang_smart_404');
			}

			else
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."redirect SET blogID = '%d', redirectFrom = '".$redirect_from."', redirectTo = '".$redirect_to."', redirectCreated = NOW()", $wpdb->blogid));

				if($wpdb->rows_affected > 0)
				{
					$done_text = __("I successfully saved the rule for you", 'lang_smart_404');
				}

				else
				{
					$error_text = __("I could not save the rule for you. If the problem persists, contact an administrator.", 'lang_smart_404');
				}
			}
		}

		else
		{
			$error_text = __("You have to enter both from and to before saving a rule", 'lang_smart_404');
		}

		if($done_text != '')
		{
			$result['success'] = true;
		}

		$result['message'] = get_notification();

		header("Content-Type: application/json");
		echo json_encode($result);
		die();
	}

	function api_smart_404_remove_redirect()
	{
		global $wpdb, $done_text, $error_text;

		$result = array(
			'success' => false,
		);

		$redirect_id = check_var('redirect_id', 'int');

		if($redirect_id > 0)
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."redirect WHERE blogID = '%d' AND redirectID = '%d'", $wpdb->blogid, $redirect_id));

			if($wpdb->rows_affected == 1)
			{
				$done_text = __("I successfully deleted the rule for you", 'lang_smart_404');
			}

			else
			{
				$error_text = __("I could not remove the rule for you", 'lang_smart_404');
			}
		}

		else
		{
			$error_text = __("There was no rule to remove", 'lang_smart_404');
		}

		if($done_text != '')
		{
			$result['success'] = true;
		}

		$result['message'] = get_notification();

		header("Content-Type: application/json");
		echo json_encode($result);
		die();
	}

	function api_smart_404_ignore_redirect()
	{
		global $wpdb, $done_text, $error_text;

		$result = array(
			'success' => false,
		);

		$redirect_id = check_var('redirect_id', 'int');

		if($redirect_id > 0)
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."redirect SET redirectStatus = %s WHERE blogID = '%d' AND redirectID = '%d'", 'ignore', $wpdb->blogid, $redirect_id));

			if($wpdb->rows_affected == 1)
			{
				$done_text = __("I successfully ignored the rule for you", 'lang_smart_404');
			}

			else
			{
				$error_text = __("I could not ignore the rule for you", 'lang_smart_404');
			}
		}

		else
		{
			$error_text = __("There was no rule to ignore", 'lang_smart_404');
		}

		if($done_text != '')
		{
			$result['success'] = true;
		}

		$result['message'] = get_notification();

		header("Content-Type: application/json");
		echo json_encode($result);
		die();
	}

	function prepare_patterns($a)
	{
		$sep = (strpos($a, "@") === false ? "@" : "%");

		return $sep.trim($a).$sep."i";
	}

	function search($search, $type)
	{
		$search_words = trim(preg_replace("@[_-]@", " ", $search));
		$arr_posts = get_posts(array('s' => $search_words, 'post_type' => $type));

		if(count($arr_posts) > 1)
		{
			$arr_titles_matches = array();

			foreach($arr_posts as $arr_post)
			{
				if(strpos(strtolower($arr_post->post_title), strtolower($search_words)) !== false)
				{
					$arr_titles_matches[] = $arr_post;
				}
			}

			if(count($arr_titles_matches) == 1)
			{
				return $arr_titles_matches;
			}
		}

		return $arr_posts;
	}

	function template_redirect()
	{
		global $wpdb;

		if(is_404())
		{
			// Extract any GET parameters from URL
			$get_params = "";
			$request_uri = $_SERVER['REQUEST_URI'];
			$redirect_to = "";

			if(preg_match("@/?(\?.*)@", $request_uri, $matches))
			{
				$get_params = $matches[1];
			}

			// Extract search term from URL
			$patterns_array = array();
			$patterns_array[] = "/(trackback|feed|(comment-)?page-?[0-9]*)/?$";
			$patterns_array[] = "\.(html|php)$";
			$patterns_array[] = "/?\?.*";

			$patterns_array = array_map(array($this, 'prepare_patterns'), $patterns_array);

			$search = preg_replace($patterns_array, "", urldecode($request_uri));
			$search = basename(trim($search));
			$search = str_replace("_", "-", $search);
			$search = trim($search, "/");
			$search = trim(preg_replace($patterns_array, "", $search));

			if($search != '')
			{
				//$search_words = trim(preg_replace( "@[_-]@", " ", $search));
				//$GLOBALS["__smart404"]["search_words"] = explode(" ", $search_words);
				//$GLOBALS["__smart404"]["suggestions"] = array();

				if($redirect_to == "")
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT redirectID, redirectStatus, redirectTo FROM ".$wpdb->base_prefix."redirect WHERE blogID = '%d' AND redirectFrom = %s ORDER BY redirectCreated DESC LIMIT 0, 1", $wpdb->blogid, $search));

					if($wpdb->num_rows > 0)
					{
						foreach($result as $r)
						{
							$redirect_id = $r->redirectID;
							$redirect_status = $r->redirectStatus;
							$redirect_to = $r->redirectTo;

							if(substr($redirect_to, 0, 4) != "http")
							{
								$redirect_to = get_site_url()."/".$redirect_to;
							}

							switch($redirect_status)
							{
								case 'publish':
									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."redirect SET redirectUsedAmount = (redirectUsedAmount + 1), redirectUsedDate = NOW() WHERE redirectID = '%d'", $redirect_id));
								break;

								case 'draft':
								case 'ignore':
								case 'search':
									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."redirect SET redirectUsedAmount = (redirectUsedAmount + 1), redirectUsedDate = NOW() WHERE redirectID = '%d'", $redirect_id));

									$redirect_to = "";
								break;
							}
						}
					}

					else if(strpos($search, ".") === false)
					{
						//do_log(__FUNCTION__.": No - ".$wpdb->last_query);

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."redirect SET blogID = '%d', redirectStatus = %s, redirectFrom = %s, redirectTo = %s, redirectCreated = NOW(), redirectUsedDate = NOW(), redirectUsedAmount = '1'", $wpdb->blogid, 'draft', $search, ""));
					}
				}

				if($redirect_to == "")
				{
					$search_groups = get_option_or_default('setting_also_search', get_post_types_for_select(array('include' => array('types'))));

					// Search twice: First looking for exact title match (high priority), then for a general search
					foreach($search_groups as $group)
					{
						switch($group)
						{
							case "tags":
								$arr_tags = get_tags(array("name__like" => $search));

								if(count($arr_tags) == 1)
								{
									$redirect_to = get_tag_link($arr_tags[0]->term_id);
								}
							break;

							case "categories":
								$categories = get_categories(array("name__like" => $search));

								if(count($categories) == 1)
								{
									$redirect_to = get_category_link($categories[0]->term_id);
								}
							break;

							default:
								$arr_posts = get_posts(array('name' => $search, 'post_type' => $group));

								if(count($arr_posts) == 1)
								{
									$redirect_to = get_permalink($arr_posts[0]->ID);
								}
							break;
						}
					}

					// Now perform general search
					foreach($search_groups as $group)
					{
						$arr_posts = $this->search($search, $group);

						if(count($arr_posts) == 1)
						{
							$redirect_to = get_permalink($arr_posts[0]->ID);
						}

						//$GLOBALS["__smart404"]["suggestions"] = array_merge((array)$GLOBALS["__smart404"]["suggestions"], $arr_posts);
					}
				}
			}

			if($redirect_to != '')
			{
				//do_log(__FUNCTION__." ".$request_uri." -> ".$redirect_to." + ".$get_params);

				mf_redirect($redirect_to.$get_params);
			}

			/*else
			{
				do_log(__FUNCTION__." ".$request_uri);
			}*/
		}
	}

	function redirect_canonical($redirect, $request)
	{
		if(is_404())
		{
			return false;
		}

		return $redirect;
	}
}