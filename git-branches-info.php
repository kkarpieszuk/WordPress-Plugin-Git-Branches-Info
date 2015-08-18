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

		$git_head_file = $this->get_git_head_file_content( $plugin_file );

		if ($git_head_file) {
			$branch_name = $this->get_branch_name($git_head_file);

			if ($branch_name) {
				$plugin_meta = $this->add_branch_info_to_version($branch_name, $plugin_meta);
			}

		}


		return $plugin_meta;
	}

	function get_git_head_file_content($plugin_name) {
		$head_file_path = $this->construct_head_path($plugin_name);

		if (is_file( $head_file_path ) && is_readable($head_file_path) ) {
			$file = file_get_contents($head_file_path);
		}

		return isset($file) ? $file : null;

	}

	private function construct_head_path($plugin_name) {
		$plugin_name = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR .$plugin_name;

		$parts = explode(DIRECTORY_SEPARATOR, $plugin_name);
		unset ( $parts[ count($parts) - 1 ] );

		$dir_name = implode(DIRECTORY_SEPARATOR, $parts);

		$git_dir_name = rtrim($dir_name, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ".git";

		$head_file_path = $git_dir_name . DIRECTORY_SEPARATOR . 'HEAD';
		
		return $head_file_path;
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

	function add_branch_info_to_version($branch_name, $plugin_meta) {
		foreach ($plugin_meta as $index => $meta) {

			$version_text = __( 'Version %s' );
			$version_match = str_replace("%s", "[0-9].", $version_text);
			$match = "/" . $version_match . "/";

			if (preg_match($match, $meta, $matches) ) {
				$plugin_meta[$index] = $meta . " (" . __("Git branch:", "git-branch-plugin") . " " . $branch_name . ")";
			}
		}

		return $plugin_meta;
	}

}

$git_branches = new Git_Branches_Client;
