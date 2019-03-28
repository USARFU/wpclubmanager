<?php
/**
 * WPClubManager Club Functions.
 *
 * Functions for clubs.
 *
 * @author 		ClubPress
 * @category 	Core
 * @package 	WPClubManager/Functions
 * @version     2.0.5
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wpcm_head_to_heads( $post_id ) {
	
	$club = get_default_club();
	
	$args = array(
		'post_type' => 'wpcm_match',
		'post_per_page' => -1,
		'meta_query' => array(
			array(
				'relation' => 'OR',
				array(
					'key' => 'wpcm_home_club',
					'value' => $post_id
				),
				array(
					'key' => 'wpcm_away_club',
					'value' => $post_id
				)
			),
			array(
				'relation' => 'OR',
				array(
					'key' => 'wpcm_home_club',
					'value' => $club
				),
				array(
					'key' => 'wpcm_away_club',
					'value' => $club
				),
			),
		)
	);

	$matches = get_posts( $args );

	return $matches;
}

function wpcm_head_to_head_count( $matches ) {

	$club = get_default_club();
	$wins = 0;
	$losses = 0;
	$draws = 0;
	$count = 0;
	foreach( $matches as $match ) {

		if( get_post_meta( $match->ID, '_wpcm_postponed', true ) != '1' && get_post_meta( $match->ID, '_wpcm_walkover', true ) == '' ) {

			$count ++;
			$home_club = get_post_meta( $match->ID, 'wpcm_home_club', true );
			$home_goals = get_post_meta( $match->ID, 'wpcm_home_goals', true );
			$away_goals = get_post_meta( $match->ID, 'wpcm_away_goals', true );

			if ( $home_goals == $away_goals ) {
				$draws ++;
			}

			if ( $club == $home_club ) {
				if ( $home_goals > $away_goals ) {
					$wins ++;
				}
				if ( $home_goals < $away_goals ) {
					$losses ++;
				}
			} else {
				if ( $home_goals > $away_goals ) {
					$losses ++;
				}
				if ( $home_goals < $away_goals ) {
					$wins ++;
				}
			}
		}

	}
	$outcome = array();
	$outcome['total'] = $count;
	$outcome['wins'] = $wins;
	$outcome['draws'] = $draws;
	$outcome['losses'] = $losses;

	return apply_filters( 'wpcm_head_to_head_count', $outcome, $matches );

}

function get_club_venue( $post ) {

	$id = get_the_terms( $post, 'wpcm_venue' );
	
	if ( is_array( $id ) ) {
		$venues = reset($id);
		$t_id = $venues->term_id; 
		$venue_meta = get_option( "taxonomy_term_$t_id" );
		$venue['id'] = $t_id;
		$venue['name'] = $venues->name;
		if( is_array( $venue_meta ) ) {
			if( array_key_exists('wpcm_address', $venue_meta) ) {
				$venue['address'] = $venue_meta['wpcm_address'];
			}
			if( array_key_exists('wpcm_capacity', $venue_meta) ) {
				$venue['capacity'] = $venue_meta['wpcm_capacity'];
			}
		}
		$venue['description'] = $venues->description;
	}
	 return $venue;
}
	