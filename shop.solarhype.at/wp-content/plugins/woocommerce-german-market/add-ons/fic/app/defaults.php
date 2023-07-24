<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

/**
* Get Default Nutritionals
*
* @return Array
*/
function gm_fic_get_default_nutritionals() {

	$nutritionals = array(

		'1000-energy'	=> array(
			'label'		=> __( 'Energy', 'woocommerce-german-market' ), // DE: Energie, Brennwert
			'required'	=> true,
			'parent'	=> false
		),

		'2000-fat'	=> array(
			'label'		=> __( 'Fat', 'woocommerce-german-market' ), // DE: Fett
			'required'	=> true,
			'parent'	=> false
		),

		'2100-fat-saturates'	=> array(
			'label'		=> __( 'Saturates', 'woocommerce-german-market' ), // DE: Gesättigte Fettsäuren
			'required'	=> true,
			'parent'	=> '2000-fat'
		),

		'2200-fat-mono-unsaturates'	=> array(
			'label'		=> __( 'Mono-unsaturates', 'woocommerce-german-market' ), // DE: Einfach ungesättigte Fettsäuren
			'required'	=> false,
			'parent'	=> '2000-fat'
		),

		'2300-fat-polyunsaturates'	=> array(
			'label'		=> __( 'Poly-unsaturates ', 'woocommerce-german-market' ), // DE: Mehrfach ungesättigte Fättsäuren
			'required'	=> false,
			'parent'	=> '2000-fat'
		),

		'3000-carbohydrate'	=> array(
			'label'		=> __( 'Carbohydrate', 'woocommerce-german-market' ), // DE: Kohlenhydrate
			'required'	=> true,
			'parent'	=> false
		),

		'3100-carbohydrate-sugars' => array(
			'label'		=> __( 'Sugars ', 'woocommerce-german-market' ), // DE: Zucker
			'required'	=> true,
			'parent'	=> '3000-carbohydrate'
		),

		'3200-carbohydrate-polyols' => array(
			'label'		=> __( 'Polyols ', 'woocommerce-german-market' ), // DE: Mehrwertige Alkohole
			'required'	=> false,
			'parent'	=> '3000-carbohydrate'
		),

		'3300-carbohydrate-starch' => array(
			'label'		=> __( 'Starch ', 'woocommerce-german-market' ), // DE: Stärke
			'required'	=> false,
			'parent'	=> '3000-carbohydrate'
		),

		'4000-fibre' => array(
			'label'		=> __( 'Fibre ', 'woocommerce-german-market' ), // DE: Ballaststoffe
			'required'	=> false,
			'parent'	=> false
		),

		'5000-protein' => array(
			'label'		=> __( 'Protein ', 'woocommerce-german-market' ), // DE: Eiweiß
			'required'	=> true,
			'parent'	=> false
		),

		'6000-salt' => array(
			'label'		=> __( 'Salt ', 'woocommerce-german-market' ), // DE: Salz
			'required'	=> true,
			'parent'	=> false
		),

		'7000-vitamins-and-minerals' => array(
			'label'		=> __( 'Vitamins and minerals ', 'woocommerce-german-market' ), // DE: Vitamine und Minteralien
			'required'	=> false,
			'parent'	=> false
		),

	);

	return apply_filters( 'gm_fic_get_default_nutritionals', $nutritionals );

}
