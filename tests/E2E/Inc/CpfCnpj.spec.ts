import { expect, test } from '@playwright/test';

import { addThePlanToTheCart, fillTheBillingAddress, placeOrder } from '../support/store';

test.describe( 'Asking Brazilian customers for their CPF or CNPJ', () => {
	test.beforeEach( async ( { page } ) => {
		await addThePlanToTheCart( page );
	} );

	test( 'hides the field outside Brazil', async ( { page } ) => {
		await fillTheBillingAddress( page, 'Portugal' );

		await expect( page.getByLabel( 'CPF or CNPJ' ) ).toBeHidden();
	} );

	test( 'requires the field in Brazil', async ( { page } ) => {
		await fillTheBillingAddress( page, 'Brazil' );
		await placeOrder( page );

		await expect( page.getByText( 'Please enter your CPF or CNPJ.' ) ).toBeVisible();
	} );

	test( 'refuses an invalid CPF', async ( { page } ) => {
		await fillTheBillingAddress( page, 'Brazil', '529.982.247-24' );
		await placeOrder( page );

		await expect( page.getByText( 'Please enter a valid CPF or CNPJ.' ) ).toBeVisible();
	} );
} );
