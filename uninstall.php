<?php

	//if uninstall not called from WordPress exit
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
	    exit();

	global $wpdb;
	$option_names = array();
	$wp_options = $wpdb->get_results("
					SELECT option_name
					FROM $wpdb->options
					WHERE option_name LIKE '%%medialibraryfeeder_%%'
					");
	foreach ( $wp_options as $wp_option ) {
		$option_names[] = $wp_option->option_name;
	}

	// For Single site
	if ( !is_multisite() ) {
		medialibraryfeeder_uninstall_taxonomy_custompost();
		foreach( $option_names as $option_name ) {
		    delete_option( $option_name );
		}
	} else {
	// For Multisite
	    // For regular options.
	    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	    $original_blog_id = get_current_blog_id();
	    foreach ( $blog_ids as $blog_id ) {
	        switch_to_blog( $blog_id );
			medialibraryfeeder_uninstall_taxonomy_custompost();
			foreach( $option_names as $option_name ) {
			    delete_option( $option_name );
			}
	    }
	    switch_to_blog( $original_blog_id );

	    // For site options.
		foreach( $option_names as $option_name ) {
		    delete_site_option( $option_name );  
		}
	}


function medialibraryfeeder_uninstall_taxonomy_custompost() {

	$post_meta_arr = array(
							'medialibraryfeeder_itunes_author',
							'medialibraryfeeder_itunes_block',
							'medialibraryfeeder_itunes_image',
							'medialibraryfeeder_itunes_explicit',
							'medialibraryfeeder_itunes_isClosedCaptioned',
							'medialibraryfeeder_itunes_order',
							'medialibraryfeeder_itunes_subtitle',
							'medialibraryfeeder_itunes_summary'
							);
	$term_meta_arr = array(
							'feedname',
							'rssmax',
							'iconhtml',
							'ttl',
							'copyright',
							'itunes_author',
							'itunes_block',
							'itunes_category_1',
							'itunes_category_2',
							'itunes_category_3',
							'itunes_image',
							'itunes_explicit',
							'itunes_complete',
							'itunes_newfeedurl',
							'itunes_name',
							'itunes_email',
							'itunes_subtitle',
							'itunes_summary'
							);

	// Delete feeds
	if ( get_option( 'medialibraryfeeder_feedfile') ) {
		$feedfile_tbl = get_option('medialibraryfeeder_feedfile');
		foreach ( $feedfile_tbl as $key => $xmlfile ) {
			if ( file_exists($xmlfile)){
				unlink($xmlfile);
			}
		}
	}

	// Delete Custom Post & Delete term meta 
	// Delete term relationships with media & Delete postmeta
	if ( get_option( 'medialibraryfeeder_term_id_post_id') ) {
		$term_id_post_id_tbl = get_option( 'medialibraryfeeder_term_id_post_id');
		foreach ( $term_id_post_id_tbl as $term_id => $post_id ) {
			wp_delete_post($post_id);
			$media_ids = get_objects_in_term($term_id, 'mediafeed');
			foreach ( $media_ids as $media_id ) {
				foreach ( $post_meta_arr as $key2 ) {
					delete_post_meta($media_id, $key2);
				}
				wp_remove_object_terms((int)$media_id, (int)$term_id, 'mediafeed');
			}
			foreach ( $term_meta_arr as $key3 ) {
				delete_term_meta( $term_id, $key3 );
			}
			wp_delete_term($term_id, 'mediafeed');
		}
	}
	// unregister taxonomy
	unregister_taxonomy('mediafeed');

}
?>
