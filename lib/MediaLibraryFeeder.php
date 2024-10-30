<?php
/**
 * MediaLibrary Feeder
 * 
 * @package    MediaLibrary Feeder
 * @subpackage MediaLibraryFeeder Main Functions
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

$medialibraryfeeder = new MediaLibraryFeeder();
add_action('wp_head', array($medialibraryfeeder, 'feed_link'));
add_action('wp_enqueue_scripts', array($medialibraryfeeder, 'load_custom_style'));

class MediaLibraryFeeder {

	private $upload_dir;
	private $upload_url;

	/* ==================================================
	 * Construct
	 * @since	4.06
	 */
	public function __construct() {

		$wp_uploads = wp_upload_dir();
		if(is_ssl()){
			$this->upload_url = str_replace('http:', 'https:', $wp_uploads['baseurl']);
		} else {
			$this->upload_url = $wp_uploads['baseurl'];
		}
		$this->upload_dir = $wp_uploads['basedir'];

	}

	/* ==================================================
	 * Generate Feed Main
	 * @param	array	$post_ids
	 * @since	1.0
	 */
	public function generate_feed($post_ids){

		$xmlitems = $this->scan_media($post_ids);
		$this->rss_write($xmlitems);

	}

	/* ==================================================
	 * Media Search and Generate XML
	 * @param	array	$post_ids
	 * @return	array	$xmlitems
	 * @since	1.0
	 */
	private function scan_media($post_ids){

		$rsscount = array();
		$xmlitems = array();
		foreach ( $post_ids as $post_id ) {
			$termobjs = wp_get_object_terms( (string)$post_id, 'mediafeed' );
			$feedtitle = $termobjs[0]->name;
			$term = get_term_by('name', $feedtitle, 'mediafeed');
			$rssmax = get_term_meta($term->term_id, 'rssmax', TRUE);
			if( !isset($rsscount[$feedtitle]) ){ $rsscount[$feedtitle] = 0; }
			if ( !empty($feedtitle) && $rssmax > $rsscount[$feedtitle]) {
				$attachment = get_post( $post_id );
				$title = $attachment->post_title;
				$stamptime = mysql2date( DATE_RSS, $attachment->post_date );
				$exts = explode('.', $attachment->guid);
				$ext = end($exts);
				$ext2type = wp_ext2type($ext);
				$thumblink = NULL;
				$link_url = NULL;
				$file_size = NULL;
				$thumblink = wp_get_attachment_image( $attachment->ID, 'thumbnail', TRUE );
				$blog_name = get_bloginfo('name');
				$length = NULL;
				if ( $ext2type === 'image' ) {
					$attachment_image_src = wp_get_attachment_image_src($attachment->ID, 'full');
					$link_url = $attachment_image_src[0];
				} else {
					$link_url = $attachment->guid;
					if ( $ext2type === 'audio' || $ext2type === 'video' ) {
						$attachment_metadata = get_post_meta($attachment->ID, '_wp_attachment_metadata', true);
						$file_size = $attachment_metadata['filesize'];
						$length = $attachment_metadata['length_formatted'];
					}
				}
				$img_url = '<a href="'.esc_url($link_url).'">'.$thumblink.'</a>';
				if( isset($xmlitems[$feedtitle]) ){
					$xmlitems[$feedtitle] .= "<item>\n";
				} else {
					$xmlitems[$feedtitle] = "<item>\n";
				}
				$xmlitems[$feedtitle] .= "<title>".$title."</title>\n";
				$xmlitems[$feedtitle] .= "<link>".esc_url($link_url)."</link>\n";

				if( !empty($thumblink) ) {
					$xmlitems[$feedtitle] .= "<description><![CDATA[".$img_url."]]>".html_entity_decode(strip_tags($attachment->post_content))."</description>\n";
				} else {
					$xmlitems[$feedtitle] .= "<description>". wp_strip_all_tags($attachment->post_content)."</description>\n";
				}
				if ( $ext === 'm4a' || $ext === 'mp3' || $ext === 'mov' || $ext === 'mp4' || $ext === 'm4v' || $ext === 'pdf' || $ext === 'epub' ) {
					$itunes_author = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_author', true );
					if ( empty($itunes_author) ) {
						$user = get_userdata($attachment->post_author);
						$itunes_author = $user->display_name;
					}
					$itunes_subtitle = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_subtitle', true );
					$itunes_summary = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_summary', true );
					$itunes_image = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_image', true );
					$itunes_block = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_block', true );
					$itunes_explicit = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_explicit', true );
					$itunes_isClosedCaptioned = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_isClosedCaptioned', true );
					$itunes_order = get_post_meta( $attachment->ID, 'medialibraryfeeder_itunes_order', true );
					$xmlitems[$feedtitle] .= "<itunes:author>".wp_strip_all_tags($itunes_author)."</itunes:author>\n";
					if ( !empty($itunes_subtitle) ) { $xmlitems[$feedtitle] .= "<itunes:subtitle>".wp_strip_all_tags($itunes_subtitle)."</itunes:subtitle>\n"; }
					if ( !empty($itunes_summary) ) { $xmlitems[$feedtitle] .= "<itunes:summary>".wp_strip_all_tags($itunes_summary)."</itunes:summary>\n"; }
					if ( !empty($itunes_image) ) { $xmlitems[$feedtitle] .= '<itunes:image href="'.esc_url($itunes_image).'"'." />\n"; }
					if ( !empty($itunes_block) ) { $xmlitems[$feedtitle] .= "<itunes:block>".$itunes_block."</itunes:block>\n"; }
					if ( !empty($itunes_explicit) ) { $xmlitems[$feedtitle] .= "<itunes:explicit>".$itunes_explicit."</itunes:explicit>\n"; }
					if ( !empty($itunes_isClosedCaptioned) ) { $xmlitems[$feedtitle] .= "<itunes:isClosedCaptioned>".$itunes_isClosedCaptioned."</itunes:isClosedCaptioned>\n"; }
					if ( !empty($itunes_order) ) { $xmlitems[$feedtitle] .= "<itunes:order>".wp_strip_all_tags($itunes_order)."</itunes:order>\n"; }
					if ( !empty($length) ) { $xmlitems[$feedtitle] .= "<itunes:duration>".$length."</itunes:duration>\n"; }
				}
				$xmlitems[$feedtitle] .= "<guid>".esc_url($link_url)."</guid>\n";
				$xmlitems[$feedtitle] .= "<dc:creator>".$blog_name."</dc:creator>\n";
				$xmlitems[$feedtitle] .= "<pubDate>".$stamptime."</pubDate>\n";
				if ( $ext2type === 'audio' || $ext2type === 'video' ){
					$xmlitems[$feedtitle] .= '<enclosure url="'.esc_url($link_url).'" length="'.$file_size.'" type="'.$this->mime_type($ext).'" />'."\n";
				}
				$xmlitems[$feedtitle] .= "</item>\n";
				++$rsscount[$feedtitle];
			}
		}

		return $xmlitems;

	}


	/* ==================================================
	 * Write Feed
	 * @param	array	$xmlitems
	 * @since	1.0
	 */
	private function rss_write( $xmlitems ) {

		foreach ( $xmlitems as $feedtitle => $xmlitem ) {
			$xml_begin = NULL;
			$xml_end = NULL;

			$term = get_term_by('name', $feedtitle, 'mediafeed');
			$xmlfile = $this->upload_dir.'/'.get_term_meta($term->term_id, 'feedname', TRUE);
			$xmlurl = $this->upload_url.'/'.get_term_meta($term->term_id, 'feedname', TRUE);

			$homeurl = home_url();
			$feedlanguage = get_option('WPLANG');
			$stamptime = mysql2date( DATE_RSS, time() );
			$itunescategory = stripslashes(get_term_meta($term->term_id, 'itunes_category_1', TRUE));
			$itunescategory .= stripslashes(get_term_meta($term->term_id, 'itunes_category_2', TRUE));
			$itunescategory .= stripslashes(get_term_meta($term->term_id, 'itunes_category_3', TRUE));
			$itunescategory = str_replace( '&', '&amp;', $itunescategory );
			if ( !empty(get_term_meta($term->term_id, 'itunes_newfeedurl', TRUE)) ) {
				$itunesnewfeedurl = '<itunes:new-feed-url>'.esc_url(get_term_meta($term->term_id, 'itunes_newfeedurl', TRUE)).'</itunes:new-feed-url>';
			} else {
				$itunesnewfeedurl = NULL;
			}

			$ttl = intval(get_term_meta($term->term_id, 'ttl', TRUE));
			$description = wp_strip_all_tags(term_description($term->term_id, 'mediafeed'), TRUE);
			$copyright = wp_strip_all_tags(get_term_meta($term->term_id, 'copyright', TRUE));
			$itunes_author = wp_strip_all_tags(get_term_meta($term->term_id, 'itunes_author', TRUE));
			$itunes_block = get_term_meta($term->term_id, 'itunes_block', TRUE);
			$itunes_image = esc_url(get_term_meta($term->term_id, 'itunes_image', TRUE));
			$itunes_explicit = get_term_meta($term->term_id, 'itunes_explicit', TRUE);
			$itunes_complete = get_term_meta($term->term_id, 'itunes_complete', TRUE);
			$itunes_name = wp_strip_all_tags(get_term_meta($term->term_id, 'itunes_name', TRUE));
			$itunes_email = get_term_meta($term->term_id, 'itunes_email', TRUE);
			$itunes_subtitle = wp_strip_all_tags(get_term_meta($term->term_id, 'itunes_subtitle', TRUE));
			$itunes_summary = wp_strip_all_tags(get_term_meta($term->term_id, 'itunes_summary', TRUE));

//RSS Feed
$xml_begin = <<<XMLBEGIN
<?xml version="1.0" encoding="UTF-8"?>
<rss
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:content="http://purl.org/rss/1.0/modules/content/"
 xmlns:itunes="http://www.itunes.com/DTDs/Podcast-1.0.dtd"
 version="2.0">
<channel>
<ttl>{$ttl}</ttl>
<title>{$feedtitle}</title>
<link>{$homeurl}</link>
<description>{$description}</description>
<language>$feedlanguage</language>
<lastBuildDate>$stamptime</lastBuildDate>
<copyright>{$copyright}</copyright>
<itunes:author>{$itunes_author}</itunes:author>
<itunes:block>{$itunes_block}</itunes:block>
{$itunescategory}
<itunes:image href="{$itunes_image}" />
<itunes:explicit>{$itunes_explicit}</itunes:explicit>
<itunes:complete>{$itunes_complete}</itunes:complete>
{$itunesnewfeedurl}
<itunes:owner>
<itunes:name>{$itunes_name}</itunes:name>
<itunes:email>{$itunes_email}</itunes:email>
</itunes:owner>
<itunes:subtitle>{$itunes_subtitle}</itunes:subtitle>
<itunes:summary>{$itunes_summary}</itunes:summary>
<generator>MediaLibrary Feeder</generator>

XMLBEGIN;

$xml_end = <<<XMLEND
</channel>
</rss>
XMLEND;
			$xml = $xml_begin.$xmlitem.$xml_end;
			$fw_sucess = FALSE;
			if ( file_exists($xmlfile)){
				if ( !strpos(file_get_contents($xmlfile), $xml) ) {
					$fno = fopen($xmlfile, 'w');
					$fw_sucess = fwrite($fno, $xml);
					fclose($fno);
				}
			}else{
				if (is_writable($this->upload_dir)) {
					$fno = fopen($xmlfile, 'w');
					$fw_sucess = fwrite($fno, $xml);
					fclose($fno);
					chmod($xmlfile, 0646);
				} else {
					_e('Could not create an RSS Feed. Please change to 777 or 757 to permissions of following directory.', 'medialibrary-feeder');
					echo '<div>'.$this->upload_url.'</div>';
				}
			}
			// Feed file & Feed widget
			if ( $fw_sucess ) {
				// Feed file
				$feedfile_tbl = array();
				if ( get_option( 'medialibraryfeeder_feedfile') ) {
					$feedfile_tbl = get_option( 'medialibraryfeeder_feedfile');
				}
				$feedfile_tbl[$term->term_id] = $xmlfile;
				update_option( 'medialibraryfeeder_feedfile', $feedfile_tbl );
				// Feed widget
				$feedwidget_tbl = array();
				if ( get_option('medialibraryfeeder_feedwidget') ) {
					$feedwidget_tbl = get_option('medialibraryfeeder_feedwidget');
					// for < version 4.00
					foreach ( $feedwidget_tbl as $key => $value ){
						if( !is_int($key) ) {
							unset($feedwidget_tbl[$key]);
						}
					}
				}
				$feedwidget_tbl[$term->term_id] = $xmlurl;
				update_option( 'medialibraryfeeder_feedwidget', $feedwidget_tbl );
			}
		}

	}

	/* ==================================================
	 * Generate FeedLink
	 * @since	1.0
	 */
	public function feed_link(){

		$feedlink = NULL;

		if ( get_option('medialibraryfeeder_feedwidget') ) {
			$feedwidget_tbl = get_option('medialibraryfeeder_feedwidget');
			$feedlink .= '<!-- Start MediaLibrary Feeder -->'."\n";
			foreach ( $feedwidget_tbl as $key => $xmlurl ) {
				$term = get_term_by('id', $key, 'mediafeed');
				$feedtitle = $term->name;
				$feedlink .= '<link rel="alternate" type="application/rss+xml" href="'.$xmlurl.'" title="'.$feedtitle.'" />'."\n";
			}
			$feedlink .= '<!-- End MediaLibrary Feeder -->'."\n";
		}

		echo $feedlink;

	}

	/* ==================================================
	 * @param	string	$suffix
	 * @return	string	$mimetype
	 * @since	1.0
	 */
	private function mime_type($suffix){

		$suffix = str_replace('.', '', $suffix);

		global $user_ID;
		$mimes = get_allowed_mime_types($user_ID);
		foreach ($mimes as $ext => $mime) {
    		if ( preg_match("/".$ext."/i", $suffix) ) {
				$mimetype = $mime;
			}
		}

		return $mimetype;

	}

	/* ==================================================
	 * Create Custom Post
	 * @param	object	$term
	 * @since	4.0
	 */
	public function create_custom_post( $term ) {

		$post_ids = get_objects_in_term($term->term_id, 'mediafeed');
		$html = NULL;
		if ( !empty($post_ids) ) {
			$pagecount = 0;
			$pagemax = get_term_meta($term->term_id, 'rssmax', TRUE);
			foreach ( $post_ids as $post_id ) {
				if ( $pagemax > $pagecount ) {
					$attachment = get_post( $post_id );
					$exts = explode('.', $attachment->guid);
					$ext = end($exts);
					$ext2type = wp_ext2type($ext);
					++$pagecount;
					$page_feed = ceil( $pagecount / $pagemax );
				}
			}
			if ( $ext2type == 'image' ) {
				$playlist_shotcode = '[gallery ids="'.implode(",", $post_ids).'" order="DESC" orderby="post_date"]';
			} else {
				$playlist_shotcode = '[playlist type="'.$ext2type.'" ids="'.implode(",", $post_ids).'" order="DESC" orderby="post_date"]';
			}
			$html .= $playlist_shotcode;
		}
		$xmlurl = $this->upload_url.'/'.get_term_meta($term->term_id, 'feedname', TRUE);
		$iconhtml = get_term_meta($term->term_id, 'iconhtml', TRUE);
		$html .= '<div align="right"><a href="'.$xmlurl.'" style="text-decoration: none;">'.stripslashes($iconhtml).'</a></div>';

		$custompost = array(
			'post_content'   => $html,
			'post_name'      => $term->slug,
			'post_title'     => $term->name,
			'post_type'      => 'mediafeed'
		);
		$post_id_from_termname = $this->check_post_id_from_term_name($term->name, 'mediafeed');
		if ( $post_id_from_termname ) {
			$custompost = array_merge( $custompost, array('ID' => $post_id_from_termname) );
			wp_update_post( $custompost );
		} else {
			$custompost_id = wp_insert_post( $custompost );
			if ( $custompost_id > 0 ) {
				$term_id_post_id_tbl = array();
				if ( get_option( 'medialibraryfeeder_term_id_post_id') ) {
					$term_id_post_id_tbl = get_option( 'medialibraryfeeder_term_id_post_id');
				}
				$term_id_post_id_tbl[$term->term_id] = $custompost_id;
				update_option( 'medialibraryfeeder_term_id_post_id', $term_id_post_id_tbl );
			}
		}

	}

	/* ==================================================
	 * Check custom post_id from term_name and taxonomy
	 * @param	string	$term_name
	 * @param	string	$taxonomy
	 * @return	int		$post_id
	 * @since	4.00
	 */
	private function check_post_id_from_term_name($term_name, $taxonomy) {

		global $wpdb;
		$post_ids = $wpdb->get_results($wpdb->prepare("
						SELECT	ID
						FROM	$wpdb->posts
						WHERE	post_title = %s
								AND post_type = %s
						",
						$term_name,
						$taxonomy
						),ARRAY_A);

		if ( !empty($post_ids[0]['ID']) ) {
			$post_id = $post_ids[0]['ID'];
		} else {
			$post_id = FALSE;
		}

		return $post_id;

	}

	/* ==================================================
	 * Add Css for icomoon
	 * @since	3.47
	 */
	public function load_custom_style() {
		wp_enqueue_style( 'icomoon-style', plugin_dir_url( __DIR__ ).'icomoon/style.css' );
	}

}

?>