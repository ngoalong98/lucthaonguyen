<?php
/*
Plugin Name: WPC Smart Quick View for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Smart Quick View allows users to get a quick look of products without opening the product page.
Version: 2.6.0
Author: WPClever
Author URI: https://wpclever.net
Text Domain: woosq
Domain Path: /languages/
Requires at least: 4.0
Tested up to: 5.7
WC requires at least: 3.0
WC tested up to: 5.1
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WOOSQ_VERSION' ) && define( 'WOOSQ_VERSION', '2.6.0' );
! defined( 'WOOSQ_URI' ) && define( 'WOOSQ_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WOOSQ_PATH' ) && define( 'WOOSQ_PATH', plugin_dir_path( __FILE__ ) );
! defined( 'WOOSQ_SUPPORT' ) && define( 'WOOSQ_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=woosq&utm_campaign=wporg' );
! defined( 'WOOSQ_REVIEWS' ) && define( 'WOOSQ_REVIEWS', 'https://wordpress.org/support/plugin/woo-smart-quick-view/reviews/?filter=5' );
! defined( 'WOOSQ_CHANGELOG' ) && define( 'WOOSQ_CHANGELOG', 'https://wordpress.org/plugins/woo-smart-quick-view/#developers' );
! defined( 'WOOSQ_DISCUSSION' ) && define( 'WOOSQ_DISCUSSION', 'https://wordpress.org/support/plugin/woo-smart-quick-view' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WOOSQ_URI );

include 'includes/wpc-dashboard.php';
include 'includes/wpc-menu.php';
include 'includes/wpc-kit.php';

if ( ! function_exists( 'woosq_init' ) ) {
	add_action( 'plugins_loaded', 'woosq_init', 11 );

	function woosq_init() {
		// load text-domain
		load_plugin_textdomain( 'woosq', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'woosq_notice_wc' );

			return;
		}

		if ( ! class_exists( 'WPCleverWoosq' ) ) {
			class WPCleverWoosq {
				protected static $summary_default = array();

				function __construct() {
					self::$summary_default = array(
						'title',
						'rating',
						'price',
						'excerpt',
						'add_to_cart',
						'meta'
					);

					add_action( 'init', array( $this, 'woosq_init' ) );

					// menu
					add_action( 'admin_menu', array( $this, 'woosq_admin_menu' ) );

					// enqueue scripts
					add_action( 'wp_enqueue_scripts', array( $this, 'woosq_wp_enqueue_scripts' ) );

					// ajax
					add_action( 'wp_ajax_woosq_quickview', array( $this, 'woosq_quickview' ) );
					add_action( 'wp_ajax_nopriv_woosq_quickview', array( $this, 'woosq_quickview' ) );

					// link
					add_filter( 'plugin_action_links', array( $this, 'woosq_action_links' ), 10, 2 );
					add_filter( 'plugin_row_meta', array( $this, 'woosq_row_meta' ), 10, 2 );

					// add image to variation
					add_filter( 'woocommerce_available_variation', array( $this, 'woosq_available_variation' ), 10, 3 );

					// summary
					add_action( 'woosq_product_summary', 'woocommerce_template_single_title', 5 );
					add_action( 'woosq_product_summary', 'woocommerce_template_single_rating', 10 );
					add_action( 'woosq_product_summary', 'woocommerce_template_single_price', 15 );
					add_action( 'woosq_product_summary', 'woocommerce_template_single_excerpt', 20 );
					add_action( 'woosq_product_summary', array( $this, 'woosq_add_to_cart' ), 25 );
					add_action( 'woosq_product_summary', 'woocommerce_template_single_meta', 30 );
					add_filter( 'woocommerce_add_to_cart_redirect', array(
						$this,
						'woosq_add_to_cart_redirect'
					), 10, 1 );

					// multiple cats
					add_filter( 'wp_dropdown_cats', array( $this, 'woosq_dropdown_cats_multiple' ), 10, 2 );
				}

				function woosq_init() {
					// image size
					add_image_size( 'woosq', 460, 460, true );

					// shortcode
					add_shortcode( 'woosq', array( $this, 'woosq_shortcode' ) );

					// position
					$button_position = apply_filters( 'woosq_button_position', get_option( 'woosq_button_position', 'after_add_to_cart' ) );

					switch ( $button_position ) {
						case 'before_title':
							add_action( 'woocommerce_shop_loop_item_title', array( $this, 'woosq_add_button' ), 9 );
							break;

						case 'after_title':
							add_action( 'woocommerce_shop_loop_item_title', array( $this, 'woosq_add_button' ), 11 );
							break;

						case 'after_rating':
							add_action( 'woocommerce_after_shop_loop_item_title', array(
								$this,
								'woosq_add_button'
							), 6 );
							break;

						case 'after_price':
							add_action( 'woocommerce_after_shop_loop_item_title', array(
								$this,
								'woosq_add_button'
							), 11 );
							break;

						case 'before_add_to_cart':
							add_action( 'woocommerce_after_shop_loop_item', array( $this, 'woosq_add_button' ), 9 );
							break;

						case 'after_add_to_cart':
							add_action( 'woocommerce_after_shop_loop_item', array( $this, 'woosq_add_button' ), 11 );
							break;
					}
				}

				function woosq_available_variation( $data, $variable, $variation ) {
					if ( $image_id = $variation->get_image_id() ) {
						$image_src               = wp_get_attachment_image_src( $image_id, get_option( 'woosq_image_size', 'woosq' ) );
						$data['woosq_image_id']  = $image_id;
						$data['woosq_image_src'] = $image_src[0];
						$data['woosq_image']     = wp_get_attachment_image( $image_id, get_option( 'woosq_image_size', 'woosq' ) );
					}

					return $data;
				}

				function woosq_add_to_cart( $product ) {
					if ( get_option( 'woosq_add_to_cart_button', 'single' ) === 'archive' ) {
						woocommerce_template_loop_add_to_cart();
					} else {
						if ( $product->is_type( 'variation' ) ) {
							?>
                            <form class="cart"
                                  action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
                                  method="post" enctype='multipart/form-data'>
								<?php woocommerce_single_variation_add_to_cart_button(); ?>
                            </form>
							<?php
						} else {
							woocommerce_template_single_add_to_cart();
						}
					}
				}

				function woosq_add_to_cart_redirect( $url ) {
					if ( isset( $_POST['woosq-redirect'] ) && ! empty( $_POST['woosq-redirect'] ) ) {
						return esc_url( $_POST['woosq-redirect'] );
					}

					return $url;
				}

				function woosq_quickview() {
					global $post, $product;
					$product_id = absint( $_GET['product_id'] );
					$product    = wc_get_product( $product_id );

					if ( $product ) {
						$post = get_post( $product_id );
						setup_postdata( $post );
						$thumb_ids = array();

						if ( get_option( 'woosq_content_image', 'all' ) === 'product_image' ) {
							if ( $product->get_image_id() ) {
								$thumb_ids[] = $product->get_image_id();
							}
						} else {
							$thumb_ids = $product->get_gallery_image_ids();

							if ( $product->get_image_id() && ( get_option( 'woosq_content_image', 'all' ) === 'all' ) ) {
								array_unshift( $thumb_ids, $product->get_image_id() );
							}
						}
						?>
                        <div id="woosq-popup" class="mfp-with-anim">
                            <div class="woocommerce single-product">
								<div class="logo-quickview"><?php echo get_custom_logo(); ?></div>
                                <div id="product-<?php echo $product_id; ?>" <?php wc_product_class( '', $product ); ?>>
                                    <div class="thumbnails thumbnails-ori col-full">
										<?php
										do_action( 'woosq_before_thumbnails', $product );

										if ( ! empty( $thumb_ids ) ) {
											foreach ( $thumb_ids as $thumb_id ) {
												echo '<div class="thumbnail">' . wp_get_attachment_image( $thumb_id, get_option( 'woosq_image_size', 'woosq' ) ) . '</div>';
											}
										} else {
											echo '<div class="thumbnail">' . wc_placeholder_img( 'woosq' ) . '</div>';
										}

										do_action( 'woosq_after_thumbnails', $product );
										?>
                                    </div>
									<?php 
										$add_to_cart = do_shortcode('[add_to_cart_url id="'.$product_id.'"]'); 
										$price = $product->get_price();
										$product_price = number_format($price);
									?>
                                    <div class="summary entry-summary col-full">
										<p class="product-name"><?php echo $product->get_title(); ?></p>
										<p class="product-price"><?php echo __('Giá: '); echo $product_price; echo __(' đ');?></p>
										<p class="product-description"><?php echo get_post_meta( $product_id ,'trang-thai-thong-bao', true) ?></p>
										<div class="btn-check-out">
											<div class="buy-now">
											<a href="<?php esc_url(home_url( '/' ));?>checkout/<?php echo $add_to_cart ?>"><?php echo __('Mua ngay') ?></a>
										</div>
										<div class="continue-buy">
											<p class="mfp-close"><?php echo __('Tiếp tục mua hàng')?></p>
										</div>
										</div>
	
                                    </div>
                                </div>
                            </div><!-- /woocommerce single-product -->
                        </div>
						<?php
						wp_reset_postdata();
					}

					die();
				}

				function woosq_add_button() {
					echo do_shortcode( '[woosq]' );
				}

				function woosq_shortcode( $atts ) {
					$output = '';

					$atts = shortcode_atts( array(
						'id'     => null,
						'type'   => get_option( 'woosq_button_type', 'button' ),
						'effect' => get_option( 'woosq_effect', 'mfp-3d-unfold' )
					), $atts, 'woosq' );

					if ( ! $atts['id'] ) {
						global $product;
						$atts['id'] = $product->get_id();
					}

					if ( $atts['id'] ) {
						// check cats
						$selected_cats = get_option( '_woosq_cats', array() );

						if ( ! empty( $selected_cats ) && ( $selected_cats[0] !== '0' ) ) {
							if ( ! has_term( $selected_cats, 'product_cat', $atts['id'] ) ) {
								return '';
							}
						}

						// button text
						$button_text = get_option( 'woosq_button_text' );

						if ( empty( $button_text ) ) {
							$button_text = esc_html__( 'Quick view', 'woosq' );
						}

						if ( $atts['type'] === 'link' ) {
							$output = '<a href="#" class="woosq-btn woosq-btn-' . esc_attr( $atts['id'] ) . ' ' . get_option( 'woosq_button_class' ) . '" data-id="' . esc_attr( $atts['id'] ) . '" data-effect="' . esc_attr( $atts['effect'] ) . '">' . esc_html( $button_text ) . '</a>';
						} else {
							$output = '<button class="woosq-btn woosq-btn-' . esc_attr( $atts['id'] ) . ' ' . get_option( 'woosq_button_class' ) . '" data-id="' . esc_attr( $atts['id'] ) . '" data-effect="' . esc_attr( $atts['effect'] ) . '">' . esc_html( $button_text ) . '</button>';
						}
					}

					return apply_filters( 'woosq_button_html', $output, $atts['id'] );
				}

				function woosq_dropdown_cats_multiple( $output, $r ) {
					if ( isset( $r['multiple'] ) && $r['multiple'] ) {
						$output = preg_replace( '/^<select/i', '<select multiple', $output );
						$output = str_replace( "name='{$r['name']}'", "name='{$r['name']}[]'", $output );

						foreach ( array_map( 'trim', explode( ',', $r['selected'] ) ) as $value ) {
							$output = str_replace( "value=\"{$value}\"", "value=\"{$value}\" selected", $output );
						}
					}

					return $output;
				}

				function woosq_admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Smart Quick View', 'woosq' ), esc_html__( 'Smart Quick View', 'woosq' ), 'manage_options', 'wpclever-woosq', array(
						&$this,
						'woosq_admin_menu_content'
					) );
				}

				function woosq_admin_menu_content() {
					$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Smart Quick View', 'woosq' ) . ' ' . WOOSQ_VERSION; ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'woosq' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WOOSQ_REVIEWS ); ?>"
                                   target="_blank"><?php esc_html_e( 'Reviews', 'woosq' ); ?></a> | <a
                                        href="<?php echo esc_url( WOOSQ_CHANGELOG ); ?>"
                                        target="_blank"><?php esc_html_e( 'Changelog', 'woosq' ); ?></a>
                                | <a href="<?php echo esc_url( WOOSQ_DISCUSSION ); ?>"
                                     target="_blank"><?php esc_html_e( 'Discussion', 'woosq' ); ?></a>
                            </p>
                        </div>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosq&tab=settings' ); ?>"
                                   class="<?php echo $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
									<?php esc_html_e( 'Settings', 'woosq' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosq&tab=premium' ); ?>"
                                   class="<?php echo $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>"
                                   style="color: #c9356e;">
									<?php esc_html_e( 'Premium Version', 'woosq' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>"
                                   class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'woosq' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'settings' ) { ?>
                                <form method="post" action="options.php">
									<?php wp_nonce_field( 'update-options' ) ?>
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'General', 'woosq' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Type', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_button_type">
                                                    <option
                                                            value="button" <?php echo( get_option( 'woosq_button_type', 'button' ) === 'button' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Button', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="link" <?php echo( get_option( 'woosq_button_type', 'button' ) === 'link' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Link', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Text', 'woosq' ); ?></th>
                                            <td>
                                                <input type="text" name="woosq_button_text"
                                                       value="<?php echo get_option( 'woosq_button_text', esc_html__( 'Quick view', 'woosq' ) ); ?>"
                                                       placeholder="<?php esc_html_e( 'Quick view', 'woosq' ); ?>"/>
                                                <span class="description">
                                                    <?php esc_html_e( 'Leave blank to use the default text and can be translated.', 'woosq' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Extra class (optional)', 'woosq' ); ?></th>
                                            <td>
                                                <input type="text" name="woosq_button_class"
                                                       value="<?php echo get_option( 'woosq_button_class', '' ); ?>"/>
                                                <span class="description">
                                                    <?php esc_html_e( 'Add extra class for action button/link, split by one space.', 'woosq' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Position', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_button_position">
                                                    <option
                                                            value="before_title" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === 'before_title' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Above title', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="after_title" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === 'after_title' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Under title', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="after_rating" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === 'after_rating' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Under rating', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="after_price" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === 'after_price' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Under price', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="before_add_to_cart" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === 'before_add_to_cart' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Above add to cart', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="after_add_to_cart" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === 'after_add_to_cart' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Under add to cart', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="0" <?php echo( get_option( 'woosq_button_position', 'after_add_to_cart' ) === '0' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'None (hide it)', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Shortcode', 'woosq' ); ?></th>
                                            <td>
										<span class="description">
											<?php printf( esc_html__( 'You can add the button by manually, please use the shortcode %s, eg. %s for the product with ID is 99.', 'woosq' ), '<code>[woosq id="{product id}"]</code>', '<code>[woosq id="99"]</code>' ); ?>
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Popup effect', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_effect">
                                                    <option
                                                            value="mfp-fade" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-fade' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Fade', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-zoom-in" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-zoom-in' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Zoom in', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-zoom-out" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-zoom-out' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Zoom out', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-newspaper" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-newspaper' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Newspaper', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-move-horizontal" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-move-horizontal' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Move horizontal', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-move-from-top" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-move-from-top' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Move from top', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-3d-unfold" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-3d-unfold' ? 'selected' : '' ); ?>>
														<?php esc_html_e( '3d unfold', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="mfp-slide-bottom" <?php echo( get_option( 'woosq_effect', 'mfp-3d-unfold' ) === 'mfp-slide-bottom' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Slide bottom', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Use perfect-scrollbar', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_perfect_scrollbar">
                                                    <option
                                                            value="yes" <?php echo( get_option( 'woosq_perfect_scrollbar', 'yes' ) === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo( get_option( 'woosq_perfect_scrollbar', 'yes' ) === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                                <span class="description">
											<?php printf( esc_html__( 'Read more about %s', 'woosq' ), '<a href="https://github.com/mdbootstrap/perfect-scrollbar" target="_blank">perfect-scrollbar</a>' ); ?>.
										</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Categories', 'woosq' ); ?></th>
                                            <td>
												<?php
												$selected_cats = get_option( '_woosq_cats' );

												if ( empty( $selected_cats ) ) {
													$selected_cats = array( 0 );
												}

												// named _woosq_cats for multiple selected
												wc_product_dropdown_categories(
													array(
														'name'             => '_woosq_cats',
														'hide_empty'       => 0,
														'value_field'      => 'id',
														'multiple'         => true,
														'show_option_all'  => esc_html__( 'All categories', 'woosq' ),
														'show_option_none' => '',
														'selected'         => implode( ',', $selected_cats )
													) );
												?>
                                                <span class="description">
                                                    <?php esc_html_e( 'Only show the Quick View button for products in selected categories.', 'woosq' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th>
												<?php esc_html_e( 'Content', 'woosq' ); ?>
                                            </th>
                                            <td>
                                                <span style="color: #c9356e">These settings available on Premium Version, click <a
                                                            href="https://wpclever.net/downloads/woocommerce-smart-quick-view?utm_source=pro&utm_medium=woosq&utm_campaign=wporg"
                                                            target="_blank">here</a> to buy, just $29!</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Images', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_content_image">
                                                    <option
                                                            value="all" <?php echo( get_option( 'woosq_content_image', 'all' ) === 'all' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Product image & Product gallery images', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="product_image" <?php echo( get_option( 'woosq_content_image', 'all' ) === 'product_image' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Product image', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="product_gallery" <?php echo( get_option( 'woosq_content_image', 'all' ) === 'product_gallery' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Product gallery images', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Image size', 'woosq' ); ?></th>
                                            <td>
												<?php
												$image_size          = get_option( 'woosq_image_size', 'woosq' );
												$image_sizes         = $this->woosq_get_image_sizes();
												$image_sizes['full'] = array(
													'width'  => '',
													'height' => '',
													'crop'   => false
												);

												if ( ! empty( $image_sizes ) ) {
													echo '<select name="woosq_image_size">';

													foreach ( $image_sizes as $image_size_name => $image_size_data ) {
														echo '<option value="' . esc_attr( $image_size_name ) . '" ' . ( $image_size_name === $image_size ? 'selected' : '' ) . '>' . esc_attr( $image_size_name ) . ( ! empty( $image_size_data['width'] ) ? ' ' . $image_size_data['width'] . '&times;' . $image_size_data['height'] : '' ) . ( $image_size_data['crop'] ? ' (cropped)' : '' ) . '</option>';
													}

													echo '</select>';
												}
												?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Lightbox for images', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_content_image_lightbox">
                                                    <option
                                                            value="no" <?php echo( get_option( 'woosq_content_image_lightbox', 'no' ) === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="yes" <?php echo( get_option( 'woosq_content_image_lightbox', 'no' ) === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Product summary', 'woosq' ); ?></th>
                                            <td>
                                                <ul>
													<?php $summary = get_option( 'woosq_summary', self::$summary_default ); ?>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="title" <?php echo( is_array( $summary ) && in_array( 'title', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Title', 'woosq' ); ?>
                                                    </li>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="rating" <?php echo( is_array( $summary ) && in_array( 'rating', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Rating', 'woosq' ); ?>
                                                    </li>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="price" <?php echo( is_array( $summary ) && in_array( 'price', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Price', 'woosq' ); ?>
                                                    </li>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="excerpt" <?php echo( is_array( $summary ) && in_array( 'excerpt', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Short description', 'woosq' ); ?>
                                                    </li>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="add_to_cart" <?php echo( is_array( $summary ) && in_array( 'add_to_cart', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Add to cart', 'woosq' ); ?>
                                                    </li>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="meta" <?php echo( is_array( $summary ) && in_array( 'meta', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Meta', 'woosq' ); ?>
                                                    </li>
                                                    <li>
                                                        <input type="checkbox" name="woosq_summary[]"
                                                               value="description" <?php echo( is_array( $summary ) && in_array( 'description', $summary, true ) ? 'checked' : '' ); ?>/>
														<?php esc_html_e( 'Description', 'woosq' ); ?>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Related products', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_related_products">
                                                    <option
                                                            value="yes" <?php echo( get_option( 'woosq_related_products', 'yes' ) === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="no" <?php echo( get_option( 'woosq_related_products', 'yes' ) === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                                <span class="description">
                                                    <?php esc_html_e( 'Show related products.', 'woosq' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Add to cart button', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_add_to_cart_button">
                                                    <option
                                                            value="archive" <?php echo( get_option( 'woosq_add_to_cart_button', 'single' ) === 'archive' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Like archive page', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="single" <?php echo( get_option( 'woosq_add_to_cart_button', 'single' ) === 'single' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Like single page', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                                <span class="description">
                                                    <?php esc_html_e( 'Choose the functionally for the add to cart button.', 'woosq' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'View details button', 'woosq' ); ?></th>
                                            <td>
                                                <select name="woosq_content_view_details_button">
                                                    <option
                                                            value="no" <?php echo( get_option( 'woosq_content_view_details_button', 'no' ) === 'no' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'No', 'woosq' ); ?>
                                                    </option>
                                                    <option
                                                            value="yes" <?php echo( get_option( 'woosq_content_view_details_button', 'no' ) === 'yes' ? 'selected' : '' ); ?>>
														<?php esc_html_e( 'Yes', 'woosq' ); ?>
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'View details text', 'woosq' ); ?></th>
                                            <td>
                                                <input type="text" name="woosq_content_view_details_text"
                                                       value="<?php echo get_option( 'woosq_content_view_details_text', esc_html__( 'View product details', 'woosq' ) ); ?>"
                                                       placeholder="<?php esc_html_e( 'View product details', 'woosq' ); ?>"/>
                                                <span class="description">
                                                    <?php esc_html_e( 'Leave blank to use the default text and can be translated.', 'woosq' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
                                                <input type="submit" name="submit" class="button button-primary"
                                                       value="<?php esc_html_e( 'Update Options', 'woosq' ); ?>"/>
                                                <input type="hidden" name="action" value="update"/>
                                                <input type="hidden" name="page_options"
                                                       value="woosq_button_type,woosq_button_text,woosq_button_class,woosq_button_position,woosq_effect,woosq_perfect_scrollbar,_woosq_cats,woosq_content_image,woosq_image_size,woosq_content_image_lightbox,woosq_summary,woosq_related_products,woosq_add_to_cart_button,woosq_content_view_details_button,woosq_content_view_details_text"/>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab === 'premium' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>Get the Premium Version just $29! <a
                                                href="https://wpclever.net/downloads/woocommerce-smart-quick-view?utm_source=pro&utm_medium=woosq&utm_campaign=wporg"
                                                target="_blank">https://wpclever.net/downloads/woocommerce-smart-quick-view</a>
                                    </p>
                                    <p><strong>Extra features for Premium Version:</strong></p>
                                    <ul style="margin-bottom: 0">
                                        <li>- Add lightbox for images.</li>
                                        <li>- Show/hide the part of content in the popup.</li>
                                        <li>- Add "View Product Details" button.</li>
                                        <li>- Get the lifetime update & premium support.</li>
                                    </ul>
                                </div>
							<?php } ?>
                        </div>
                    </div>
					<?php
				}

				function woosq_wp_enqueue_scripts() {
					wp_enqueue_script( 'wc-add-to-cart-variation' );

					// slick
					wp_enqueue_style( 'slick', WOOSQ_URI . 'assets/libs/slick/slick.css' );
					wp_enqueue_script( 'slick', WOOSQ_URI . 'assets/libs/slick/slick.min.js', array( 'jquery' ), WOOSQ_VERSION, true );

					// perfect srollbar
					if ( get_option( 'woosq_perfect_scrollbar', 'yes' ) === 'yes' ) {
						wp_enqueue_style( 'perfect-scrollbar', WOOSQ_URI . 'assets/libs/perfect-scrollbar/css/perfect-scrollbar.min.css' );
						wp_enqueue_style( 'perfect-scrollbar-wpc', WOOSQ_URI . 'assets/libs/perfect-scrollbar/css/custom-theme.css' );
						wp_enqueue_script( 'perfect-scrollbar', WOOSQ_URI . 'assets/libs/perfect-scrollbar/js/perfect-scrollbar.jquery.min.js', array( 'jquery' ), WOOSQ_VERSION, true );
					}

					// magnific
					wp_enqueue_style( 'magnific-popup', WOOSQ_URI . 'assets/libs/magnific-popup/magnific-popup.css' );
					wp_enqueue_script( 'magnific-popup', WOOSQ_URI . 'assets/libs/magnific-popup/jquery.magnific-popup.min.js', array( 'jquery' ), WOOSQ_VERSION, true );

					// feather icons
					wp_enqueue_style( 'woosq-feather', WOOSQ_URI . 'assets/libs/feather/feather.css' );

					// main style & js
					wp_enqueue_style( 'woosq-frontend', WOOSQ_URI . 'assets/css/frontend.css' );
					wp_enqueue_script( 'woosq-frontend', WOOSQ_URI . 'assets/js/frontend.js', array(
						'jquery',
						'wc-add-to-cart-variation'
					), WOOSQ_VERSION, true );
					wp_localize_script( 'woosq-frontend', 'woosq_vars', array(
							'ajax_url'      => admin_url( 'admin-ajax.php' ),
							'effect'        => get_option( 'woosq_effect', 'mfp-3d-unfold' ),
							'scrollbar'     => get_option( 'woosq_perfect_scrollbar', 'yes' ),
							'cart_url'      => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
							'cart_redirect' => get_option( 'woocommerce_cart_redirect_after_add' ),
							'is_rtl'        => is_rtl()
						)
					);
				}

				function woosq_action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings         = '<a href="' . admin_url( 'admin.php?page=wpclever-woosq&tab=settings' ) . '">' . esc_html__( 'Settings', 'woosq' ) . '</a>';
						$links['premium'] = '<a href="' . admin_url( 'admin.php?page=wpclever-woosq&tab=premium' ) . '">' . esc_html__( 'Premium Version', 'woosq' ) . '</a>';
						array_unshift( $links, $settings );
					}

					return (array) $links;
				}

				function woosq_row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = array(
							'support' => '<a href="' . esc_url( WOOSQ_SUPPORT ) . '" target="_blank">' . esc_html__( 'Support', 'woosq' ) . '</a>',
						);

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				function woosq_get_image_sizes() {
					global $_wp_additional_image_sizes;
					$sizes = array();

					foreach ( get_intermediate_image_sizes() as $_size ) {
						if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
							$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
							$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
							$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
						} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
							$sizes[ $_size ] = array(
								'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
								'height' => $_wp_additional_image_sizes[ $_size ]['height'],
								'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
							);
						}
					}

					return $sizes;
				}
			}

			new WPCleverWoosq();
		}
	}
} else {
	add_action( 'admin_notices', 'woosq_notice_premium' );
}

if ( ! function_exists( 'woosq_notice_wc' ) ) {
	function woosq_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Smart Quick View</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}

if ( ! function_exists( 'woosq_notice_premium' ) ) {
	function woosq_notice_premium() {
		?>
        <div class="error">
            <p>Seems you're using both free and premium version of <strong>WPC Smart Quick View</strong>. Please
                deactivate the free version when using the premium version.</p>
        </div>
		<?php
	}
}