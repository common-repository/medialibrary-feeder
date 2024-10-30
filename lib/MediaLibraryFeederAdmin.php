<?php
/**
 * MediaLibrary Feeder
 * 
 * @package    MediaLibrary Feeder
 * @subpackage MediaLibraryFeederAdmin Management screen
    Copyright (c) 2014- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$medialibraryfeederadmin = new MediaLibraryFeederAdmin();

class MediaLibraryFeederAdmin {

	private $itunes_categories;
	private $plugin_base_file;
	private $upload_url;

	/* ==================================================
	 * Construct
	 * @since	4.06
	 */
	public function __construct() {

		$this->itunes_categories = json_decode(get_option('medialibrary_feeder_itunes_categories'), TRUE);

		$plugin_base_dir = untrailingslashit(plugin_dir_path( __DIR__ ));
		$slugs = explode('/', $plugin_base_dir);
		$slug = end($slugs);
		$this->plugin_base_file = $slug.'/medialibraryfeeder.php';

		$wp_uploads = wp_upload_dir();
		if(is_ssl()){
			$this->upload_url = str_replace('http:', 'https:', $wp_uploads['baseurl']);
		} else {
			$this->upload_url = $wp_uploads['baseurl'];
		}

		if(!class_exists('MediaLibraryFeeder')){
			require_once( $plugin_base_dir.'/lib/MediaLibraryFeeder.php' );
		}

		add_action( 'admin_menu', array($this, 'plugin_menu') );
		add_action( 'admin_enqueue_scripts', array($this, 'load_custom_wp_admin_style') );
		add_filter( 'plugin_action_links', array($this, 'settings_link'), 10, 2 );
		add_action( 'mediafeed_add_form_fields', array($this, 'add_taxonomy_fields') ); 
		add_action( 'mediafeed_edit_form_fields', array($this, 'edit_taxonomy_fields') ); 
		add_action( 'create_mediafeed', array($this, 'mediafeed_edit_terms'), 10, 2 );
		add_action( 'edited_mediafeed', array($this, 'mediafeed_edit_terms'), 11, 2 );
		add_action( 'delete_mediafeed', array($this, 'mediafeed_delete_terms'), 10, 4 );
		add_action( 'add_meta_boxes', array($this, 'mediafeed_add_meta_box') );
		add_action( 'edit_attachment', array($this, 'mediafeed_save_meta_box_data') );
		add_filter( 'manage_media_columns', array($this, 'muc_column') );
		add_action( 'manage_media_custom_column', array($this, 'muc_value'), 12, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'add_feed_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'search_filter') );
		add_action( 'admin_footer', array( $this, 'custom_bulk_admin_footer') );
		add_action( 'load-upload.php', array( $this, 'custom_bulk_action') );
		add_action( 'admin_notices', array( $this, 'custom_bulk_admin_notices') );

	}

	/* ==================================================
	 * Add a "Settings" link to the plugins page
	 * @since	1.0
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty($this_plugin) ) {
			$this_plugin = $this->plugin_base_file;
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="'.admin_url('options-general.php?page=medialibraryfeeder').'">'.__( 'Settings').'</a>';
		}
			return $links;
	}

	/* ==================================================
	 * Settings page
	 * @since	1.0
	 */
	public function plugin_menu() {
			add_options_page( 'MediaLibrary Feeder', 'MediaLibrary Feeder', 'manage_categories', 'medialibraryfeeder', array($this, 'plugin_options') );
	}

	/* ==================================================
	 * Add Css and Script
	 * @since	2.9
	 */
	public function load_custom_wp_admin_style() {
		if ($this->is_my_plugin_screen()) {
			wp_enqueue_style( 'jquery-responsiveTabs', plugin_dir_url( __DIR__ ).'css/responsive-tabs.css' );
			wp_enqueue_style( 'jquery-responsiveTabs-style', plugin_dir_url( __DIR__ ).'css/style.css' );
			wp_enqueue_style( 'icomoon-style', plugin_dir_url( __DIR__ ).'icomoon/style.css' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-responsiveTabs', plugin_dir_url( __DIR__ ).'js/jquery.responsiveTabs.min.js' );
			wp_enqueue_script( 'medialibraryfeeder-js', plugin_dir_url( __DIR__ ).'js/jquery.medialibraryfeeder.js', array('jquery') );
		}
	}

	/* ==================================================
	 * For only admin style
	 * @since	3.4
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if (is_object($screen) && $screen->id == 'settings_page_medialibraryfeeder') {
			return TRUE;
		} else if (is_object($screen) && $screen->id == 'edit-mediafeed') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/* ==================================================
	 * Settings Menu
	 */
	public function plugin_options() {

		if ( !current_user_can( 'manage_categories' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$screenshot_html = '<a href="'.__('https://wordpress.org/plugins/medialibrary-feeder/screenshots/', 'medialibrary-feeder').'" target="_blank" style="text-decoration: none; word-break: break-all;">'.__('Screenshots', 'medialibrary-feeder').'</a>';

		?>
		<div class="wrap">
		<h2>MediaLibrary Feeder</h2>
		<div id="medialibraryfeeder-admin-tabs">
		  <ul>
		    <li><a href="#medialibraryfeeder-admin-tabs-1"><?php _e('How to use', 'medialibrary-feeder'); ?></a></li>
			<li><a href="#medialibraryfeeder-admin-tabs-2"><?php _e('Donate to this plugin &#187;'); ?></a></li>
		  </ul>

		  <div id="medialibraryfeeder-admin-tabs-1">
			<div class="wrap">
				<h2><?php _e('How to use', 'medialibrary-feeder'); ?>(<?php echo $screenshot_html; ?>)</h2>
				<li style="margin: 0px 40px;">
				<h3><?php _e('Register your feed with "Feeds Management".', 'medialibrary-feeder'); ?>
				</h3>
				</li>
				<li style="margin: 0px 40px;">
				<h3><?php _e('If you want to enter individual itunes tags for each media, enter by "Edit Media".', 'medialibrary-feeder'); ?>
				</h3>
				</li>
				<li style="margin: 0px 40px;">
				<h3><?php _e('Select feeds and media in the "Media Library" and create feeds.', 'medialibrary-feeder'); ?>
				</h3>
				</li>
				<li style="margin: 0px 40px;">
				<h3><?php _e('Custom posts is created in "Feeds".', 'medialibrary-feeder'); ?>
				</h3>
				</li>
				<li style="margin: 0px 40px;">
				<h3><?php _e('If custom post becomes 404 error, please "Save Changes" in "Settings" -> "Permalinks".', 'medialibrary-feeder'); ?>
				</h3>
				</li>
				<li style="margin: 0px 40px;">
				<h3><?php _e('You can set the feed icon to widget.', 'medialibrary-feeder'); ?>
				</h3>
				</li>
			</div>
		  </div>

		  <div id="medialibraryfeeder-admin-tabs-2">
			<div class="wrap">
			<?php $this->credit(); ?>
			</div>
		  </div>

		</div>
		</div>
	<?php
	}

	/* ==================================================
	 * Credit
	 */
	private function credit() {

		$plugin_name = NULL;
		$plugin_ver_num = NULL;
		$plugin_path = plugin_dir_path( __DIR__ );
		$plugin_dir = untrailingslashit($plugin_path);
		$slugs = explode('/', $plugin_dir);
		$slug = end($slugs);
		$files = scandir($plugin_dir);
		foreach ($files as $file) {
			if($file == '.' || $file == '..' || is_dir($plugin_path.$file)){
				continue;
			} else {
				$exts = explode('.', $file);
				$ext = strtolower(end($exts));
				if ( $ext === 'php' ) {
					$plugin_datas = get_file_data( $plugin_path.$file, array('name'=>'Plugin Name', 'version' => 'Version') );
					if ( array_key_exists( "name", $plugin_datas ) && !empty($plugin_datas['name']) && array_key_exists( "version", $plugin_datas ) && !empty($plugin_datas['version']) ) {
						$plugin_name = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __('Version:').' '.$plugin_ver_num;
		$faq = __('https://wordpress.org/plugins/'.$slug.'/faq', $slug);
		$support = 'https://wordpress.org/support/plugin/'.$slug;
		$review = 'https://wordpress.org/support/view/plugin-reviews/'.$slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/'.$slug;
		$facebook = 'https://www.facebook.com/katsushikawamori/';
		$twitter = 'https://twitter.com/dodesyo312';
		$youtube = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate = __('https://riverforest-wp.info/donate/', $slug);

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo $plugin_version; ?> | 
		<a style="text-decoration: none;" href="<?php echo $faq; ?>" target="_blank"><?php _e('FAQ'); ?></a> | <a style="text-decoration: none;" href="<?php echo $support; ?>" target="_blank"><?php _e('Support Forums'); ?></a> | <a style="text-decoration: none;" href="<?php echo $review; ?>" target="_blank"><?php _e('Reviews', 'media-from-ftp'); ?></a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo $translate; ?>" target="_blank"><?php echo sprintf(__('Translations for %s'), $plugin_name); ?></a> | <a style="text-decoration: none;" href="<?php echo $facebook; ?>" target="_blank"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo $twitter; ?>" target="_blank"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo $youtube; ?>" target="_blank"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php _e('Please make a donation if you like my work or would like to further the development of this plugin.', $slug); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo $donate; ?>')"><?php _e('Donate to this plugin &#187;'); ?></button>
		</div>

		<?php

	}

	/**
	 * Add Term
	 * @since	4.0
	 */
	public function add_taxonomy_fields( $taxonomy ) {

		wp_nonce_field('mlf_add_edit_taxonomy', 'medialibrary_feeder_add_edit_taxonomy');
		?>
		<div class="form-field term-parent-wrap">
			<label for="rssmax"><?php _e('Number of feeds', 'medialibrary-feeder'); ?></label>
			<input type="number" id="rssmax" name="rssmax" step="1" min="1" max="999" class="screen-per-page" maxlength="3" value="10" />
			<p><?php _e('Number of feeds of the latest to publish', 'medialibrary-feeder'); ?></p>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="iconhtml"><?php _e('Icon', 'medialibrary-feeder'); ?></label>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-headphones&#034;></span>"><span class="icon icon-headphones"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-connection&#034;></span>"><span class="icon icon-connection"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-feed&#034;></span>"><span class="icon icon-feed"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-podcast&#034;></span>" checked><span class="icon icon-podcast"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-rss&#034;></span>"><span class="icon icon-rss"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-rss2&#034;></span>"><span class="icon icon-rss2"></span>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="ttl"><code>&lt;ttl&gt;</code></label>
			<input type="number" id="ttl" name="ttl" step="1" min="1" max="999" class="screen-per-page" maxlength="3" value="60" />
			<p><?php _e('Stands for time to live. It is a number of minutes.', 'medialibrary-feeder'); ?></p>
		</div>
		<h4><span class="dashicons dashicons-editor-help"></span><a href="<?php _e('https://help.apple.com/itc/podcasts_connect/#/itcb54353390', 'medialibrary-feeder'); ?> " target="_blank"><?php _e('RSS tags for Podcasts Connect', 'medialibrary-feeder'); ?></a></h4>
		<div class="form-field term-parent-wrap">
			<label for="copyright"><code>&lt;copyright&gt;</code></label>
			<input type="text" id="copyright" name="copyright" />
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_author"><code>&lt;itunes:author&gt;</code></label>
			<input type="text" id="itunes_author" name="itunes_author" />
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_block"><code>&lt;itunes:block&gt;</code></label>
			<select id="itunes_block" name="itunes_block">
			<option value='no'>no</option>
			<option value='yes'>yes</option>
			</select>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_category_1"><code>&lt;itunes:category&gt;</code></label>
			<select style="width: 250px;" id="itunes_category_1" name="itunes_category_1">
			<option value=''><?php echo __('Select').'1'; ?></option>
			<?php
			foreach ( $this->itunes_categories as $category_name => $category_tag ) {
				?>
				<option value='<?php echo $category_tag; ?>'><?php _e($category_name, 'medialibrary-feeder'); ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_category_2"><code>&lt;itunes:category&gt;</code></label>
			<select style="width: 250px;" id="itunes_category_2" name="itunes_category_2">
			<option value=''><?php echo __('Select').'2'; ?></option>
			<?php
			foreach ( $this->itunes_categories as $category_name => $category_tag ) {
				?>
				<option value='<?php echo $category_tag; ?>'><?php _e($category_name, 'medialibrary-feeder'); ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_category_3"><code>&lt;itunes:category&gt;</code></label>
			<select style="width: 250px;" id="itunes_category_3" name="itunes_category_3">
			<option value=''><?php echo __('Select').'3'; ?></option>
			<?php
			foreach ( $this->itunes_categories as $category_name => $category_tag ) {
				?>
				<option value='<?php echo $category_tag; ?>'><?php _e($category_name, 'medialibrary-feeder'); ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_image"><code>&lt;itunes:image&gt;</code></label>
			<input type="text" id="itunes_image" name="itunes_image" />
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_explicit"><code>&lt;itunes:explicit&gt;</code></label>
			<select id="itunes_explicit" name="itunes_explicit">
			<option value='no'>no</option>
			<option value='yes'>yes</option>
			<option value='clean'>clean</option>
			</select>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_complete"><code>&lt;itunes:complete&gt;</code></label>
			<select id="itunes_complete" name="itunes_complete">
			<option value='no'>no</option>
			<option value='yes'>yes</option>
			</select>
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_newfeedurl"><code>&lt;itunes:new-feed-url&gt;</code></label>
			<input type="text" id="itunes_newfeedurl" name="itunes_newfeedurl" />
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_name"><code>&lt;itunes:name&gt;</code></label>
			<input type="text" id="itunes_name" name="itunes_name" />
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_email"><code>&lt;itunes:email&gt;</code></label>
			<input type="text" id="itunes_email" name="itunes_email" />
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_subtitle"><code>&lt;itunes:subtitle&gt;</code></label>
			<input type="text" id="itunes_subtitle" name="itunes_subtitle">
		</div>
		<div class="form-field term-parent-wrap">
			<label for="itunes_summary"><code>&lt;itunes:summary&gt;</code></label>
			<textarea name="itunes_summary" id="itunes_summary"></textarea>
		</div>

		<?php
	}

	/**
	 * Edit Term meta
	 * @since	4.0
	 */
	public function edit_taxonomy_fields($tag, $taxonomy = null ) {

		wp_nonce_field('mlf_add_edit_taxonomy', 'medialibrary_feeder_add_edit_taxonomy');
		?>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="feedname"><?php _e('Feed Name', 'medialibrary-feeder'); ?></label></th>
		<td>
		<input type="text" id="feedname" name="feedname" value="<?php echo get_term_meta($tag->term_id, 'feedname', TRUE)?>">
		<?php $feedurl = $this->upload_url.'/'.get_term_meta($tag->term_id, 'feedname', TRUE); ?>
		<p class="description"><?php _e('Feed URL', 'medialibrary-feeder'); ?><a href ="<?php echo $feedurl; ?>"><?php echo $feedurl; ?></a></p>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="rssmax"><?php _e('Number of feeds', 'medialibrary-feeder'); ?></label></th>
		<td>
		<input type="number" id="rssmax" step="1" min="1" max="999" class="screen-per-page" name="rssmax" maxlength="3" value="<?php echo get_term_meta($tag->term_id, 'rssmax', TRUE) ?>">
		<p class="description"><?php _e('Number of feeds of the latest to publish', 'medialibrary-feeder'); ?></p>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="iconhtml"><?php _e('Icon', 'medialibrary-feeder'); ?></label></th>
		<td>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-headphones&#034;></span>" <?php checked('<span class="icon icon-headphones"></span>', get_term_meta($tag->term_id, 'iconhtml', TRUE)); ?>><span class="icon icon-headphones"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-connection&#034;></span>" <?php checked('<span class="icon icon-connection"></span>', get_term_meta($tag->term_id, 'iconhtml', TRUE)); ?>><span class="icon icon-connection"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-feed&#034;></span>" <?php checked('<span class="icon icon-feed"></span>', get_term_meta($tag->term_id, 'iconhtml', TRUE)); ?>><span class="icon icon-feed"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-podcast&#034;></span>" <?php checked('<span class="icon icon-podcast"></span>', get_term_meta($tag->term_id, 'iconhtml', TRUE)); ?>><span class="icon icon-podcast"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-rss&#034;></span>" <?php checked('<span class="icon icon-rss"></span>', get_term_meta($tag->term_id, 'iconhtml', TRUE)); ?>><span class="icon icon-rss"></span>
			<span style="margin-right: 1em;"></span>
			<input type="radio" id="iconhtml" name="iconhtml" value="<span class=&#034;icon icon-rss2&#034;></span>" <?php checked('<span class="icon icon-rss2"></span>', get_term_meta($tag->term_id, 'iconhtml', TRUE)); ?>><span class="icon icon-rss2"></span>

		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="ttl"><code>&lt;ttl&gt;</code></label></th>
		<td>
			<input type="number" id="ttl" name="ttl" step="1" min="1" max="999" class="screen-per-page" maxlength="3" value="<?php echo get_term_meta($tag->term_id, 'ttl', TRUE) ?>" />
		<p class="description"><?php _e('Stands for time to live. It is a number of minutes.', 'medialibrary-feeder'); ?></p>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th colspan="2">
		<h4><span class="dashicons dashicons-editor-help"></span><a href="<?php _e('https://help.apple.com/itc/podcasts_connect/#/itcb54353390', 'medialibrary-feeder'); ?> " target="_blank"><?php _e('RSS tags for Podcasts Connect', 'medialibrary-feeder'); ?></a></h4></th>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="copyright"><code>&lt;copyright&gt;</code></label></th>
		<td>
			<input type="text" id="copyright" name="copyright" value="<?php echo get_term_meta($tag->term_id, 'copyright', TRUE) ?>" />
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_author"><code>&lt;itunes:author&gt;</code></label></th>
		<td>
			<input type="text" id="itunes_author" name="itunes_author" value="<?php echo get_term_meta($tag->term_id, 'itunes_author', TRUE) ?>" />
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_block"><code>&lt;itunes:block&gt;</code></label></th>
		<td>
			<select id="itunes_block" name="itunes_block">
			<option value='no' <?php if (get_term_meta($tag->term_id, 'itunes_block', TRUE) === 'no') echo 'selected';?>>no</option>
			<option value='yes' <?php if (get_term_meta($tag->term_id, 'itunes_block', TRUE) === 'yes') echo 'selected';?>>yes</option>
			</select>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_category_1"><code>&lt;itunes:category&gt;</code></label></th>
		<td>
			<select style="width: 250px;" id="itunes_category_1" name="itunes_category_1">
			<option value=''><?php echo __('Select').'1'; ?></option>
			<?php
			foreach ( $this->itunes_categories as $category_name => $category_tag ) {
				?>
				<option value='<?php echo $category_tag; ?>' <?php if (get_term_meta($tag->term_id, 'itunes_category_1', TRUE) === $category_tag) echo 'selected';?>><?php _e($category_name, 'medialibrary-feeder'); ?></option>
				<?php
			}
			?>
			</select>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_category_2"><code>&lt;itunes:category&gt;</code></label></th>
		<td>
			<select style="width: 250px;" id="itunes_category_2" name="itunes_category_2">
			<option value=''><?php echo __('Select').'2'; ?></option>
			<?php
			foreach ( $this->itunes_categories as $category_name => $category_tag ) {
				?>
				<option value='<?php echo $category_tag; ?>' <?php if (get_term_meta($tag->term_id, 'itunes_category_2', TRUE) === $category_tag) echo 'selected';?>><?php _e($category_name, 'medialibrary-feeder'); ?></option>
				<?php
			}
			?>
			</select>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_category_3"><code>&lt;itunes:category&gt;</code></label></th>
		<td>
			<select style="width: 250px;" id="itunes_category_3" name="itunes_category_3">
			<option value=''><?php echo __('Select').'3'; ?></option>
			<?php
			foreach ( $this->itunes_categories as $category_name => $category_tag ) {
				?>
				<option value='<?php echo $category_tag; ?>' <?php if (get_term_meta($tag->term_id, 'itunes_category_3', TRUE) === $category_tag) echo 'selected';?>><?php _e($category_name, 'medialibrary-feeder'); ?></option>
				<?php
			}
			?>
			</select>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_image"><code>&lt;itunes:image&gt;</code></label></th>
		<td>
			<input type="text" id="itunes_image" name="itunes_image" value="<?php echo get_term_meta($tag->term_id, 'itunes_image', TRUE) ?>" />
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_explicit"><code>&lt;itunes:explicit&gt;</code>
		<td>
			<select id="itunes_explicit" name="itunes_explicit">
			<option value='no' <?php if (get_term_meta($tag->term_id, 'itunes_explicit', TRUE) === 'no') echo 'selected';?>>no</option>
			<option value='yes' <?php if (get_term_meta($tag->term_id, 'itunes_explicit', TRUE) === 'yes') echo 'selected';?>>yes</option>
			<option value='clean' <?php if (get_term_meta($tag->term_id, 'itunes_explicit', TRUE) === 'clean') echo 'selected';?>>clean</option>
			</select>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_complete"><code>&lt;itunes:complete&gt;</code></label></th>
		<td>
			<select id="itunes_complete" name="itunes_complete">
			<option value='no' <?php if (get_term_meta($tag->term_id, 'itunes_complete', TRUE) === 'no') echo 'selected';?>>no</option>
			<option value='yes' <?php if (get_term_meta($tag->term_id, 'itunes_complete', TRUE) === 'yes') echo 'selected';?>>yes</option>
			</select>
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_newfeedurl"><code>&lt;itunes:new-feed-url&gt;</code></label></th>
		<td>
			<input type="text" id="itunes_newfeedurl" name="itunes_newfeedurl" value="<?php echo get_term_meta($tag->term_id, 'itunes_newfeedurl', TRUE) ?>" />
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_name"><code>&lt;itunes:name&gt;</code></label></th>
		<td>
			<input type="text" id="itunes_name" name="itunes_name" value="<?php echo get_term_meta($tag->term_id, 'itunes_name', TRUE) ?>" />
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_email"><code>&lt;itunes:email&gt;</code></label></th>
		<td>
			<input type="text" id="itunes_email" name="itunes_email" value="<?php echo get_term_meta($tag->term_id, 'itunes_email', TRUE) ?>" />
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_subtitle"><code>&lt;itunes:subtitle&gt;</code></label></th>
		<td>
			<input type="text" id="itunes_subtitle" name="itunes_subtitle" value="<?php echo get_term_meta($tag->term_id, 'itunes_subtitle', TRUE) ?>">
		</td>
		</tr>
		<tr class="form-field term-image-wrap">
		<th scope="row"><label for="itunes_summary"><code>&lt;itunes:summary&gt;</code></label></th>
		<td>
			<textarea name="itunes_summary" id="itunes_summary"><?php echo get_term_meta($tag->term_id, 'itunes_summary', TRUE) ?></textarea>
		</td>
		</tr>

		<?php

	}

	/**
	 * Save Term meta
	 * @since	4.0
	 */
	public function mediafeed_edit_terms( $term_id, $taxonomy ) {
		if ( isset($_POST['rssmax']) && $_POST['rssmax'] ) {
			if ( check_admin_referer('mlf_add_edit_taxonomy', 'medialibrary_feeder_add_edit_taxonomy')) {
				$term = get_term( $term_id, 'mediafeed' );
				if (isset($_POST['feedname'])) {
					$feedname = sanitize_text_field($_POST['feedname']);
				} else {
					$feedname = 'medialibraryfeeder-'.md5($term->name).'.xml';
				}
				update_term_meta( $term_id, 'feedname', $feedname );
				update_term_meta( $term_id, 'rssmax', intval($_POST['rssmax']) );
				update_term_meta( $term_id, 'iconhtml', $_POST['iconhtml'] );
				update_term_meta( $term_id, 'ttl', intval($_POST['ttl']) );
				update_term_meta( $term_id, 'copyright', sanitize_text_field($_POST['copyright']) );
				update_term_meta( $term_id, 'itunes_author', sanitize_text_field($_POST['itunes_author']) );
				update_term_meta( $term_id, 'itunes_block', sanitize_text_field($_POST['itunes_block']) );
				update_term_meta( $term_id, 'itunes_category_1', $_POST['itunes_category_1'] );
				update_term_meta( $term_id, 'itunes_category_2', $_POST['itunes_category_2'] );
				update_term_meta( $term_id, 'itunes_category_3', $_POST['itunes_category_3'] );
				update_term_meta( $term_id, 'itunes_image', sanitize_text_field($_POST['itunes_image']) );
				update_term_meta( $term_id, 'itunes_explicit', sanitize_text_field($_POST['itunes_explicit']) );
				update_term_meta( $term_id, 'itunes_complete', sanitize_text_field($_POST['itunes_complete']) );
				update_term_meta( $term_id, 'itunes_newfeedurl', sanitize_text_field($_POST['itunes_newfeedurl']) );
				update_term_meta( $term_id, 'itunes_name', sanitize_text_field($_POST['itunes_name']) );
				update_term_meta( $term_id, 'itunes_email', sanitize_email($_POST['itunes_email']) );
				update_term_meta( $term_id, 'itunes_subtitle', sanitize_text_field($_POST['itunes_subtitle']) );
				update_term_meta( $term_id, 'itunes_summary', sanitize_text_field($_POST['itunes_summary']) );
				// for custom post
				$term = get_term( $term_id, 'mediafeed' );
				$medialibraryfeeder = new MediaLibraryFeeder();
				$medialibraryfeeder->create_custom_post($term);
				unset($medialibraryfeeder);
			}
		}
	}

	/**
	 * Dlete Term meta
	 * @since	4.0
	 */
	public function mediafeed_delete_terms( $term, $tt_id, $deleted_term, $object_ids ) {

		// Feed file
		if ( get_option( 'medialibraryfeeder_feedfile') ) {
			$feedfile_tbl = get_option('medialibraryfeeder_feedfile');
			$xmlfile = $feedfile_tbl[$term];
			if ( file_exists($xmlfile)){
				unlink($xmlfile);
			}
			unset($feedfile_tbl[$term]);
			update_option( 'medialibraryfeeder_feedfile', $feedfile_tbl );
		}
		// Feed widget
		if ( get_option('medialibraryfeeder_feedwidget') ) {
			$feedwidget_tbl = get_option('medialibraryfeeder_feedwidget');
			unset($feedwidget_tbl[$term]);
			update_option( 'medialibraryfeeder_feedwidget', $feedwidget_tbl );
		}

		// Custom Post
		if ( get_option( 'medialibraryfeeder_term_id_post_id') ) {
			$term_id_post_id_tbl = get_option( 'medialibraryfeeder_term_id_post_id');
			wp_delete_post($term_id_post_id_tbl[$term]);
			unset($term_id_post_id_tbl[$term]);
			update_option( 'medialibraryfeeder_term_id_post_id', $term_id_post_id_tbl );
		}

	}

	/**
	 * Add meta box
	 * @since	4.0
	 */
	public function mediafeed_add_meta_box() {
	    	add_meta_box( 'mediafeed_sectionid', __( 'Edit individual media feed', 'medialibrary-feeder' ),
						array($this, 'mediafeed_meta_box_callback'),
						'attachment', 'advanced', 'high' );
	}

	/**
	 * Display Feed meta box
	 * @since	4.0
	 */
	public function mediafeed_meta_box_callback( $post ) {

		wp_nonce_field( 'mediafeed_save_meta_box_data', 'mediafeed_meta_box_nonce' );

		$prefix = 'feed_metas['.$post->ID.'][medialibraryfeeder_';

		$termobjs = wp_get_object_terms( (string)$post->ID, 'mediafeed' );
		if ( ! empty( $termobjs ) && ! is_wp_error( $termobjs ) ) {
			$feedtitle = $termobjs[0]->name;
		} else {
			$feedtitle = NULL;
		}

		$terms = get_terms( 'mediafeed', array( 'hide_empty' => false ) );

		$feedtitle_selected = NULL;
		$feedcount = 0;
		$select_feedtitle = '<select name="'.$prefix.'title]" id="'.$prefix.'title]">';
		foreach ( $terms as $term ) {
			if ( $feedtitle === $term->name ) {
				$feedtitle_selected = ' selected="selected"';
				++$feedcount;
			} else {
				$feedtitle_selected = NULL;
			}
			$select_feedtitle .= '<option value="'.esc_attr( $term->name ).'"'.$feedtitle_selected.'>'.esc_attr( $term->name ).'</option>';
		}
		if ( $feedcount == 0 ) {
			$feedtitle_selected = ' selected="selected"';
		} else {
			$feedtitle_selected = NULL;
		}
		$select_feedtitle .= '<option value=""'.$feedtitle_selected.'>'.esc_attr(__('Select')).'</option>';
		$select_feedtitle .= '</select>';


		$rsstags_description = '<a href="'.__('https://help.apple.com/itc/podcasts_connect/#/itcb54353390', 'medialibrary-feeder').'" target="_blank">'.__('RSS tags for Podcasts Connect', 'medialibrary-feeder').'</a>';

		$itunes_author = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_author', true );
		if ( empty($itunes_author) ) {
			$user = get_userdata($post->post_author);
			$itunes_author = $user->display_name;
		}
		$itunes_block = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_block', true );
		$itunes_image = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_image', true );
		$itunes_explicit = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_explicit', true );
		$itunes_isClosedCaptioned = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_isClosedCaptioned', true );
		$itunes_order = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_order', true );
		$itunes_subtitle = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_subtitle', true );
		$itunes_summary = get_post_meta( $post->ID, 'medialibraryfeeder_itunes_summary', true );

		?>
		<table>
		<tr>
		<th align="right"><label for="<?php echo $prefix.'title]'; ?>">
		<?php _e('Feed Title', 'medialibrary-feeder'); ?>
		</label></th>
		<td><?php echo $select_feedtitle; ?></td>
		</tr>
		<?php
		$exts = explode( '.', wp_get_attachment_url($post->ID) );
		$ext = end($exts);
		if ( $ext === 'm4a' || $ext === 'mp3' || $ext === 'mov' || $ext === 'mp4' || $ext === 'm4v' || $ext === 'pdf' || $ext === 'epub' ) {
			?>
			<tr>
			<th><hr></th>
			<td style="width: 100%;"><hr></td>
			</tr>
			<tr>
			<th align="right"><label for="rss_tags_title">
			<?php _e('RSS tags', 'medialibrary-feeder'); ?>
			</label></th>
			<td><?php echo $rsstags_description; ?></td>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_author]'; ?>">
			<code>&lt;itunes:author&gt;</code>
			</label></th>
			<td><input type="text" name="<?php echo $prefix.'itunes_author]'; ?>" id="<?php echo $prefix.'itunes_author]'; ?>" value="<?php echo $itunes_author; ?>" /></td>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_block]'; ?>">
			<code>&lt;itunes:block&gt;</code>
			</label></th>
			<td><select name="<?php echo $prefix.'itunes_block]'; ?>" id="<?php echo $prefix.'itunes_block]'; ?>">
			<option value="no"<?php if ($itunes_block === 'no') echo ' selected="selected"';?>>no</option>
			<option value="yes"<?php if ($itunes_block === 'yes') echo ' selected="selected"';?>>yes</option>
			</select>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_image]'; ?>">
			<code>&lt;itunes:image&gt;</code>
			</label></th>
			<td><input type="text" name="<?php echo $prefix.'itunes_image]'; ?>" id="<?php echo $prefix.'itunes_image]'; ?>" value="<?php echo $itunes_image; ?>" /></td>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_explicit]'; ?>">
			<code>&lt;itunes:explicit&gt;</code>
			</label></th>
			<td><select name="<?php echo $prefix.'itunes_explicit]'; ?>" id="<?php echo $prefix.'itunes_explicit]'; ?>">
			<option value="no"<?php if ($itunes_explicit === 'no') echo ' selected="selected"';?>>no</option>
			<option value="yes"<?php if ($itunes_explicit === 'yes') echo ' selected="selected"';?>>yes</option>
			<option value="clean"<?php if ($itunes_explicit === 'clean') echo ' selected="selected"';?>>clean</option>
			</select>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_isClosedCaptioned]'; ?>">
			<code>&lt;itunes:isClosedCaptioned&gt;</code>
			</label></th>
			<td><select name="<?php echo $prefix.'itunes_isClosedCaptioned]'; ?>" id="<?php echo $prefix.'itunes_isClosedCaptioned]'; ?>">
			<option value="no"<?php if ($itunes_isClosedCaptioned === 'no') echo ' selected="selected"';?>>no</option>
			<option value="yes"<?php if ($itunes_isClosedCaptioned === 'yes') echo ' selected="selected"';?>>yes</option>
			</select>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_order]'; ?>">
			<code>&lt;itunes:order&gt;</code>
			</label></th>
			<td><input type="text" name="<?php echo $prefix.'itunes_order]'; ?>" id="<?php echo $prefix.'itunes_order]'; ?>" value="<?php echo $itunes_order; ?>" /></td>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_subtitle]'; ?>">
			<code>&lt;itunes:subtitle&gt;</code>
			</label></th>
			<td><input type="text" name="<?php echo $prefix.'itunes_subtitle]'; ?>" id="<?php echo $prefix.'itunes_subtitle]'; ?>" value="<?php echo $itunes_subtitle; ?>" /></td>
			</tr>
			<tr>
			<th align="right"><label for="<?php echo $prefix.'itunes_summary]'; ?>">
			<code>&lt;itunes_summary&gt;</code>
			</label></th>
			<td><textarea name="<?php echo $prefix.'itunes_summary]'; ?>" id="<?php echo $prefix.'itunes_summary]'; ?>"><?php echo $itunes_summary; ?></textarea></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}

	/**
	 * Save meta box for post_meta
	 * @since	4.0
	 */
	public function mediafeed_save_meta_box_data( $post_id ) {

		if ( ! isset( $_POST['mediafeed_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['mediafeed_meta_box_nonce'], 'mediafeed_save_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'attachment' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		if ( ! isset( $_POST['feed_metas'] ) ) {
			return;
		}
		$feed_metas = $_POST['feed_metas'];

		$medialibraryfeeder_arr = array(
									'medialibraryfeeder_itunes_author',
									'medialibraryfeeder_itunes_block',
									'medialibraryfeeder_itunes_image',
									'medialibraryfeeder_itunes_explicit',
									'medialibraryfeeder_itunes_isClosedCaptioned',
									'medialibraryfeeder_itunes_order',
									'medialibraryfeeder_itunes_subtitle',
									'medialibraryfeeder_itunes_summary'
								);

		foreach ( $medialibraryfeeder_arr as $key ) {
			if( isset( $feed_metas[$post_id][$key] ) ) {
				if ( !empty(sanitize_text_field($feed_metas[$post_id]['medialibraryfeeder_title'])) ) {
		    		update_post_meta( $post_id, $key, sanitize_text_field($feed_metas[$post_id][$key]) );
				} else {
					delete_post_meta( $post_id, $key );
				}
			} else {
				delete_post_meta( $post_id, $key );
			}
		}

		$medialibraryfeeder = new MediaLibraryFeeder();
		$term = NULL;
		if ( isset($feed_metas[$post_id]['medialibraryfeeder_title']) ) {
			$term_name = sanitize_text_field($feed_metas[$post_id]['medialibraryfeeder_title']);
			$term = get_term_by('name', $term_name, 'mediafeed');
			if ( !empty($term_name) ) {
				if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
					$is_term_relation = get_objects_in_term($term->term_id, 'mediafeed');
					if ( !empty($is_term_relation) ) {
						wp_set_object_terms( $post_id, $term->term_id, 'mediafeed', false );
					} else {
						wp_set_object_terms( $post_id, $term->term_id, 'mediafeed', true );
					}
				}
			} else {
				wp_delete_object_term_relationships( $post_id, 'mediafeed' );
			}
		}
		if ( is_object($term) ) {
			$medialibraryfeeder->create_custom_post($term);
		}
		unset($medialibraryfeeder);

	}

	/* ==================================================
	 * Media Library Column
	 * @param	array	$cols
	 * @return	array	$cols
	 * @since	4.0
	 */
	public function muc_column( $cols ) {

		if ( current_user_can('manage_categories') ) {
			$cols["mediafeed"] = __('Feed', 'medialibrary-feeder');
		}

		return $cols;
	}

	/* ==================================================
	 * Media Library Column
	 * @param	string	$column_name
	 * @param	int		$id
	 * @since	4.0
	 */
	public function muc_value( $column_name, $id ) {

		if ( $column_name == "mediafeed" ) {

			$termobjs = wp_get_object_terms( (string)$id, 'mediafeed' );
			if ( ! empty( $termobjs ) && ! is_wp_error( $termobjs ) ) {
				$feedtitle = $termobjs[0]->name;
			} else {
				$feedtitle = NULL;
			}

			$term_link = NULL;
			if ( $feedtitle ) {
				$term = get_term_by('name', $feedtitle, 'mediafeed');
				$term_url = get_term_link($term->term_id);
				if ( !is_wp_error( $term_url ) ) {
					$term_link = '<a href="'.esc_url($term_url).'">'.$feedtitle.'</a>';
				}
			} else {
				$term_link = '('.__('Unused', 'medialibrary-feeder').')';
			}

			$feedtitle_selected = NULL;
			$feedcount = 0;
			$select_feedtitle = '<select name="targetfeeds['.$id.']" style="width: 100%; font-size: small; text-align: left;">';
			$terms = get_terms( 'mediafeed', array( 'hide_empty' => false ) );
			foreach ( $terms as $term ) {
				if ( $feedtitle === $term->name ) {
					$feedtitle_selected = ' selected="selected"';
					++$feedcount;
				} else {
					$feedtitle_selected = NULL;
				}
				$select_feedtitle .= '<option value="'.esc_attr( $term->name ).'"'.$feedtitle_selected.'>'.esc_attr( $term->name ).'</option>';
			}
			if ( $feedcount == 0 ) {
				$feedtitle_selected = ' selected="selected"';
			} else {
				$feedtitle_selected = NULL;
			}
			$select_feedtitle .= '<option value=""'.$feedtitle_selected.'>'.esc_attr(__('Select')).'</option>';
			$select_feedtitle .= '</select>';

			echo $term_link.$select_feedtitle;

		}

	}

	/* ==================================================
	 * Media Library Search Filter for terms
	 * Form
	 * @since	4.0
	 */
	public function add_feed_filter() {

		if ( !current_user_can( 'manage_categories' ) )
			return;

		global $wp_list_table;

		if ( empty( $wp_list_table->screen->post_type ) &&
			isset( $wp_list_table->screen->parent_file ) &&
			$wp_list_table->screen->parent_file == 'upload.php' )
			$wp_list_table->screen->post_type = 'attachment';

		if ( is_object_in_taxonomy( $wp_list_table->screen->post_type, 'mediafeed' ) ) {
			$get_mediafeed = NULL;
			$get_mediafeed = filter_input(INPUT_GET, 'postmediafeed', FILTER_SANITIZE_STRING );
			?>
			<select name="postmediafeed" id="postmediafeed">
				<option value="" <?php if(empty($get_mediafeed)) echo 'selected="selected"'; ?>><?php _e('All Feeds', 'medialibrary-feeder'); ?></option>
				<?php
				$terms = get_terms( 'mediafeed', array('hide_empty' => false));
				foreach ($terms as $term) {
					?>
					<option value="<?php echo $term->slug; ?>" <?php if($get_mediafeed == $term->slug) echo 'selected="selected"'; ?>><?php echo $term->name; ?></option>
				<?php
				}
				?>
			</select>
			<?php
		}

	}

	/* ==================================================
	 * Media Library Search Filter for terms
	 * WP Query
	 * @since	4.0
	 */
	public function search_filter($query) {

		if ( !is_admin() )
			return;

		if ( !current_user_can( 'manage_categories' ) )
			return;

		if( !function_exists( 'get_current_screen' ) )
			return;		

		$scr = get_current_screen();
		$get_mediafeed = filter_input(INPUT_GET, 'postmediafeed', FILTER_SANITIZE_STRING );
		if ( !$query->is_main_query() || empty($get_mediafeed) || $scr->base !== 'upload' )
			return;

		$term = get_term_by('slug', $get_mediafeed, 'mediafeed');
		$pids = get_objects_in_term($term->term_id, 'mediafeed');

		if ( !empty($pids) ) {
			$query->set( 'post__in', $pids );
		} else {
			$query->set( 'p', -1 );
		}

	}

	/* ==================================================
	 * Bulk Action Select
	 * @since	4.0
	 */
	public function custom_bulk_admin_footer() {

		if ( !current_user_can( 'manage_categories' ) )
			return;

		global $pagenow;
		if($pagenow == 'upload.php') {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('<option>').val('createfeed').text('<?php _e('Create feed', 'medialibrary-feeder')?>').appendTo("select[name='action']");
					jQuery('<option>').val('createfeed').text('<?php _e('Create feed', 'medialibrary-feeder')?>').appendTo("select[name='action2']");
				});
			</script>
			<?php
		}

	}

	/* ==================================================
	 * Bulk Action
	 * @since	4.0
	 */
	public function custom_bulk_action() {

		if ( !current_user_can( 'manage_categories' ) )
			return;

		if ( !isset( $_REQUEST['detached'] ) ) {

			// get the action
			$wp_list_table = _get_list_table('WP_Media_List_Table');  
			$action = $wp_list_table->current_action();

			$allowed_actions = array("createfeed");
			if(!in_array($action, $allowed_actions)) return;

			check_admin_referer('bulk-media');

			if(isset($_REQUEST['media'])) {
				$post_ids = array_map('intval', $_REQUEST['media']);
			}

			if(empty($post_ids)) return;

			$sendback = remove_query_arg( array('feedcreated', 'message', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
			if ( ! $sendback )
			$sendback = admin_url( "upload.php?post_type=$post_type" );

			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			switch($action) {
				case 'createfeed':
					$feedcreated = 0;
					$target_feeds = $this->sanitize_array($_REQUEST['targetfeeds']);
					$messages = array();

					$medialibraryfeeder = new MediaLibraryFeeder();
					foreach( $post_ids as $post_id ) {
						$term_name = sanitize_text_field($target_feeds[$post_id]);
						$term = get_term_by('name', $term_name, 'mediafeed');
						if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
							$is_term_relation = get_objects_in_term($term->term_id, 'mediafeed');
							if ( !empty($is_term_relation) ) {
								wp_set_object_terms( $post_id, $term->term_id, 'mediafeed', false );
							} else {
								wp_set_object_terms( $post_id, $term->term_id, 'mediafeed', true );
							}
							$edit_terms[$feedcreated] = $term_name;
							$messages[$feedcreated] = 'success';
						} else {
							wp_delete_object_term_relationships( $post_id, 'mediafeed' );
							$messages[$feedcreated] = sprintf(__('%1$s was not added to the feed.', 'medialibrary-feeder'), get_the_title($post_id));
						}
						$feedcreated++;
					}
					$edit_terms = array_unique($edit_terms);
					foreach ( $edit_terms as $edit_term ) {
						$recreate_term = get_term_by('name', $edit_term, 'mediafeed');
						$medialibraryfeeder->create_custom_post($recreate_term);
					}
					$medialibraryfeeder->generate_feed($post_ids);
					unset($medialibraryfeeder);
					$sendback = add_query_arg( array('feedcreated' => $feedcreated, 'ids' => join(',', $post_ids), 'message' => join(',',  $messages)), $sendback );
				break;
				default: return;
			}

			$sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
			wp_redirect($sendback);
			exit();

		}

	}

	/* ==================================================
	 * Bulk Action Message
	 * @since	4.0
	 */
	public function custom_bulk_admin_notices() {

		if ( !current_user_can( 'manage_categories' ) )
			return;

	    global $post_type, $pagenow;

		if ( $pagenow == 'upload.php' && $post_type == 'attachment' && isset($_REQUEST['feedcreated']) && (int) $_REQUEST['feedcreated'] && isset($_REQUEST['message']) ) {
			$messages = explode( ',', sanitize_text_field(urldecode($_REQUEST['message'])) );
			$success_count = 0;
			foreach ( $messages as $message ) {
				if ( $message === 'success' ) {
					++$success_count;
				} else {
					echo '<div class="notice notice-error is-dismissible"><ul><li>'.$message.'</li></ul></div>';
				}
			}
			if ( $success_count > 0 ) {
				echo '<div class="notice notice-success is-dismissible"><ul><li>'.sprintf(__('Created feed from %1$d media files.', 'medialibrary-feeder'),$success_count).'</li></ul></div>';
			}
		}

	}

	/* ==================================================
	* Sanitize Array
	* @param	array	$a
	* @return	string	$_a
	* @since	4.04
	*/
	private function sanitize_array($a) {

		$_a = array();
		foreach($a as $key=>$value) {
			if ( is_array($value) ) {
				$_a[$key] = $this->sanitize_array($value);
			} else {
				$_a[$key] = htmlspecialchars($value);
			}
		}

		return $_a;

	}

}
?>