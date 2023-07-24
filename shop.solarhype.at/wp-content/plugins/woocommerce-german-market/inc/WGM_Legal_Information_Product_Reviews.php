<?php

/**
 * Class WGM_Legal_Information_Product_Reviews
 *
 */
class WGM_Legal_Information_Product_Reviews {

	/**
	 * @var WGM_Legal_Information_Product_Reviews
	 * @since v3.15
	 */
	private static $instance = null;
	public $star_ratings_option = null;

	/**
	* Singletone get_instance
	*
	* @static
	* @return WGM_Legal_Information_Product_Reviews
	*/
	public static function get_instance() {
		if ( self::$instance == NULL) {
			self::$instance = new WGM_Legal_Information_Product_Reviews();	
		}
		return self::$instance;
	}

	/**
	* Singletone constructor
	*
	* @access private
	*/
	private function __construct() {
		
		$this->star_ratings_option = get_option( 'gm_legal_information_product_reviews_star_ratings', 'nothing' );

		if ( 'on' === get_option( 'gm_legal_information_product_reviews_wc_review_before', 'off' ) ) {
			add_action( 'wp_ajax_product_review_info', array( $this, 'before_review_ajax' ) );
  			add_action( 'wp_ajax_nopriv_product_review_info', array( $this, 'before_review_ajax' ) );
		}
		
		if ( 'nothing' !== $this->star_ratings_option ) {

			add_filter( 'woocommerce_product_get_rating_html', array( $this, 'rating_html' ), 10, 3 );

			// don't change behaviour in review template
			add_action( 'woocommerce_review_before', array( $this, 'review_before' ) );
			add_action( 'woocommerce_review_meta', array( $this, 'review_meta' ), 99 );
		}
		
		do_action( 'german_market_after_wgm_legal_information_product_reviews', $this );
	}

	/**
	* Don't change rating star output in product review template
	* 
	* @wp-hook woocommerce_review_before
	* @return void
	*/
	public function review_before() {
		remove_filter( 'woocommerce_product_get_rating_html', array( $this, 'rating_html' ), 10, 3 );
	}

	/**
	* Don't change rating star output in product review template
	* 
	* @wp-hook woocommerce_review_meta
	* @return void
	*/
	public function review_meta() {
		add_filter( 'woocommerce_product_get_rating_html', array( $this, 'rating_html' ), 10, 3 );
	}

	/**
	* Change Rating HTML Output
	* 
	* @wp-hook woocommerce_product_get_rating_html
	* @param String $html
	* @param String $rating
	* @param String $count
	* 
	* @return String
	*/
	public function rating_html( $html, $rating, $count ) {

		$empty_html = apply_filters( 'german_market_legal_info_product_reviews_empty_html', empty( $html ), $html, $rating, $count );

		if ( 'hide' === $this->star_ratings_option ) {
			$html = '';
		} else if ( ( 'complete_text' === $this->star_ratings_option ) && ( ! $empty_html ) ) {
			$html .= sprintf( $this->get_markup_after_stars_rating(), $this->get_info_text() );
		} else if ( ( 'short_text' === $this->star_ratings_option ) && ( ! $empty_html ) ) {
			$html .= $this->get_short_text_markup();
		}

		return $html;
	}

	/**
	* TO DO: Return Markup For Short Text Information
	* 
	* @return String
	*/
	public function get_short_text_markup() {
		
		$short_text = get_option( 'gm_legal_information_product_reviews_short_information_text', self::get_short_text_default() );
		$close_element = apply_filters( 'german_market_legal_info_product_reviews_short_text_close_element','<span class="close-full-text">' . __( 'Close', 'woocommerce-german-market' ) .'</span>' );
		$info_icon = apply_filters( 'german_market_legal_info_product_reviews_short_text_info_icon', '<span class="german-market-legal-information-for-product-reviews-info-icon">ⓘ</span>' );

		$markup = apply_filters( 'german_market_legal_info_product_reviews_short_text_markup',

					'<span class="german-market-legal-information-for-product-reviews short-after-star-rating">' .
						$info_icon . 
						'%s<span class="full-text">' . 
							$close_element . '%s
						</span>
					</span>' );

		$markup_with_short_text = sprintf( $markup, $short_text, $this->get_info_text() );

		return $markup_with_short_text;
	}

	/**
	* Get Default Text for Short Text Information
	* 
	* @static
	* @return String
	*/
	public static function get_short_text_default() {
		return __( 'Information on verifying the authenticity of reviews', 'woocommerce-german-market' );
	}

	/**
	* Output Markup in AJAX Request
	* 
	* @wp-hook wp_ajax_product_review_info
	* @wp-hook wp_ajax_nopriv_product_review_info
	* 
	* @return void
	*/
	public function before_review_ajax() {
		echo sprintf( $this->get_markup_before_review(), $this->get_info_text() );
		exit();
	}

	/**
	* Get Markup Before Reviews
	* 
	* @return String
	*/
	public function get_markup_before_review() {
		return apply_filters( 'german_market_legal_info_product_reviews_before_review_markup', '<div class="german-market-legal-information-for-product-reviews">%s</div>' ); 
	}

	/**
	* Get Markup After Star Ratings
	* 
	* @return String
	*/
	public function get_markup_after_stars_rating() {
		return apply_filters( 'german_market_legal_info_product_reviews_after_star_rating_markup', '<span class="german-market-legal-information-for-product-reviews after-star-rating">%s</span>' ); 
	}

	/**
	* Get Default Option Key
	* 
	* @static
	* @return String
	*/
	public static function get_default_info_text_setting() {
		$wc_setting_verify = get_option( 'woocommerce_review_rating_verification_required' ) === 'yes';
		return $wc_setting_verify ? 'verified' : 'not_verified';
	}

	/**
	* Get Text "verified"
	* 
	* @static
	* @return String
	*/
	public static function get_verified_text() {
		return __( "Each product review is checked for authenticity before publication, so that it is ensured that reviews only come from consumers who have actually purchased the reviewed products. The verification is done automatically by comparing the review with the customer's order history, so that a previous product purchase becomes a necessary condition for publication.", 'woocommerce-german-market' );
	}

	/**
	* Get Text "not verified"
	* 
	* @static
	* @return String
	*/
	public static function get_not_verified_text() {
		return __( 'The reviews are not checked for authenticity before they are published. They can therefore also come from consumers who have not actually purchased the rated products.', 'woocommerce-german-market' );
	}

	/**
	* Get Text
	* 
	* @return String
	*/
	public function get_info_text() {
		
		$info_text_setting = get_option( 'gm_legal_information_product_reviews_information_text', self::get_default_info_text_setting() );

		if ( 'verified' === $info_text_setting ) {
			$info_text = esc_html( self::get_verified_text() );
		} else if ( 'custom' === $info_text_setting ) {
			$info_text = strip_tags( nl2br( get_option( 'gm_legal_information_product_reviews_custom_text' ), false ), '<br><a>' );
		} else {
			$info_text = esc_html( self::get_not_verified_text() );
		}

		return apply_filters( 'german_market_legal_info_product_reviews_text', $info_text );
	}
}
