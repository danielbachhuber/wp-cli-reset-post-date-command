<?php
class WP_CLI_Reset_Post_Date_Command extends WP_CLI_Command {

	/**
	 * Reset the post_date field on your posts.
	 * A sadly necessary step after you change your timezone in WordPress
	 * 
	 * @synopsis --post_type=<post-type>
	 */
	public function __invoke( $args, $assoc_args ) {
		global $wpdb;

		$query_args = array(
				'post_type'       => $assoc_args['post_type'],
				'posts_per_page'  => -1,
				'post_status'     => 'publish',
			);
		$query = new WP_Query( $query_args );

		if ( empty( $query->posts ) )
			WP_CLI::error( "No posts found" );

		WP_CLI::line( sprintf( "Updating post_date on %d posts.", count( $query->posts ) ) );

		foreach( $query->posts as $key => $post ) {

			if ( empty( $post->post_date_gmt ) || "0000-00-00 00:00:00" == $post->post_date_gmt ) {
				WP_CLI::line( sprintf( "Error: Post %d is missing a publish date.", $post->ID ) );
				continue;
			}

			$original = $post->post_date;
			$new = get_date_from_gmt( $post->post_date_gmt );

			if ( $new == $original ) {
				WP_CLI::line( sprintf( "No Change: Post %d has the correct post_date of %s", $post->ID, $original ) );
				continue;
			}

			$wpdb->update( $wpdb->posts, array( 'post_date' => $new ), array( 'ID' => $post->ID ) );
			clean_post_cache( $post->ID );
			WP_CLI::line( sprintf( "Updated: Post %d changed from %s to %s", $post->ID, $original, $new ) );

			if ( $key && $key % 10 == 0 )
				sleep( 1 );
		}

		WP_CLI::success( "Posts were updated with the correct post_date." );

	}

}
WP_CLI::add_command( 'reset-post-date', 'WP_CLI_Reset_Post_Date_Command' );