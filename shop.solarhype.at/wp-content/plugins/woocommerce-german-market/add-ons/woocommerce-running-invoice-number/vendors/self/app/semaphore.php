<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_WC_Running_Invoice_Number_Semaphore' ) ) {
	
	class WP_WC_Running_Invoice_Number_Semaphore {

		/**
		* @var Boolean
		* @access public
		* are sem-functions available
		*/
		public static $use_sem 		= false;
		
		/**
		* @var Boolean
		* @access public
		* can flock-function be used to create a lock file
		*/
		public static $use_flock 	= false;
		
		/**
		* @var null | String
		* @access public
		* path to lock file
		*/
		public static $lock_file 	= null;
		
		/**
		* @var Boolean
		* @access public
		* if we use the option-method
		*/
		public static $use_option 	= true;
		
		/**
		* @var Integer
		* @access public
		* id of semaphore if sem-functions are available
		*/
		public static $semaphore 	= null;

		/**
		* @var Ressource
		* @access public
		* ressource semaphore returned by sem_get or by fopen
		*/
		public static $ressource	= null;

		/**
		* Semaphore / Mutex / Flock init
		*
		* @access public
		* @static
		* @return void
		*/
		public static function init() {

			if ( function_exists( 'sem_get' ) && function_exists( 'sem_acquire' ) && function_exists( 'sem_release' ) && ( 'yes' === get_option( 'wp_wc_running_invoice_number_semaphore_use_sem', 'yes' ) ) ) {
				self::$use_sem = true;
				self::$semaphore = 1700001;
				self::$use_option = false;
			} else {

				if ( 'yes' === get_option( 'wp_wc_running_invoice_number_semaphore_use_flock', 'yes' ) ) {
					$try_path_cache  	= untrailingslashit( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'woocommerce-running-invoice-number' . DIRECTORY_SEPARATOR;
					$wp_uploads     	= wp_upload_dir();
					$wp_uploads_dir 	= untrailingslashit( $wp_uploads[ 'basedir' ] ) . DIRECTORY_SEPARATOR . 'woocommerce-running-invoice-number' . DIRECTORY_SEPARATOR;

					if ( wp_mkdir_p( $try_path_cache ) ) {
						$maybe_lock_file = $try_path_cache . 'lockfile.lock';
						if ( touch( $maybe_lock_file ) ) {
							self::$lock_file 	= $maybe_lock_file;
							self::$use_flock 	= true;
							self::$use_option 	= false;
						}
					}

					if ( ! self::$use_flock ) {
						if ( wp_mkdir_p( $wp_uploads_dir ) ) {
							$maybe_lock_file = $wp_uploads_dir . 'lockfile.lock';
							if ( touch( $maybe_lock_file ) ) {
								self::$lock_file 	= $maybe_lock_file;
								self::$use_flock 	= true;
								self::$use_option 	= false;
							}
						}
					}
				}
			}
		}

		/**
		* Semaphore get (like sem_get)
		*
		* @access public
		* @static
		* @return Ressource | String
		*/
		public static function sem_get() {

			$return_value = '';

			if ( self::$use_sem ) {

				if ( is_null( self::$ressource ) ) {
					self::$ressource = sem_get( self::$semaphore,  1, 0666, true );
				}

				$return_value = self::$ressource;

			} elseif ( self::$use_flock ) {
				
				if ( is_null( self::$ressource ) ) {
					self::$ressource = fopen( self::$lock_file, 'a' ); 
				}
				
				$return_value = self::$ressource;
			}

			return $return_value;
		}

		/**
		* Semaphore aquire (like sem_acquire)
		*
		* @access public
		* @static
		* @return Boolean
		*/
		public static function sem_acquire() {
			
			$return_value = true;

			if ( self::$use_sem ) {
				$return_value = sem_acquire( self::$ressource, false );
			} elseif ( self::$use_flock ) {
				$return_value = flock( self::$ressource, LOCK_EX ); 
			} else {

				$i = 0;
				while ( get_option( 'wp_wc_invoice_number_construct_running', 'no' ) == 'yes' ) {
					
					if ( apply_filters( 'german_market_invoice_number_logging', false ) ) {
						$new_log = 'Entered nanosleep, wait 0.25 seconds';
						$logger = wc_get_logger();
						$context = array( 'source' => 'german-market-invoice-number' );
						$logger->notice( $new_log, $context );
					}

					time_nanosleep( 0, 250000000 );
					$i++;
					
					if ( $i > apply_filters( 'wp_wc_invoice_number_construct_running_loop_break', 24 ) ) {
						if ( apply_filters( 'german_market_invoice_number_logging', false ) ) {
							$new_log = sprintf( 'Break in nanosleep after %s loops', ($i-1) );
							$logger = wc_get_logger();
							$context = array( 'source' => 'german-market-invoice-number' );
							$logger->warning( $new_log, $context );
						}

						break;
					}
				}
			}

			return $return_value;
		}

		/**
		* Semaphore release (like sem_release)
		*
		* @access public
		* @static
		* @return Boolean
		*/
		public static function sem_release() {
			$return_value = true;

			if ( self::$use_sem ) {
				$return_value = sem_release( self::$ressource );
			} elseif ( self::$use_flock ) {
				$return_value = flock( self::$ressource, LOCK_UN ); 
			} else {
				$return_value = update_option( 'wp_wc_invoice_number_construct_running', 'no', 'no' );
			}

			return $return_value;
		}

		/**
		* If neither sem-functions nor flock is available, we set up an option
		*
		* @access public
		* @static
		* @return void
		*/
		public static function init_option_lock() {
			if ( self::$use_option ) {
				update_option( 'wp_wc_invoice_number_construct_running', 'yes', 'no' );
			}
		}
	}
}
