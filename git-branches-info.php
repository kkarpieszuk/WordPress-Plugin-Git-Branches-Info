<?php
/*
 * Plugin Name: Git Branches Info
 * Description: Shows git branch information next to plugin version in Dashboard > Plugins
 * Author: Konrad Karpieszuk
 * Author URI: http://muzungu.pl
 * Version: 0.1
 * Text Domain: git-branch-plugin
 *
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

class Git_Branches_Client {

	function __construct() {
		add_filter('plugin_row_meta', array($this, 'filter_plugin_row_meta'), 4, 10);
	}

	function filter_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
		$additional_infos = array();

		$git_head_file = $this->get_git_head_file_content( $plugin_file );

		if ($git_head_file) {
			$branch_name = $this->get_branch_name($git_head_file);
			if ($branch_name) {
				$additional_infos[] = $this->branch_name_string($branch_name);
			}

			$git_fetch_head_file_time = $this->get_git_fetch_head_file_time( $plugin_file );
			if ($git_fetch_head_file_time) {
				$additional_infos[] = $this->file_time_string($git_fetch_head_file_time);
			} else if ($git_fetch_head_file_time === null) {
				$additional_infos[] = __("never pulled", "git-branch-plugin");
			}
		}

		$plugin_meta = $this->add_branch_info_to_version($additional_infos, $plugin_meta);

		return $plugin_meta;
	}

	function get_git_head_file_content($plugin_name) {
		$head_file_path = $this->construct_head_path($plugin_name);

		if (is_file( $head_file_path ) && is_readable($head_file_path) ) {
			$file = file_get_contents($head_file_path);
		}

		return isset($file) ? $file : null;

	}

	function get_git_fetch_head_file_time( $plugin_name ) {
		$head_fetch_file_path = $this->construct_fetch_head_path( $plugin_name );
		if (!is_file($head_fetch_file_path)) {
			$time = null;
		} else {
			$time = filemtime($head_fetch_file_path);
		}

		return  $time;
	}

	private function construct_head_path($plugin_name) {
		$git_dir_name = $this->git_directory_path($plugin_name);

		$head_file_path = $git_dir_name . DIRECTORY_SEPARATOR . 'HEAD';

		return $head_file_path;
	}

	function construct_fetch_head_path( $plugin_name ) {
		$git_dir_name = $this->git_directory_path($plugin_name);

		$file_path = $git_dir_name . DIRECTORY_SEPARATOR . 'FETCH_HEAD';

		return $file_path;
	}

	function git_directory_path( $plugin_name ) {
		$plugin_name = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR .$plugin_name;

		if(PHP_OS == "Windows" || PHP_OS == "WINNT"){
            $plugin_name = str_replace( "/" , DIRECTORY_SEPARATOR , $plugin_name );
        }

		$parts = explode(DIRECTORY_SEPARATOR, $plugin_name);
		unset ( $parts[ count($parts) - 1 ] );

		$dir_name = implode(DIRECTORY_SEPARATOR, $parts);

		$git_dir_name = rtrim($dir_name, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ".git";

		return $git_dir_name;
	}

	function get_branch_name($file_content) {
		$lines = explode("\n", $file_content);
		$branch_name = false;
		foreach ($lines as $line) {
			if (strpos($line, 'ref:') === 0) {
				$in_line = explode("/", $line);
				$branch_name = $in_line[ count($in_line) - 1 ];
				break;
			}
		}

		return $branch_name;
	}

	function add_branch_info_to_version($additional_infos, $plugin_meta) {
		if (!empty($additional_infos)) {
			foreach ($plugin_meta as $index => $meta) {

				$version_text = __( 'Version %s' );
				$version_match = str_replace("%s", "[0-9].", $version_text);
				$match = "/" . $version_match . "/";

				if (preg_match($match, $meta, $matches) ) {
					foreach($additional_infos as $info) {
							$plugin_meta[$index] .= ", " . $info;
					}
				}
			}
		}

		return $plugin_meta;
	}

	function branch_name_string( $branch_name ) {
		return __("git branch:", "git-branch-plugin") . " " . $branch_name;
	}

	function file_time_string($git_fetch_head_file_time) {
		$time_elapsed = human_time_diff($git_fetch_head_file_time);
		$date = date('l jS \of F Y h:i:s A', $git_fetch_head_file_time);
		return "<span title='".$date."'>". sprintf(__("pulled %s ago", "git-branch-plugin"), $time_elapsed) . "</span>";
	}

}

$git_branches = new Git_Branches_Client;
