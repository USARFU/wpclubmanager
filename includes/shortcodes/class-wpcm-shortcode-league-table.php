<?php
/**
 * League Table Shortcode
 *
 * @author 		Clubpress
 * @category 	Shortcodes
 * @package 	WPClubManager/Shortcodes
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPCM_Shortcode_League_Table {

	/**
	 * Output the standings shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {

		extract(shortcode_atts(array(
		), $atts));

        $id 		= ( isset( $atts['id'] ) ? $atts['id'] : null );
        $title 		= ( isset( $atts['title'] ) ? $atts['title'] : '');
        $limit 		= ( isset( $atts['limit'] ) ? $atts['limit'] : '' );
		$thumb 		= ( isset( $atts['thumb'] ) ? $atts['thumb'] : 1 );
		$link_club  = ( isset( $atts['link_club'] ) ? $atts['link_club'] : 1 );
        $type 		= ( isset( $atts['type'] ) ? $atts['type'] : '' );
        $notes 	    = ( isset( $atts['notes'] ) ? $atts['notes'] : 0 );
        $columns 	= ( isset( $atts['columns'] ) ? $atts['columns'] : get_option( 'wpcm_standings_columns_display' ) );
        $linktext 	= ( isset( $atts['linktext'] ) ? $atts['linktext'] : '' );
		$linkpage 	= ( isset( $atts['linkpage'] ) ? $atts['linkpage'] : '' );

        if( $linkpage == '' )
            $linkpage = null;
        if( $columns == '' )
            $columns = get_option( 'wpcm_standings_columns_display' );
        if( $thumb == '' )
            $thumb = 1;
        if( $link_club == '' )
            $link_club = 1;
        if( $notes == '' )
            $notes = 0;

		$disable_cache = get_option( 'wpcm_disable_cache' );
		if( $disable_cache === 'no' && $type !== 'widget' ) {
			$transient_name = WPCM_Cache_Helper::create_plugin_transient_name( $atts, 'league_table' );
			$output = get_transient( $transient_name );
		} else {
			$output = false;
		}

		if( $output === false ) {

            $default_club = get_default_club();
            $team_label = null;
            if( is_club_mode() ) {
                $teams = get_the_terms( $id, 'wpcm_team' );
                $team_id = $teams[0]->term_id;
                $team_label = wpcm_get_team_name( $default_club, $team_id );
            }
            $comps = get_the_terms( $id, 'wpcm_comp' );
            $comp = $comps[0]->term_id;
            $seasons = get_the_terms( $id, 'wpcm_season' );
            $season = $seasons[0]->term_id;
            $manual_stats = (array)unserialize( get_post_meta( $id, '_wpcm_table_stats', true ) );
            $selected_clubs = (array)unserialize( get_post_meta( $id, '_wpcm_table_clubs', true ) );
            //$columns = get_option( 'wpcm_standings_columns_display' );
            $columns = explode( ',', $columns );
            $order = get_option( 'wpcm_standings_order' );
            $notes = get_post_meta( $id, '_wpcm_table_notes', true );
            
            $args = array(
                'post_type' => 'wpcm_club',
                'tax_query' => array(),
                'numberposts' => -1,
                'posts_per_page' => -1,
                'post__in' => $selected_clubs
            );
            $clubs = get_posts( $args );

            $size = sizeof( $clubs );
               
            foreach ( $clubs as $club ) {
                
                $auto_stats = get_wpcm_club_auto_stats( $club->ID, $comp, $season );
                $club->wpcm_stats = $auto_stats;
                if( array_key_exists( $club->ID, $manual_stats ) ) {
                    $club->wpcm_manual_stats = $manual_stats[$club->ID];
                    $club->wpcm_auto_stats = $auto_stats;
                    $total_stats = get_wpcm_table_total_stats( $club->ID, $comp, $season, $manual_stats[$club->ID] );	
                    $club->wpcm_stats = $total_stats;
                }
                if ( $thumb == 1 ) {
					if ( has_post_thumbnail( $club->ID ) ) {
						$club->thumb = get_the_post_thumbnail( $club->ID, 'crest-small' );
					} else {
						$club->thumb = wpcm_crest_placeholder_img( 'crest-small' );
					}
				} else {
					$club->thumb = '';
                }
            }

            //usort( $clubs, 'wpcm_club_standings_sort');
            usort( $clubs, 'wpcm_sort_table_clubs');

            if ( $order == 'ASC' ) {
                $clubs = array_reverse( $clubs );
            }

            foreach ( $clubs as $key => $value ) {	
                $value->place = $key + 1;
            }

            if ( !isset( $default_club ) ) {
                $default_club = $clubs[0]->ID;
            }
            
            if ( $limit == '' ) {
                $limit = $size;
            }

            if ( $limit < $size ) {
                $middle = 0;
                foreach( $clubs as $key => $value ) {
                    if ( $value->ID == $default_club ) $middle = $key;
                }
                $before = floor( ( $limit - 1 ) / 2 );
                $first = $middle - $before;
                $actual = $size - $first;
                if ( $actual < $limit ) {
                    $first -= ( $limit - $actual );
                }
                if ( $first < 0 ) {
                    $first = 0;
                }
            } else {
                $first = 0;
                $limit = $size;
            }

            $clubs = array_slice( $clubs, $first, $limit );

            $stats_labels = wpcm_get_preset_labels( 'standings', 'label' );

			ob_start();
			wpclubmanager_get_template( 'shortcodes/league-table.php', array(
                'id'            => $id,
				'type' 			=> $type,
				'title' 		=> $title,
				'clubs' 		=> $clubs,
				'columns' 		=> $columns,
                'link_club' 	=> $link_club,
                'stats_labels'  => $stats_labels,
                'team_label'    => $team_label,
                'default_club'  => $default_club,
                'notes'         => $notes,
                'linkpage' 		=> $linkpage,
				'linktext'  	=> $linktext
			) );
			$output = ob_get_clean();

			wp_reset_postdata();
			if( $disable_cache === 'no' && $type !== 'widget') {
				set_transient( $transient_name, $output, 4*WEEK_IN_SECONDS );
				do_action('update_plugin_transient_keys', $transient_name);
			}
		}

		echo $output;
	}
}