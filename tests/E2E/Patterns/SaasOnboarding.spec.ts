import { expect, Page, test } from '@playwright/test';

import { addThePlanToTheCart, fillTheBillingAddress, placeOrder } from '../support/store';

async function expectTheOnboarding( page: Page ): Promise< void > {
	await expect( page.getByRole( 'heading', { name: 'How your LibreSign workspace works' } ) ).toHaveCount( 1 );
	await expect( page.getByRole( 'heading', { name: '1. Choose a plan' } ) ).toBeVisible();
	await expect( page.getByRole( 'heading', { name: '2. Create your workspace' } ) ).toBeVisible();
	await expect( page.getByRole( 'heading', { name: '3. Start signing' } ) ).toBeVisible();
}

test.describe( 'Showing how the workspace works while buying', () => {
	test( 'on the shop', async ( { page } ) => {
		await page.goto( '/shop/' );

		await expectTheOnboarding( page );
	} );

	test( 'on the checkout', async ( { page } ) => {
		await addThePlanToTheCart( page );
		await page.goto( '/checkout/' );

		await expectTheOnboarding( page );
	} );

	test( 'on the order received', async ( { page } ) => {
		await addThePlanToTheCart( page );
		await fillTheBillingAddress( page, 'Brazil', '529.982.247-25' );
		await page.getByLabel( 'I agree to the terms and privacy policy before placing the order.' ).check();
		await placeOrder( page );

		await expect( page ).toHaveURL( /\/order-received\// );
		await expectTheOnboarding( page );
	} );

	test( 'not on other pages', async ( { page } ) => {
		await page.goto( '/product/basic/' );

		await expect( page.getByRole( 'heading', { name: 'How your LibreSign workspace works' } ) ).toHaveCount( 0 );
	} );
} );
