<?php
/**
 * League Match List Shortcode
 *
 * @author 		Clubpress
 * @category 	Shortcodes
 * @package 	WPClubManager/Shortcodes
 * @version     2.0.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPCM_Shortcode_Match_List {

	/**
	 * Output the standings shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {
		
		extract(shortcode_atts(array(
		), $atts));

		$title 		= ( isset( $atts['title'] ) ? $atts['title'] : '' );
		$format 	= ( isset( $atts['format'] ) ? $atts['format'] : '' );
		$limit 		= ( isset( $atts['limit'] ) ? $atts['limit'] : '' );
		$comp 		= ( isset( $atts['comp'] ) ? $atts['comp'] : '' );
		$season 	= ( isset( $atts['season'] ) ? $atts['season'] : '' );
		$team 		= ( isset( $atts['team'] ) ? $atts['team'] : '' );
		$date_range = ( isset( $atts['date_range'] ) ? $atts['date_range'] : '' );
		$order 		= ( isset( $atts['order'] ) ? $atts['order'] : '' );
		$show_thumb = ( isset( $atts['show_thumb'] ) ? $atts['show_thumb'] : 0 );
		$show_comp 	= ( isset( $atts['show_comp'] ) ? $atts['show_comp'] : 1 );
		//$show_venue = ( isset( $atts['show_venue'] ) ? $atts['show_venue'] : 1 );
		$linktext 	= ( isset( $atts['linktext'] ) ? $atts['linktext'] : '' );
		$linkpage 	= ( isset( $atts['linkpage'] ) ? $atts['linkpage'] : '' );
		//$link_club  = ( get_option( 'wpcm_match_list_link_club', 'yes' ) == 'yes' ? true : false );

		if( $limit == '' )
			$limit = -1;
		if( $comp == '' )
			$comp = null;
		if( $season == '' )
			$season = null;
		if( $team == '' )
			$team = null;
		if( $date_range == '' )
			$date_range = null;
		if( $order == '' )
			$order = 'ASC';
		if( $show_thumb == '' )
			$show_thumb = 0;
		if( $show_comp == '' )
			$show_comp = 1;
		if( $linkpage == '' )
			$linkpage = null;

		$disable_cache = get_option( 'wpcm_disable_cache' );
		if( $disable_cache === 'no') {
			$transient_name = WPCM_Cache_Helper::create_plugin_transient_name( $atts, 'league_match_list' );
			$output = get_transient( $transient_name );
		} else {
			$output = false;
		}

		if( $output === false ) {

			//$club = get_default_club();
			if( $format == '' ){
				$format = array('publish','future');
				//$order = 'ASC';
			}elseif( $format == 'fixtures' ){
				$format = 'future';
				//$order = 'ASC';
			}elseif( $format == 'results' ){
				$format = 'publish';
				//$order = 'DESC';
			}

			// get matches
			$query_args = array(
				'tax_query' => array(),
				'order' => $order,
				'orderby' => 'post_date',
				'post_type' => 'wpcm_match',
				'post_status' => $format,
				'posts_per_page' => $limit
			);

			if ( $format == 'results' ) {
				$query_args['meta_query'] = array(
					array(
						'key' => 'wpcm_played',
						'value' => false
					)
				);
			}

			if ( isset( $comp ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'wpcm_comp',
					'terms' => $comp,
					'field' => 'term_id'
				);
			}
			if ( isset( $season ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'wpcm_season',
					'terms' => $season,
					'field' => 'term_id'
				);
			}
			if ( isset( $team ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'wpcm_team',
					'terms' => $team,
					'field' => 'term_id'
				);
			}
			// if ( isset( $month ) ) {
			// 	if( $month == 'last_week' ) {
			// 		$today = getdate();
			// 		$query_args['date_query'] = array(
			// 			'column'  => 'post_date',
			// 			'before' => array(
			// 				'year'  => $today['year'],
			// 				'month' => $today['mon'],
			// 				'day'   => $today['mday'],
			// 			),
			// 			'after'   => '- 7 days'
			// 		);
			// 	}elseif( $month == 'next_week' ) {
			// 		$today = getdate();
			// 		$query_args['date_query'] = array(
			// 			'column'  => 'post_date',
			// 			'after' => array(
			// 				'year'  => $today['year'],
			// 				'month' => $today['mon'],
			// 				'day'   => $today['mday'],
			// 			),
			// 			'before'   => '+ 7 days'
			// 		);
			// 	} else {
			// 		$query_args['date_query'] = array(
			// 			'month' => $month
			// 		);
			// 	}
            // }
            if ( isset( $date_range ) ) {
                $query_args['date_query'] = array(
                    'month' => $date_range
                );
            }

			$matches = get_posts( $query_args );

			if ( $matches ) {
				ob_start();
				wpclubmanager_get_template( 'shortcodes/match-list.php', array(
					'title' 		=> $title,
					//'link_club' 	=> $link_club,
					'show_thumb' 	=> $show_thumb,
					'show_comp' 	=> $show_comp,
					//'show_venue'	=> $show_venue,
					'matches' 		=> $matches,
					'linkpage' 		=> $linkpage,
					'linktext'  	=> $linktext
				) );
				$output = ob_get_clean();

				wp_reset_postdata();
				if( $disable_cache === 'no' ) {
					set_transient( $transient_name, $output, 4*WEEK_IN_SECONDS );
					do_action('update_plugin_transient_keys', $transient_name);
				}
			}
		}

		echo $output;
	}
}