<?php
/**
 * Billing compliance: CPF/CNPJ field for Brazilian customers.
 *
 * Controls the visibility of the CPF/CNPJ checkout field based on billing
 * country. Field registration, required-for-BR logic, and format validation
 * live in inc/cpf-cnpj.php.
 *
 * @package libresign
 */

defined( 'ABSPATH' ) || exit;

/**
 * Show the CPF/CNPJ field only when the billing address country is Brazil.
 *
 * The decision is based exclusively on the billing address country (the
 * country the customer enters in the checkout form), NOT on the browser
 * language or site locale. A customer browsing in English from England with
 * a Brazilian billing address will see and be required to fill this field.
 *
 * WooCommerce Blocks renders additional fields for all countries; this script
 * hides the row when billing country ≠ BR and shows it when billing country = BR.
 * Uses a MutationObserver so it survives React re-renders.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	?>
	<style>
		/* Initially hidden; JavaScript reveals it only for Brazil */
		.wc-block-components-address-form__libresign-cpf-cnpj { display: none; }
	</style>
	<script>
	( function () {
		var FIELD_KEY     = 'billing_libresign/cpf-cnpj';
		var FIELD_CSS     = '.wc-block-components-address-form__libresign-cpf-cnpj';
		var CLEAN_LABEL   = <?php echo wp_json_encode( __( 'CPF or CNPJ', 'libresign' ) ); ?>;
		var COUNTRY_SELECTORS = [ '#billing-country', '[name="billing_country"]', '[data-testid="select-country"]' ];
		var OBS_OPTIONS   = { childList: true, subtree: true };

		function getCountry() {
			for ( var i = 0; i < COUNTRY_SELECTORS.length; i++ ) {
				var el = document.querySelector( COUNTRY_SELECTORS[ i ] );
				if ( el ) return el.value;
			}
			return '';
		}

		// ---------------------------------------------------------------------------
		// Show / hide field based on billing country
		// ---------------------------------------------------------------------------
		var observer = new MutationObserver( updateVisibility );

		function updateVisibility() {
			var row = document.querySelector( FIELD_CSS );
			if ( ! row ) return;
			var isBrazil = getCountry() === 'BR';
			row.style.display = isBrazil ? 'block' : 'none';

			// Remove "(optional)" suffix — disconnect observer first to prevent loop
			var label = row.querySelector( 'label' );
			if ( isBrazil && label && label.textContent.trim() !== CLEAN_LABEL ) {
				observer.disconnect();
				label.textContent = CLEAN_LABEL;
				observer.observe( document.body, OBS_OPTIONS );
			}
		}

		// ---------------------------------------------------------------------------
		// Bootstrap
		// ---------------------------------------------------------------------------
		function init() {
			updateVisibility();
			observer.observe( document.body, OBS_OPTIONS );
			document.body.addEventListener( 'change', function ( e ) {
				if ( e.target && COUNTRY_SELECTORS.some( function ( s ) {
					return e.target.matches( s );
				} ) ) {
					updateVisibility();
				}
			} );
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', init );
		} else {
			init();
		}
	} )();
	</script>
	<?php
} );
