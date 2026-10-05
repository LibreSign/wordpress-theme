<?php

namespace LibreSign\WordPressTheme\Tests\Unit;

use LibreSign\WordPressTheme\Tests\Support\ThemeFiles;
use PHPUnit\Framework\TestCase;

final class StructureTest extends TestCase {

	/**
	 * @dataProvider provide_theme_files
	 */
	public function test_a_file_of_the_theme_is_covered_by_the_test_named_after_it( $file ) {
		$this->assert_one_exists( self::tests_covering( $file ), $file . ' is not covered by' );
	}

	public static function provide_theme_files() {
		$files = array_merge(
			ThemeFiles::under( 'inc', '.php' ),
			ThemeFiles::under( 'src', '.php' ),
			ThemeFiles::under( 'woocommerce', '.php' )
		);

		foreach ( $files as $file ) {
			yield $file => array( $file );
		}
	}

	/**
	 * @dataProvider provide_test_files
	 */
	public function test_a_test_covers_a_file_of_the_theme( $file ) {
		$this->assert_one_exists( self::files_covered_by( $file ), $file . ' does not cover' );
	}

	public static function provide_test_files() {
		$files = array_merge(
			ThemeFiles::under( 'tests/Unit', 'Test.php' ),
			ThemeFiles::under( 'tests/Integration', 'Test.php' ),
			ThemeFiles::under( 'tests/E2E', '.spec.ts' )
		);

		foreach ( $files as $file ) {
			if ( 'tests/Unit/StructureTest.php' !== $file ) {
				yield $file => array( $file );
			}
		}
	}

	private static function tests_covering( $file ) {
		if ( str_starts_with( $file, 'src/' ) ) {
			$name = substr( $file, strlen( 'src/' ), -strlen( '.php' ) );

			return array(
				'tests/Unit/' . $name . 'Test.php',
				'tests/Integration/' . $name . 'Test.php',
			);
		}

		return array( 'tests/Integration/' . self::test_path( substr( $file, 0, -strlen( '.php' ) ) ) . 'Test.php' );
	}

	private static function files_covered_by( $file ) {
		if ( str_starts_with( $file, 'tests/E2E/' ) ) {
			$name = substr( $file, strlen( 'tests/E2E/' ), -strlen( '.spec.ts' ) );

			return array(
				'src/' . $name . '.php',
				self::theme_path( $name ) . '.php',
			);
		}

		if ( str_starts_with( $file, 'tests/Unit/' ) ) {
			return array( 'src/' . substr( $file, strlen( 'tests/Unit/' ), -strlen( 'Test.php' ) ) . '.php' );
		}

		$name = substr( $file, strlen( 'tests/Integration/' ), -strlen( 'Test.php' ) );

		return array(
			'src/' . $name . '.php',
			self::theme_path( $name ) . '.php',
		);
	}

	private function assert_one_exists( array $files, $subject ) {
		$found = array_filter(
			$files,
			static function ( $file ) {
				return file_exists( ThemeFiles::root() . '/' . $file );
			}
		);

		$this->assertNotEmpty( $found, sprintf( '%s %s.', $subject, implode( ' or ', $files ) ) );
	}

	private static function test_path( $theme_path ) {
		return implode( '/', array_map( static fn ( $part ) => str_replace( ' ', '', ucwords( str_replace( '-', ' ', $part ) ) ), explode( '/', $theme_path ) ) );
	}

	private static function theme_path( $test_path ) {
		return implode( '/', array_map( static fn ( $part ) => strtolower( (string) preg_replace( '/(?<!^)[A-Z]/', '-$0', $part ) ), explode( '/', $test_path ) ) );
	}
}
