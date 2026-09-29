import { expect, Page, test } from '@playwright/test';

import { addThePlanToTheCart } from '../support/store';

async function fillTheWorkspaceForm( page: Page ): Promise< void > {
	await page.goto( '/my-account/' );
	await page.waitForFunction( () => 'zxcvbn' in window );
	await page.locator( '#reg_email' ).fill( `workspace-${ Date.now() }@example.org` );
	await page.locator( '#reg_password' ).pressSequentially( 'a strong password 123' );
}

test.describe( 'Creating a workspace together with a plan', () => {
	test( 'sends a visitor to choose a plan first', async ( { page } ) => {
		await page.goto( '/my-account/' );

		await expect( page.getByRole( 'heading', { name: 'Create your workspace' } ) ).toBeHidden();
		await page.getByRole( 'link', { name: 'Choose a plan' } ).click();

		await expect( page ).toHaveURL( /\/shop\/$/ );
	} );

	test( 'creates the workspace and continues to checkout', async ( { page } ) => {
		await addThePlanToTheCart( page );
		await fillTheWorkspaceForm( page );

		await page.locator( '#libresign_workspace_terms' ).check();
		await page.getByRole( 'button', { name: 'Continue to checkout' } ).click();

		await expect( page ).toHaveURL( /\/checkout\/$/ );
	} );

	test( 'refuses the workspace without the terms consent', async ( { page } ) => {
		await addThePlanToTheCart( page );
		await fillTheWorkspaceForm( page );

		await page.locator( '#libresign_workspace_terms' ).evaluate( ( checkbox ) => checkbox.removeAttribute( 'required' ) );
		await page.getByRole( 'button', { name: 'Continue to checkout' } ).click();

		await expect( page.getByText( 'You must accept the terms to create your workspace.' ) ).toBeVisible();
	} );
} );
