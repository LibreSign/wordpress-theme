<?php

namespace LibreSign\WordPressTheme\Tests\Unit;

use LibreSign\WordPressTheme\TaxId;
use PHPUnit\Framework\TestCase;

final class TaxIdTest extends TestCase {

	/**
	 * @dataProvider provide_cpfs
	 */
	public function test_validates_a_cpf( $cpf, $valid ) {
		$this->assertSame( $valid, TaxId::valid_cpf( $cpf ) );
	}

	public static function provide_cpfs() {
		yield 'digits only'                  => array( '52998224725', true );
		yield 'formatted'                    => array( '529.982.247-25', true );
		yield 'another valid one'            => array( '123.456.789-09', true );
		yield 'wrong first check digit'      => array( '529.982.247-35', false );
		yield 'wrong second check digit'     => array( '529.982.247-24', false );
		yield 'every digit the same'         => array( '111.111.111-11', false );
		yield 'too short'                    => array( '5299822472', false );
		yield 'too long'                     => array( '529982247250', false );
		yield 'empty'                        => array( '', false );
	}

	/**
	 * @dataProvider provide_cnpjs
	 */
	public function test_validates_a_cnpj( $cnpj, $valid ) {
		$this->assertSame( $valid, TaxId::valid_cnpj( $cnpj ) );
	}

	public static function provide_cnpjs() {
		yield 'digits only'                           => array( '11222333000181', true );
		yield 'formatted'                             => array( '11.222.333/0001-81', true );
		yield 'alphanumeric'                          => array( '12.ABC.345/01DE-35', true );
		yield 'alphanumeric in lowercase'             => array( '12.abc.345/01de-35', true );
		yield 'wrong second check digit'              => array( '11.222.333/0001-80', false );
		yield 'wrong alphanumeric check digit'        => array( '12.ABC.345/01DE-34', false );
		yield 'letters in the check digits'           => array( '12.ABC.345/01DE-3A', false );
		yield 'every digit the same'                  => array( '00.000.000/0000-00', false );
		yield 'symbols other than the formatting'     => array( '12.ABC.345/01D*-35', false );
		yield 'too short'                             => array( '1122233300018', false );
		yield 'empty'                                 => array( '', false );
	}

	/**
	 * @dataProvider provide_values
	 */
	public function test_names_the_problem_of_a_value( $value, $error_code ) {
		$this->assertSame( $error_code, TaxId::error_code( $value ) );
	}

	public static function provide_values() {
		yield 'empty'                                   => array( '', 'libresign_cpf_cnpj_required' );
		yield 'blank'                                   => array( '   ', 'libresign_cpf_cnpj_required' );
		yield 'a valid cpf'                             => array( '529.982.247-25', '' );
		yield 'a valid cpf with surrounding spaces'     => array( ' 529.982.247-25 ', '' );
		yield 'an invalid cpf'                          => array( '529.982.247-24', 'invalid_cpf' );
		yield 'a valid cnpj'                            => array( '11.222.333/0001-81', '' );
		yield 'a valid alphanumeric cnpj'               => array( '12.abc.345/01de-35', '' );
		yield 'an invalid cnpj'                         => array( '11.222.333/0001-80', 'invalid_cnpj' );
		yield 'fourteen characters decide for cnpj'     => array( '529 982 247 25', 'invalid_cnpj' );
		yield 'neither length'                          => array( '123456789', 'invalid_cpf_cnpj' );
	}
}
