<?php
/**
 * MediaLibrary Feeder
 * 
 * @package    MediaLibrary Feeder
 * @subpackage MediaLibraryFeederRegist registered in the database
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

$medialibraryfeederregist = new MediaLibraryFeederRegist();

class MediaLibraryFeederRegist {

	/* ==================================================
	 * Construct
	 * @since	4.06
	 */
	public function __construct() {

		add_action( 'admin_init', array($this, 'itunes_categories') );
		add_action( 'init', array($this, 'mediafeed_taxonomies'), 9 );
		add_action( 'init', array($this, 'update_mediafeed_term_count'), 10 );
		add_action( 'init', array($this, 'create_mediafeed_posttype'), 11 );
		add_filter( 'init', array($this, 'add_rewrite_rules') );

	}

	/* ==================================================
	 * itunes Categories
	 * @since	4.00
	 */
	public function itunes_categories() {

		$itunes_categories = array(
'Arts' => '<itunes:category text="Arts" />',
'Arts - Design' => '<itunes:category text="Arts"><itunes:category text="Design" /></itunes:category>',
'Arts - Fashion & Beauty' => '<itunes:category text="Arts"><itunes:category text="Fashion & Beauty" /></itunes:category>',
'Arts - Food' => '<itunes:category text="Arts"><itunes:category text="Food" /></itunes:category>',
'Arts - Literature' => '<itunes:category text="Arts"><itunes:category text="Literature" /></itunes:category>',
'Arts - Performing Arts' => '<itunes:category text="Arts"><itunes:category text="Performing Arts" /></itunes:category>',
'Arts - Visual Arts' => '<itunes:category text="Arts"><itunes:category text="Visual Arts" /></itunes:category>',
'Business' => '<itunes:category text="Business" />',
'Business - Business News' => '<itunes:category text="Business"><itunes:category text="Business News" /></itunes:category>',
'Business - Careers' => '<itunes:category text="Business"><itunes:category text="Careers" /></itunes:category>',
'Business - Investing' => '<itunes:category text="Business"><itunes:category text="Investing" /></itunes:category>',
'Business - Management & Marketing' => '<itunes:category text="Business"><itunes:category text="Management & Marketing" /></itunes:category>',
'Business - Shopping' => '<itunes:category text="Business"><itunes:category text="Shopping" /></itunes:category>',
'Comedy' => '<itunes:category text="Comedy" />',
'Education' => '<itunes:category text="Education" />',
'Education - Education' => '<itunes:category text="Education"><itunes:category text="Education" /></itunes:category>',
'Education - Education Technology' => '<itunes:category text="Education"><itunes:category text="Education Technology" /></itunes:category>',
'Education - Higher Education' => '<itunes:category text="Education"><itunes:category text="Higher Education" /></itunes:category>',
'Education - K-12' => '<itunes:category text="Education"><itunes:category text="K-12" /></itunes:category>',
'Education - Language Courses' => '<itunes:category text="Education"><itunes:category text="Language Courses" /></itunes:category>',
'Education - Training' => '<itunes:category text="Education"><itunes:category text="Training" /></itunes:category>',
'Games & Hobbies' => '<itunes:category text="Games & Hobbies" />',
'Games & Hobbies - Automotive' => '<itunes:category text="Games & Hobbies"><itunes:category text="Automotive" /></itunes:category>',
'Games & Hobbies - Aviation' => '<itunes:category text="Games & Hobbies"><itunes:category text="Aviation" /></itunes:category>',
'Games & Hobbies - Hobbies' => '<itunes:category text="Games & Hobbies"><itunes:category text="Hobbies" /></itunes:category>',
'Games & Hobbies - Other Games' => '<itunes:category text="Games & Hobbies"><itunes:category text="Other Games" /></itunes:category>',
'Games & Hobbies - Video Games' => '<itunes:category text="Games & Hobbies"><itunes:category text="Video Games" /></itunes:category>',
'Government & Organizations' => '<itunes:category text="Government & Organizations" />',
'Government & Organizations - Local' => '<itunes:category text="Government & Organizations"><itunes:category text="Local" /></itunes:category>',
'Government & Organizations - National' => '<itunes:category text="Government & Organizations"><itunes:category text="National" /></itunes:category>',
'Government & Organizations - Non-Profit' => '<itunes:category text="Government & Organizations"><itunes:category text="Non-Profit" /></itunes:category>',
'Government & Organizations - Regional' => '<itunes:category text="Government & Organizations"><itunes:category text="Regional" /></itunes:category>',
'Health' => '<itunes:category text="Health" />',
'Health - Alternative Health' => '<itunes:category text="Health"><itunes:category text="Alternative Health" /></itunes:category>',
'Health - Fitness & Nutrition' => '<itunes:category text="Health"><itunes:category text="Fitness & Nutrition" /></itunes:category>',
'Health - Self-Help' => '<itunes:category text="Health"><itunes:category text="Self-Help" /></itunes:category>',
'Health - Sexuality' => '<itunes:category text="Health"><itunes:category text="Sexuality" /></itunes:category>',
'Kids & Family' => '<itunes:category text="Kids & Family" />',
'Music' => '<itunes:category text="Music" />',
'News & Politics' => '<itunes:category text="News & Politics" />',
'Religion & Spirituality' => '<itunes:category text="Religion & Spirituality" />',
'Religion & Spirituality - Buddhism' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Buddhism" /></itunes:category>',
'Religion & Spirituality - Christianity' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Christianity" /></itunes:category>',
'Religion & Spirituality - Hinduism' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Hinduism" /></itunes:category>',
'Religion & Spirituality - Islam' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Islam" /></itunes:category>',
'Religion & Spirituality - Judaism' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Judaism" /></itunes:category>',
'Religion & Spirituality - Other' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Other" /></itunes:category>',
'Religion & Spirituality - Spirituality' => '<itunes:category text="Religion & Spirituality"><itunes:category text="Spirituality" /></itunes:category>',
'Science & Medicine' => '<itunes:category text="Science & Medicine" />',
'Science & Medicine - Medicine' => '<itunes:category text="Science & Medicine"><itunes:category text="Medicine" /></itunes:category>',
'Science & Medicine - Natural Sciences' => '<itunes:category text="Science & Medicine"><itunes:category text="Natural Sciences" /></itunes:category>',
'Science & Medicine - Social Sciences' => '<itunes:category text="Science & Medicine"><itunes:category text="Social Sciences" /></itunes:category>',
'Society & Culture' => '<itunes:category text="Society & Culture" />',
'Society & Culture - History' => '<itunes:category text="Society & Culture"><itunes:category text="History" /></itunes:category>',
'Society & Culture - Personal Journals' => '<itunes:category text="Society & Culture"><itunes:category text="Personal Journals" /></itunes:category>',
'Society & Culture - Philosophy' => '<itunes:category text="Society & Culture"><itunes:category text="Philosophy" /></itunes:category>',
'Society & Culture - Places & Travel' => '<itunes:category text="Society & Culture"><itunes:category text="Places & Travel" /></itunes:category>',
'Sports & Recreation' => '<itunes:category text="Sports & Recreation" />',
'Sports & Recreation - Amateur' => '<itunes:category text="Sports & Recreation"><itunes:category text="Amateur" /></itunes:category>',
'Sports & Recreation - College & High School' => '<itunes:category text="Sports & Recreation"><itunes:category text="College & High School" /></itunes:category>',
'Sports & Recreation - Outdoor' => '<itunes:category text="Sports & Recreation"><itunes:category text="Outdoor" /></itunes:category>',
'Sports & Recreation - Professional' => '<itunes:category text="Sports & Recreation"><itunes:category text="Professional" /></itunes:category>',
'Technology' => '<itunes:category text="Technology" />',
'Technology - Gadgets' => '<itunes:category text="Technology"><itunes:category text="Gadgets" /></itunes:category>',
'Technology - Tech News' => '<itunes:category text="Technology"><itunes:category text="Tech News" /></itunes:category>',
'Technology - Podcasting' => '<itunes:category text="Technology"><itunes:category text="Podcasting" /></itunes:category>',
'Technology - Software How-To' => '<itunes:category text="Technology"><itunes:category text="Software How-To" /></itunes:category>',
'TV & Film' => '<itunes:category text="TV & Film" />'
		);

		if ( !get_option('medialibrary_feeder_itunes_categories') ) {
			update_option('medialibrary_feeder_itunes_categories', json_encode($itunes_categories));
		}

		// for GlotPress.
		$glotpress = __('Arts', 'medialibrary-feeder');
		$glotpress = __('Arts - Design', 'medialibrary-feeder');
		$glotpress = __('Arts - Fashion & Beauty', 'medialibrary-feeder');
		$glotpress = __('Arts - Food', 'medialibrary-feeder');
		$glotpress = __('Arts - Literature', 'medialibrary-feeder');
		$glotpress = __('Arts - Performing Arts', 'medialibrary-feeder');
		$glotpress = __('Arts - Visual Arts', 'medialibrary-feeder');
		$glotpress = __('Business', 'medialibrary-feeder');
		$glotpress = __('Business - Business News', 'medialibrary-feeder');
		$glotpress = __('Business - Careers', 'medialibrary-feeder');
		$glotpress = __('Business - Investing', 'medialibrary-feeder');
		$glotpress = __('Business - Management & Marketing', 'medialibrary-feeder');
		$glotpress = __('Business - Shopping', 'medialibrary-feeder');
		$glotpress = __('Comedy', 'medialibrary-feeder');
		$glotpress = __('Education', 'medialibrary-feeder');
		$glotpress = __('Education - Education', 'medialibrary-feeder');
		$glotpress = __('Education - Education Technology', 'medialibrary-feeder');
		$glotpress = __('Education - Higher Education', 'medialibrary-feeder');
		$glotpress = __('Education - K-12', 'medialibrary-feeder');
		$glotpress = __('Education - Language Courses', 'medialibrary-feeder');
		$glotpress = __('Education - Training', 'medialibrary-feeder');
		$glotpress = __('Games & Hobbies', 'medialibrary-feeder');
		$glotpress = __('Games & Hobbies - Automotive', 'medialibrary-feeder');
		$glotpress = __('Games & Hobbies - Aviation', 'medialibrary-feeder');
		$glotpress = __('Games & Hobbies - Hobbies', 'medialibrary-feeder');
		$glotpress = __('Games & Hobbies - Other Games', 'medialibrary-feeder');
		$glotpress = __('Games & Hobbies - Video Games', 'medialibrary-feeder');
		$glotpress = __('Government & Organizations', 'medialibrary-feeder');
		$glotpress = __('Government & Organizations - Local', 'medialibrary-feeder');
		$glotpress = __('Government & Organizations - National', 'medialibrary-feeder');
		$glotpress = __('Government & Organizations - Non-Profit', 'medialibrary-feeder');
		$glotpress = __('Government & Organizations - Regional', 'medialibrary-feeder');
		$glotpress = __('Health', 'medialibrary-feeder');
		$glotpress = __('Health - Alternative Health', 'medialibrary-feeder');
		$glotpress = __('Health - Fitness & Nutrition', 'medialibrary-feeder');
		$glotpress = __('Health - Self-Help', 'medialibrary-feeder');
		$glotpress = __('Health - Sexuality', 'medialibrary-feeder');
		$glotpress = __('Kids & Family', 'medialibrary-feeder');
		$glotpress = __('Music', 'medialibrary-feeder');
		$glotpress = __('News & Politics', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Buddhism', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Christianity', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Hinduism', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Islam', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Judaism', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Other', 'medialibrary-feeder');
		$glotpress = __('Religion & Spirituality - Spirituality', 'medialibrary-feeder');
		$glotpress = __('Science & Medicine', 'medialibrary-feeder');
		$glotpress = __('Science & Medicine - Medicine', 'medialibrary-feeder');
		$glotpress = __('Science & Medicine - Natural Sciences', 'medialibrary-feeder');
		$glotpress = __('Science & Medicine - Social Sciences', 'medialibrary-feeder');
		$glotpress = __('Society & Culture', 'medialibrary-feeder');
		$glotpress = __('Society & Culture - History', 'medialibrary-feeder');
		$glotpress = __('Society & Culture - Personal Journals', 'medialibrary-feeder');
		$glotpress = __('Society & Culture - Philosophy', 'medialibrary-feeder');
		$glotpress = __('Society & Culture - Places & Travel', 'medialibrary-feeder');
		$glotpress = __('Sports & Recreation', 'medialibrary-feeder');
		$glotpress = __('Sports & Recreation - Amateur', 'medialibrary-feeder');
		$glotpress = __('Sports & Recreation - College & High School', 'medialibrary-feeder');
		$glotpress = __('Sports & Recreation - Outdoor', 'medialibrary-feeder');
		$glotpress = __('Sports & Recreation - Professional', 'medialibrary-feeder');
		$glotpress = __('Technology', 'medialibrary-feeder');
		$glotpress = __('Technology - Gadgets', 'medialibrary-feeder');
		$glotpress = __('Technology - Tech News', 'medialibrary-feeder');
		$glotpress = __('Technology - Podcasting', 'medialibrary-feeder');
		$glotpress = __('Technology - Software How-To', 'medialibrary-feeder');
		$glotpress = __('TV & Film', 'medialibrary-feeder');
		// for GlotPress.

	}

	/* ==================================================
	 * Register Taxonomy
	 * @since	4.00
	 */
	public function mediafeed_taxonomies() {

		$labels = array(
			'name'              => __( 'Feeds Management', 'medialibrary-feeder' ),
			'singular_name'     => __( 'Feed', 'medialibrary-feeder' ),
			'search_items'      => __( 'Search Feeds', 'medialibrary-feeder' ),
			'all_items'         => __( 'All Feeds', 'medialibrary-feeder' ),
			'parent_item'       => __( 'Parent Feed', 'medialibrary-feeder' ),
			'parent_item_colon' => __( 'Parent Feed:', 'medialibrary-feeder' ),
			'edit_item'         => __( 'Edit Feed', 'medialibrary-feeder' ),
			'update_item'       => __( 'Update Feed', 'medialibrary-feeder' ),
			'add_new_item'      => __( 'Add New Feed', 'medialibrary-feeder' ),
			'new_item_name'     => __( 'New Feed Name', 'medialibrary-feeder' ),
			'menu_name'         => __( 'Feeds Management', 'medialibrary-feeder' )
		);

		$args = array(
			'hierarchical'      	=> false,
			'labels'            	=> $labels,
			'show_ui'           	=> true,
			'show_admin_column'		=> false,
			'query_var'         	=> true,
			'meta_box_cb'			=> false,
			'rewrite' 				=> array( 'slug' => 'mediafeed' )
		);
		register_taxonomy( 'mediafeed', 'attachment', $args);

	}

	/* ==================================================
	 * Custom term count
	 * @since	4.03
	 */
	public function update_mediafeed_term_count() {

		$terms = get_terms( 'mediafeed', array( 'hide_empty' => false ) );

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			global $wpdb;
			foreach ( $terms as $term ) {

				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term->term_id ) );

				do_action( 'edit_term_taxonomy', $term->term_id, 'mediafeed' );
				$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term->term_id ) );

				do_action( 'edited_term_taxonomy', $term->term_id, 'mediafeed' );
			}
		}

	}

	/* ==================================================
	 * Custom Post
	 * @since	4.00
	 */
	public function create_mediafeed_posttype() {
		register_post_type( 'mediafeed',
			array(
				'labels' => array(
				'name' => __( 'Feeds', 'medialibrary-feeder' ),
				'singular_name' => __( 'Feed', 'medialibrary-feeder' )
			),
			'public' => true,
			'supports' => array(
						'title',
						'editor',
						'author',
						'comments'
						),
			'has_archive' => true,
			'rewrite' => array( 'slug' => 'mediafeed' )
			)
		);
	}

	/* ==================================================
	 * Rewruit rule
	 * @since	4.00
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule('^mediafeed/([0-9]+)/?', 'index.php?page_id=$matches[1]', 'top');
	} 

}

?>