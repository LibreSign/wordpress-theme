import { expect, Page } from '@playwright/test';

export async function addThePlanToTheCart( page: Page ): Promise< void > {
	await page.goto( '/product/basic/' );
	await page.getByRole( 'button', { name: 'Add to cart' } ).click();
	await expect( page.getByRole( 'alert' ) ).toContainText( 'has been added to your cart' );
}

export async function fillTheBillingAddress( page: Page, country: string, cpfCnpj = '' ): Promise< void > {
	await page.goto( '/checkout/' );
	await page.locator( '#email' ).fill( 'customer@example.org' );
	await page.locator( '#billing-country' ).selectOption( { label: country } );
	await page.locator( '#billing-first_name' ).fill( 'Ana' );
	await page.locator( '#billing-last_name' ).fill( 'Lima' );
	await page.locator( '#billing-address_1' ).fill( 'Rua do Ouvidor, 50' );
	await page.locator( '#billing-city' ).fill( 'Rio de Janeiro' );
	await page.locator( '#billing-postcode' ).fill( '20040-030' );
	if ( 'Brazil' === country ) {
		await page.locator( '#billing-state' ).selectOption( { label: 'Rio de Janeiro' } );
	}
	if ( '' !== cpfCnpj ) {
		await page.getByLabel( 'CPF or CNPJ' ).fill( cpfCnpj );
	}
}

export function placeOrder( page: Page ) {
	return page.getByRole( 'button', { name: 'Place Order' } ).click();
}
