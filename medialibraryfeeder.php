<?php
/*
Plugin Name: MediaLibrary Feeder
Plugin URI: https://wordpress.org/plugins/medialibrary-feeder/
Version: 4.07
Description: Output feed from the media library. Generate a podcast for iTunes Store.
Author: Katsushi Kawamori
Author URI: https://riverforest-wp.info/
Text Domain: medialibrary-feeder
Domain Path: /languages
*/

/*  Copyright (c) 2014- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

	add_action( 'plugins_loaded', 'medialibrary_feeder_load_textdomain' );
	function medialibrary_feeder_load_textdomain() {
		load_plugin_textdomain('medialibrary-feeder');
	}

	if(!class_exists('MediaLibraryFeederRegist')) require_once( dirname(__FILE__).'/lib/MediaLibraryFeederRegist.php' );
	if(!class_exists('MediaLibraryFeederAdmin')) require_once( dirname(__FILE__).'/lib/MediaLibraryFeederAdmin.php' );
	if(!class_exists('MediaLibraryFeeder')) require_once( dirname(__FILE__).'/lib/MediaLibraryFeeder.php' );
	if(!class_exists('MediaLibraryFeederWidgetItem')) require_once( dirname(__FILE__).'/lib/MediaLibraryFeederWidgetItem.php' );

?>