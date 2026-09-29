import { expect, test } from '@playwright/test';

import { addThePlanToTheCart, fillTheBillingAddress, placeOrder } from '../support/store';

const CONSENT = 'I agree to the terms and privacy policy before placing the order.';

test.describe( 'Agreeing to the policy at checkout', () => {
	test.beforeEach( async ( { page } ) => {
		await addThePlanToTheCart( page );
		await fillTheBillingAddress( page, 'Brazil', '529.982.247-25' );
	} );

	test( 'links the terms and privacy policy', async ( { page } ) => {
		await expect( page.getByRole( 'link', { name: 'terms and privacy policy' } ) ).toHaveAttribute( 'href', 'https://libresign.coop/privacy-policy' );
	} );

	test( 'refuses the order without the consent', async ( { page } ) => {
		await placeOrder( page );

		await expect( page.getByText( 'You must agree to the policies before completing the purchase.' ) ).toBeVisible();
		await expect( page ).toHaveURL( /\/checkout\/$/ );
	} );

	test( 'places the order with the consent', async ( { page } ) => {
		await page.getByLabel( CONSENT ).check();
		await placeOrder( page );

		await expect( page ).toHaveURL( /\/order-received\// );
		await expect( page.getByText( 'Thank you. Your order has been received.' ) ).toBeVisible();
	} );
} );
