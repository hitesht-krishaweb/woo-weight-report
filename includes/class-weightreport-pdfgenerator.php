<?php
/**
 * The file that defines the pdf ganeartor plugin class
 *
 * @since      1.0.0
 *
 * @package    WooCommerce
 * @subpackage WooCommerce_WeightReport
 */

namespace Woo\WeightReport;

use Mpdf\Mpdf;

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The core plugin class.
 *
 * @since 1.0.0
 */
class WeightReport_PDFGenerator {

	/**
	 * Total weight of copper in ounces.
	 *
	 * @var global variable.
	 */
	private $mpdf;

	/**
	 * Constructor to initialize the mPDF object.
	 */
	public function __construct() {

		$mpdf_args = apply_filters(
			'woo_report_mpdf_args',
			array(
				'autoScriptToLang'  => true,
				'autoLangToFont'    => true,
				'default_font'      => 'dejavusans',
				'default_font_size' => 9,
				'margin_left'       => 1,
				'margin_right'      => 1,
				'margin_top'        => 1,
				'margin_bottom'     => 1,
			),
		);

		// Instantiate the mPDF object.
		$this->mpdf = new Mpdf( $mpdf_args );
	}

	/**
	 * Function to generate a PDF document.
	 *
	 * @param string $html_content The HTML content to convert to PDF.
	 * @param string $file_name The name of the PDF file.
	 * @param string $output_mode Output mode (I: Inline, D: Download, F: Save to file).
	 */
	public function generate_pdf( $html_content, $file_name = 'document.pdf', $output_mode = 'D' ) {
		$style = $this->mpdf_inline_style();
		try {
			$html_content = preg_replace( '/<a[^>]*>(.*?)<\/a>/is', '$1', $html_content );
			// Load the HTML content into the mPDF instance.
			$this->mpdf->WriteHTML( $style, \Mpdf\HTMLParserMode::HEADER_CSS );
			$this->mpdf->WriteHTML( $html_content, \Mpdf\HTMLParserMode::HTML_BODY );

			// Output the generated PDF based on the specified mode.
			$this->mpdf->Output( $file_name, $output_mode );
		} catch ( \Mpdf\MpdfException $e ) {
			// Log any errors to the WordPress debug log.
			error_log( 'PDF generation error: ' . $e->getMessage() ); //phpcs:ignore
		}
	}

	/**
	 * Set a custom header for the PDF.
	 *
	 * @param string $header_content The content to use in the header.
	 */
	public function set_custom_header( $header_content ) {
		$this->mpdf->SetHeader( $header_content );
	}

	/**
	 * Set a custom footer for the PDF.
	 *
	 * @param string $footer_content The content to use in the footer.
	 */
	public function set_custom_footer( $footer_content ) {
		$this->mpdf->SetFooter( $footer_content );
	}

	/**
	 * Set custom margins for the PDF layout.
	 *
	 * @param float $margin_top Top margin in millimeters.
	 * @param float $margin_bottom Bottom margin in millimeters.
	 * @param float $margin_left Left margin in millimeters.
	 * @param float $margin_right Right margin in millimeters.
	 */
	public function set_custom_page_layout( $margin_top, $margin_bottom, $margin_left, $margin_right ) {
		$this->mpdf->SetMargins( $margin_left, $margin_right, $margin_top );
		$this->mpdf->SetAutoPageBreak( true, $margin_bottom );
	}

	/**
	 * Set PDF style inline.
	 */
	public function mpdf_inline_style() {

		$css = '
			table {
			width: 100%;
			border-collapse: collapse;
		}
		th, td {
			padding: 5px 5px;
			border: 1px solid #fff;
			text-align:center;
		}
		thead {
			background-color: #f9f9f9;
		}
		tbody tr:nth-child(odd) {
			background-color: #f9f9f9;
		}
		#test-order,
		#product,
		.hidden,
		.empty,
		.column-product,
		.toggle-view,
		.column-test-order,
		.sorting-indicators,
		.screen-reader-text,
		.tablenav{
			display:none;
		}';

		return $css;
	}
}
