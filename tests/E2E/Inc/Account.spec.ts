import { expect, test } from '@playwright/test';

test.describe( 'Recovering the password', () => {
	test( 'opens the lost password form on its own page', async ( { page } ) => {
		await page.goto( '/lost-password/' );

		await expect( page.getByRole( 'heading', { name: 'Lost password', level: 1 } ) ).toBeVisible();
		await expect( page.getByLabel( /Email or username/ ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Back to sign in' } ) ).toHaveAttribute( 'href', /\/my-account\/$/ );
	} );

	test( 'offers the lost password form from the account page', async ( { page } ) => {
		await page.goto( '/my-account/' );
		await page.getByRole( 'link', { name: 'Lost your password?' } ).click();

		await expect( page.getByRole( 'button', { name: 'Send reset link' } ) ).toBeVisible();
	} );
} );
