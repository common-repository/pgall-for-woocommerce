<?php


namespace CODEM\PGALL\Utility;

use WP_Query;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPUtil' ) ) {

	class WPUtil {
		static $roles = null;
		static function get( $array, $key, $default = '' ) {
			return array_key_exists( $key, $array ) ? $array[ $key ] : $default;
		}
		static function get_roles() {
			if ( is_null( self::$roles ) ) {
				self::$roles = array();

				foreach ( wp_roles()->get_names() as $role => $name ) {
					self::$roles[ $role ] = translate_user_role( $name );
				}

				self::$roles['guest'] = __( 'Guest', 'codem-wp-util' );

				self::$roles = apply_filters( 'codem_get_roles', self::$roles );
			}

			return self::$roles;
		}
		static function get_user_roles( $user_id = null ) {
			if ( empty( $user_id ) && ! is_user_logged_in() ) {
				return array( 'guest' );
			}

			$user_roles = array();

			$matched_user_roles = array();

			if ( is_null( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			if ( is_numeric( $user_id ) ) {
				$user       = new WP_User( $user_id );
				$user_roles = $user->roles;
			} else if ( $user_id instanceof WP_User ) {
				$user_roles = $user_id->roles;
			}

			if ( ! empty( $user_roles ) ) {
				$matched_user_roles = array_intersect( array_keys( self::get_roles() ), $user_roles );
			}

			return apply_filters( 'codem_get_user_roles', $matched_user_roles, $user_id );
		}
		static function get_user_role( $user_id = null ) {
			$user_roles = self::get_user_roles( $user_id );

			return apply_filters( 'codem_get_user_role', array_shift( $user_roles ), $user_id );
		}
		static function get_user_role_name( $user_role = null ) {
			if ( is_null( $user_role ) ) {
				$user_role = self::get_user_role();
			}

			return apply_filters( 'codem_get_user_role_name', self::get( self::get_roles(), $user_role ) );
		}
		static function get_user_role_names( $user_id = null ) {
			$user_roles = self::get_user_roles( $user_id );

			$user_role_names = array();

			foreach ( $user_roles as $user_role ) {
				$user_role_names[] = self::get_user_role_name( $user_role );
			}

			return apply_filters( 'codem_get_user_role_names', implode( ', ', $user_role_names ), $user_roles, $user_id );
		}
		static function get_role_options( $defaults ) {
			$results = array();

			foreach ( self::get_roles() as $role => $name ) {
				$results[] = array_merge( array(
					'role' => $role,
					'name' => $name
				), $defaults );
			}

			return $results;
		}
		public static function target_search_user() {
			$results = array();
			$args    = array();

			$keyword = isset( $_REQUEST['args'] ) ? esc_attr( sanitize_text_field( $_REQUEST['args'] ) ) : '';

			if ( ! empty( $keyword ) ) {
				$args = array(
					'search_columns' => array( 'user_login', 'user_nicename', 'display_name', 'user_email' ),
					'search'         => "*" . $keyword . "*"
				);
			}

			$users = get_users( $args );

			foreach ( $users as $user ) {
				$results[] = array(
					"value" => $user->ID,
					"name"  => $user->data->display_name . ' ( #' . $user->ID . ' - ' . $user->data->user_email . ', ' . $user->billing_last_name . $user->billing_first_name . ')'
				);
			}

			return $results;
		}
		static function target_search_menu() {
			$menu_items = wp_get_nav_menu_items( sanitize_text_field( $_REQUEST['menu'] ) );

			$results = array();
			foreach ( $menu_items as $menu_item ) {
				$results[] = array(
					"name"  => $menu_item->title,
					"value" => $menu_item->ID
				);
			}

			return $results;
		}
		static function make_taxonomy_tree( $taxonomy, $args, $depth = 0, $parent = 0, $paths = array() ) {
			$results = array();

			$args['parent'] = $parent;
			if ( $parent > 0 ) {
				unset( $args['name__like'] );
			}

			$terms = get_terms( $taxonomy, $args );

			foreach ( $terms as $term ) {
				$current_paths = array_merge( $paths, array( $term->name ) );
				$results[]     = array(
					"name"  => '<span class="tree-indicator-desc">' . implode( '-', $current_paths ) . '</span><span class="tree-indicator" style="margin-left: ' . ( $depth * 8 ) . 'px;">' . $term->name . '</span>',
					"value" => $term->term_id
				);

				$results = array_merge( $results, self::make_taxonomy_tree( $taxonomy, $args, $depth + 1, $term->term_id, $current_paths ) );
			}

			return $results;
		}
		static function target_search_taxonomy( $taxonomy ) {
			$args = array(
				'hide_empty' => false
			);

			if ( ! empty( $_REQUEST['args'] ) ) {
				$args['name__like'] = sanitize_text_field( $_REQUEST['args'] );
			}

			return self::make_taxonomy_tree( $taxonomy, $args );
		}
		static function target_search_posts_title_like( $where, &$wp_query ) {
			global $wpdb;
			if ( $posts_title = $wp_query->get( 'posts_title' ) ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE '%%%s%%'", $posts_title );
			}

			return $where;
		}
		static function target_search_page() {
			$keyword = ! empty( $_REQUEST['args'] ) ? sanitize_text_field( $_REQUEST['args'] ) : '';

			add_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ), 10, 2 );
			$args = array(
				'post_type'      => 'page',
				'posts_title'    => $keyword,
				'post_status'    => 'publish',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => -1
			);

			$query = new WP_Query( $args );

			remove_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ) );

			$results = array();

			foreach ( $query->posts as $post ) {
				$results[] = array(
					"name"  => '[#' . $post->ID . '] ' . $post->post_title,
					"value" => $post->ID
				);
			}

			return $results;
		}
		static function target_search_post( $post_type ) {
			$keyword = ! empty( $_REQUEST['args'] ) ? sanitize_text_field( $_REQUEST['args'] ) : '';

			add_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ), 10, 2 );
			$args = array(
				'post_type'      => $post_type,
				'posts_title'    => $keyword,
				'post_status'    => 'publish',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => -1
			);

			$query = new WP_Query( $args );

			remove_filter( 'posts_where', array( __CLASS__, 'target_search_posts_title_like' ) );

			$results = array();

			foreach ( $query->posts as $post ) {
				$results[] = array(
					"name"  => '[#' . $post->ID . '] ' . $post->post_title,
					"value" => $post->ID
				);
			}

			return $results;
		}
	}
}