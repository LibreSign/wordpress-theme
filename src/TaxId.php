<?php

namespace LibreSign\WordPressTheme;

final class TaxId {

	public static function error_code( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return 'libresign_cpf_cnpj_required';
		}

		if ( 14 === strlen( self::without_formatting( $value ) ) ) {
			return self::valid_cnpj( $value ) ? '' : 'invalid_cnpj';
		}

		if ( 11 === strlen( self::digits( $value ) ) ) {
			return self::valid_cpf( $value ) ? '' : 'invalid_cpf';
		}

		return 'invalid_cpf_cnpj';
	}

	public static function valid_cpf( string $cpf ): bool {
		$cpf = self::digits( $cpf );
		if ( strlen( $cpf ) !== 11 || preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
			return false;
		}

		$check_digit = static function ( string $digits, int $weight ): int {
			$sum = 0;
			for ( $position = 0; $position < strlen( $digits ); $position++ ) {
				$sum += (int) $digits[ $position ] * $weight--;
			}
			$remainder = $sum % 11;

			return $remainder < 2 ? 0 : 11 - $remainder;
		};

		return $check_digit( substr( $cpf, 0, 9 ), 10 ) === (int) $cpf[9]
			&& $check_digit( substr( $cpf, 0, 10 ), 11 ) === (int) $cpf[10];
	}

	public static function valid_cnpj( string $cnpj ): bool {
		$cnpj = self::without_formatting( $cnpj );
		if ( strlen( $cnpj ) !== 14 || preg_match( '/^(\d)\1{13}$/', $cnpj ) || ! preg_match( '/^[A-Z0-9]{12}\d{2}$/', $cnpj ) ) {
			return false;
		}

		$check_digit = static function ( string $characters ): int {
			$sum    = 0;
			$weight = 2;
			for ( $position = strlen( $characters ) - 1; $position >= 0; $position-- ) {
				$sum   += ( ord( $characters[ $position ] ) - 48 ) * $weight;
				$weight = 9 === $weight ? 2 : $weight + 1;
			}
			$remainder = $sum % 11;

			return $remainder < 2 ? 0 : 11 - $remainder;
		};

		return $check_digit( substr( $cnpj, 0, 12 ) ) === (int) $cnpj[12]
			&& $check_digit( substr( $cnpj, 0, 13 ) ) === (int) $cnpj[13];
	}

	private static function digits( string $value ): string {
		return (string) preg_replace( '/[^0-9]/', '', $value );
	}

	private static function without_formatting( string $value ): string {
		return strtoupper( (string) preg_replace( '/[\.\-\/]/', '', $value ) );
	}
}
