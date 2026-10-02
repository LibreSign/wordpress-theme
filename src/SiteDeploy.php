<?php

namespace LibreSign\WordPressTheme;

final class SiteDeploy {

	public static function signature_matches( string $body, string $signature, string $secret ): bool {
		$secret    = trim( $secret );
		$signature = trim( $signature );

		if ( '' === $body || '' === $secret || '' === $signature ) {
			return false;
		}

		if ( 0 === stripos( $signature, 'sha256=' ) ) {
			$signature = substr( $signature, 7 );
		}

		if ( ! ctype_xdigit( $signature ) ) {
			return false;
		}

		return hash_equals( hash_hmac( 'sha256', $body, $secret ), strtolower( $signature ) );
	}

	public static function is_github_delivery( string $user_agent ): bool {
		return str_starts_with( trim( $user_agent ), 'GitHub-Hookshot/' );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function workflow_name( array $payload ): string {
		$run_name = self::text( $payload, 'workflow_run', 'name' );

		return '' !== $run_name ? $run_name : self::text( $payload, 'workflow', 'name' );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function is_production_deploy( array $payload, string $repository, string $branch, string $workflow ): bool {
		return self::text( $payload, 'repository', 'full_name' ) === $repository
			&& self::text( $payload, 'action' ) === 'completed'
			&& self::text( $payload, 'workflow_run', 'conclusion' ) === 'success'
			&& self::text( $payload, 'workflow_run', 'head_branch' ) === $branch
			&& self::workflow_name( $payload ) === $workflow;
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function is_deploy_starting( array $payload, string $repository ): bool {
		return self::text( $payload, 'repository', 'full_name' ) === $repository
			&& self::text( $payload, 'action' ) === 'in_progress'
			&& self::text( $payload, 'workflow_run', 'head_branch' ) === 'main'
			&& self::workflow_name( $payload ) === 'Deploy';
	}

	public static function merged_pull_request( mixed $pull_requests ): int {
		if ( ! is_array( $pull_requests ) ) {
			return 0;
		}

		foreach ( $pull_requests as $pull_request ) {
			if ( ! empty( $pull_request['merged_at'] ) ) {
				return (int) $pull_request['number'];
			}
		}

		return 0;
	}

	/**
	 * @param array<string, string[]> $synced
	 */
	public static function sync_comment( array $synced, string $origin ): string {
		$header_locales = implode( ', ', (array) ( $synced['header'] ?? array() ) );
		$footer_locales = implode( ', ', (array) ( $synced['footer'] ?? array() ) );

		return "✅ **Header and footer fragments updated in production!**\n\n" .
			"| Fragment | Synced locales |\n" .
			"|----------|----------------|\n" .
			"| Header | `{$header_locales}` |\n" .
			"| Footer | `{$footer_locales}` |\n\n" .
			"Source: {$origin}";
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private static function text( array $payload, string $key, string $subkey = '' ): string {
		$value = $payload[ $key ] ?? '';

		if ( '' !== $subkey ) {
			$value = is_array( $value ) ? ( $value[ $subkey ] ?? '' ) : '';
		}

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
