<?php
/**
 * Facebook Feed Service
 *
 * Fetches posts from Facebook Graph API with caching and fallback.
 *
 * @package CustomBlockPackage
 */

declare(strict_types=1);

namespace CustomBlockPackage\Services;

use CustomBlockPackage\Cache\BlockCache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FacebookFeedService
 *
 * Provides methods to fetch, cache, and retrieve Facebook Page posts
 * via the Graph API with graceful degradation on failures.
 */
class FacebookFeedService {

	/**
	 * Graph API version.
	 *
	 * @var string
	 */
	private const API_VERSION = 'v19.0';

	/**
	 * Maximum posts to fetch per API call.
	 *
	 * @var int
	 */
	private const MAX_POSTS = 50;

	/**
	 * Option key for page ID.
	 */
	public const OPTION_PAGE_ID = 'cbp_fb_page_id';

	/**
	 * Option key for access token.
	 */
	public const OPTION_ACCESS_TOKEN = 'cbp_fb_access_token';

	/**
	 * Option key for cache TTL in seconds.
	 */
	public const OPTION_CACHE_TTL = 'cbp_fb_cache_ttl';

	/**
	 * Option key for last successful sync timestamp.
	 */
	public const OPTION_LAST_SYNC = 'cbp_fb_last_sync';

	/**
	 * Option key for last error message.
	 */
	public const OPTION_LAST_ERROR = 'cbp_fb_last_error';

	/**
	 * Option key for backup posts (never expires, used as fallback).
	 */
	public const OPTION_BACKUP_POSTS = 'cbp_fb_backup_posts';

	/**
	 * Default cache TTL: 2 hours.
	 */
	public const DEFAULT_TTL = 2 * HOUR_IN_SECONDS;

	/**
	 * Get posts from cache, refresh if stale.
	 *
	 * @param int $limit Number of posts to return.
	 * @return array<int, array<string, mixed>> Array of post data.
	 */
	public function get_posts( int $limit = 10 ): array {
		return $this->get_posts_range( 0, $limit );
	}

	/**
	 * Get slice of cached posts by offset + limit (for pagination / infinite scroll).
	 *
	 * @param int $offset Starting index.
	 * @param int $limit  Number of posts to return.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_posts_range( int $offset = 0, int $limit = 10 ): array {
		$offset = max( 0, $offset );
		$limit  = max( 1, min( $limit, self::MAX_POSTS ) );

		$all = $this->get_all_cached();

		return array_slice( $all, $offset, $limit );
	}

	/**
	 * Get total number of cached posts.
	 *
	 * @return int
	 */
	public function get_total_count(): int {
		return count( $this->get_all_cached() );
	}

	/**
	 * Get the full cached post list (transient or backup fallback).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_all_cached(): array {
		$transient_key = BlockCache::FACEBOOK_FEED_PREFIX . 'all';
		$cached        = get_transient( $transient_key );

		if ( false === $cached ) {
			$this->refresh();
			$cached = get_transient( $transient_key );
		}

		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$backup = get_option( self::OPTION_BACKUP_POSTS, array() );
		return is_array( $backup ) ? $backup : array();
	}

	/**
	 * Load mock posts into cache for UI testing without a real token.
	 *
	 * @return bool
	 */
	public function load_mock_data(): bool {
		$posts = array();
		$now   = time();

		for ( $i = 0; $i < 30; $i++ ) {
			$posts[] = array(
				'id'            => 'mock_' . $i,
				'message'       => sprintf(
					/* translators: %d: mock post index */
					__( 'Przykładowy post numer %d — to jest testowa treść używana do podglądu wyglądu feedu przed podłączeniem prawdziwego tokenu. Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'custom-block-package' ),
					$i + 1
				),
				'created_time'  => gmdate( 'c', $now - ( $i * DAY_IN_SECONDS ) ),
				'permalink_url' => 'https://www.facebook.com/',
				'image'         => 'https://picsum.photos/seed/fb' . $i . '/800/450',
			);
		}

		$ttl           = (int) get_option( self::OPTION_CACHE_TTL, self::DEFAULT_TTL );
		$transient_key = BlockCache::FACEBOOK_FEED_PREFIX . 'all';
		set_transient( $transient_key, $posts, $ttl );
		update_option( self::OPTION_BACKUP_POSTS, $posts );
		update_option( self::OPTION_LAST_SYNC, time() );
		update_option( self::OPTION_LAST_ERROR, '' );

		return true;
	}

	/**
	 * Fetch fresh posts from Graph API and update cache.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function refresh(): bool {
		$page_id = (string) get_option( self::OPTION_PAGE_ID, '' );
		$token   = (string) get_option( self::OPTION_ACCESS_TOKEN, '' );

		if ( '' === $page_id || '' === $token ) {
			update_option( self::OPTION_LAST_ERROR, __( 'Page ID or access token not configured.', 'custom-block-package' ) );
			return false;
		}

		$response = $this->call_api( $page_id, $token );

		if ( is_wp_error( $response ) ) {
			update_option( self::OPTION_LAST_ERROR, $response->get_error_message() );
			return false;
		}

		$posts = $this->parse_response( $response );

		if ( empty( $posts ) ) {
			update_option( self::OPTION_LAST_ERROR, __( 'API returned no posts.', 'custom-block-package' ) );
			return false;
		}

		$ttl = (int) get_option( self::OPTION_CACHE_TTL, self::DEFAULT_TTL );
		if ( $ttl < MINUTE_IN_SECONDS ) {
			$ttl = self::DEFAULT_TTL;
		}

		$transient_key = BlockCache::FACEBOOK_FEED_PREFIX . 'all';
		set_transient( $transient_key, $posts, $ttl );
		update_option( self::OPTION_BACKUP_POSTS, $posts );
		update_option( self::OPTION_LAST_SYNC, time() );
		update_option( self::OPTION_LAST_ERROR, '' );

		return true;
	}

	/**
	 * Test connection with current credentials.
	 *
	 * @return array{success: bool, message: string, posts_count: int}
	 */
	public function test_connection(): array {
		$page_id = (string) get_option( self::OPTION_PAGE_ID, '' );
		$token   = (string) get_option( self::OPTION_ACCESS_TOKEN, '' );

		if ( '' === $page_id || '' === $token ) {
			return array(
				'success'     => false,
				'message'     => __( 'Page ID or access token not set. Save them first.', 'custom-block-package' ),
				'posts_count' => 0,
			);
		}

		$response = $this->call_api( $page_id, $token );

		if ( is_wp_error( $response ) ) {
			return array(
				'success'     => false,
				'message'     => $response->get_error_message(),
				'posts_count' => 0,
			);
		}

		$posts = $this->parse_response( $response );

		return array(
			'success'     => true,
			'message'     => sprintf(
				/* translators: %d: number of posts fetched */
				__( 'Connection successful. Retrieved %d posts.', 'custom-block-package' ),
				count( $posts )
			),
			'posts_count' => count( $posts ),
		);
	}

	/**
	 * Make HTTP GET request to Graph API.
	 *
	 * @param string $page_id Facebook page ID or username.
	 * @param string $token   Page access token.
	 * @return array<string, mixed>|\WP_Error Response body array or error.
	 */
	private function call_api( string $page_id, string $token ) {
		$url = sprintf(
			'https://graph.facebook.com/%s/%s/posts',
			self::API_VERSION,
			rawurlencode( $page_id )
		);

		$url = add_query_arg(
			array(
				'fields'       => 'message,created_time,permalink_url,full_picture,attachments{media,subattachments,type}',
				'limit'        => self::MAX_POSTS,
				'access_token' => $token,
			),
			$url
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 3,
				'headers'     => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status ) {
			$error_message = isset( $data['error']['message'] )
				? (string) $data['error']['message']
				: sprintf(
					/* translators: %d: HTTP status code */
					__( 'API returned status %d', 'custom-block-package' ),
					$status
				);
			return new \WP_Error( 'fb_api_error', $error_message );
		}

		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'fb_api_invalid', __( 'Invalid JSON response from API.', 'custom-block-package' ) );
		}

		return $data;
	}

	/**
	 * Normalize API response into simple post array.
	 *
	 * @param array<string, mixed> $response Raw API response.
	 * @return array<int, array<string, mixed>> Simplified posts.
	 */
	private function parse_response( array $response ): array {
		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return array();
		}

		$posts = array();

		foreach ( $response['data'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$posts[] = array(
				'id'            => isset( $item['id'] ) ? (string) $item['id'] : '',
				'message'       => isset( $item['message'] ) ? (string) $item['message'] : '',
				'created_time'  => isset( $item['created_time'] ) ? (string) $item['created_time'] : '',
				'permalink_url' => isset( $item['permalink_url'] ) ? (string) $item['permalink_url'] : '',
				'image'         => isset( $item['full_picture'] ) ? (string) $item['full_picture'] : '',
			);
		}

		return $posts;
	}
}
