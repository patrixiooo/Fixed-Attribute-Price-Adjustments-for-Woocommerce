<?php
/**
 * Plugin Name: Fixed Attribute Price Adjustments
 * Description: Adds fixed amount or percentage price increases to WooCommerce products based on selected attributes.
 * Version: 2.7
 * Author: Patryk Czemarnik
 * Text Domain: fixed-attribute-price-adjustments
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Adjust cart item prices based on attributes
add_action( 'woocommerce_before_calculate_totals', 'fapa_adjust_cart_item_prices', 20, 1 );

function fapa_adjust_cart_item_prices( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        $attributes = $cart_item['variation'];
        $additional_price = 0;

        // Loop through each attribute
        foreach ( $attributes as $attribute_name => $attribute_value ) {
            // Get taxonomy and term
            $taxonomy = str_replace( 'attribute_', '', $attribute_name );
            $term = get_term_by( 'slug', $attribute_value, $taxonomy );

            if ( $term ) {
                // Get price adjustment for the term
                $price_adjustment = get_term_meta( $term->term_id, 'fapa_price_adjustment', true );
                $adjustment_type = get_term_meta( $term->term_id, 'fapa_adjustment_type', true );

                // If adjustment type is not set, default to 'fixed' for backward compatibility
                if ( ! $adjustment_type ) {
                    $adjustment_type = 'fixed';
                }

                if ( $price_adjustment !== '' ) {
                    $price_adjustment = floatval( $price_adjustment );
                    $product_price = $product->get_price();

                    if ( $adjustment_type === 'percentage' ) {
                        $additional_price += ( $product_price * $price_adjustment ) / 100;
                    } elseif ( $adjustment_type === 'fixed' ) {
                        $additional_price += $price_adjustment;
                    }
                }
            }
        }

        if ( $additional_price != 0 ) {
            $original_price = $product->get_price();
            $new_price = $original_price + $additional_price;
            $product->set_price( $new_price );
        }
    }
}

// Enqueue custom JavaScript files
add_action( 'admin_enqueue_scripts', 'fapa_admin_enqueue_scripts' );
add_action( 'wp_enqueue_scripts', 'fapa_enqueue_scripts' );

function fapa_admin_enqueue_scripts( $hook ) {
    if ( 'edit-tags.php' === $hook && isset( $_GET['taxonomy'] ) && strpos( $_GET['taxonomy'], 'pa_' ) === 0 ) {
        wp_enqueue_script( 'fapa-quick-edit', plugin_dir_url( __FILE__ ) . 'js/fapa-quick-edit.js', array( 'jquery' ), '2.7', true );
    }
}

function fapa_enqueue_scripts() {
    if ( is_product() ) {
        wp_enqueue_script( 'fapa-script', plugin_dir_url( __FILE__ ) . 'js/fapa-script.js', array( 'jquery' ), '2.7', true );
        wp_localize_script( 'fapa-script', 'fapa_ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }
}

// Handle AJAX request to update price on the product page
add_action( 'wp_ajax_fapa_update_price', 'fapa_update_price' );
add_action( 'wp_ajax_nopriv_fapa_update_price', 'fapa_update_price' );

function fapa_update_price() {
    if ( ! isset( $_POST['product_id'] ) ) {
        wp_send_json_error();
    }

    $product_id = intval( $_POST['product_id'] );
    $product = wc_get_product( $product_id );

    parse_str( $_POST['attributes'], $attributes );

    $additional_price = 0;

    // Loop through each attribute in the request
    foreach ( $attributes as $attribute_name => $attribute_value ) {
        if ( strpos( $attribute_name, 'attribute_' ) !== false ) {
            $taxonomy = str_replace( 'attribute_', '', $attribute_name );
            $term = get_term_by( 'slug', $attribute_value, $taxonomy );

            if ( $term ) {
                $price_adjustment = get_term_meta( $term->term_id, 'fapa_price_adjustment', true );
                $adjustment_type = get_term_meta( $term->term_id, 'fapa_adjustment_type', true );

                // If adjustment type is not set, default to 'fixed' for backward compatibility
                if ( ! $adjustment_type ) {
                    $adjustment_type = 'fixed';
                }

                if ( $price_adjustment !== '' ) {
                    $price_adjustment = floatval( $price_adjustment );
                    $product_price = $product->get_price();

                    if ( $adjustment_type === 'percentage' ) {
                        $additional_price += ( $product_price * $price_adjustment ) / 100;
                    } elseif ( $adjustment_type === 'fixed' ) {
                        $additional_price += $price_adjustment;
                    }
                }
            }
        }
    }

    $original_price = $product->get_price();
    $new_price = $original_price + $additional_price;
    $new_price_html = wc_price( $new_price );

    wp_send_json_success( array( 'new_price_html' => $new_price_html ) );
}

// Attach custom fields and columns to all product attributes
function fapa_add_attribute_hooks() {
    $attribute_taxonomies = wc_get_attribute_taxonomies();

    if ( ! $attribute_taxonomies ) {
        return;
    }

    foreach ( $attribute_taxonomies as $attribute ) {
        $taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );

        // Add fields to Add New form
        add_action( "{$taxonomy}_add_form_fields", 'fapa_add_attribute_field', 10 );
        add_action( "created_{$taxonomy}", 'fapa_save_attribute_field', 10, 2 );

        // Add fields to Edit Term form
        add_action( "{$taxonomy}_edit_form_fields", 'fapa_edit_attribute_field', 10, 2 );
        add_action( "edited_{$taxonomy}", 'fapa_save_attribute_field', 10, 2 );

        // Add custom column to attribute terms list
        add_filter( "manage_edit-{$taxonomy}_columns", 'fapa_add_custom_columns' );
        add_filter( "manage_{$taxonomy}_custom_column", 'fapa_custom_column_content', 10, 3 );

        // Add Quick Edit custom box
        add_action( 'quick_edit_custom_box', 'fapa_quick_edit_custom_box', 10, 3 );
    }
}
add_action( 'init', 'fapa_add_attribute_hooks' );

// Add custom fields to Add New term form
function fapa_add_attribute_field( $taxonomy ) {
    ?>
    <div class="form-field term-fapa-price-adjustment-wrap">
        <label for="fapa-price-adjustment"><?php esc_html_e( 'Price Adjustment', 'fixed-attribute-price-adjustments' ); ?></label>
        <input type="number" step="0.01" name="fapa_price_adjustment" id="fapa-price-adjustment" value="">
        <p class="description"><?php esc_html_e( 'Enter a fixed amount or percentage for the price adjustment.', 'fixed-attribute-price-adjustments' ); ?></p>
    </div>
    <div class="form-field term-fapa-adjustment-type-wrap">
        <label for="fapa-adjustment-type"><?php esc_html_e( 'Adjustment Type', 'fixed-attribute-price-adjustments' ); ?></label>
        <select name="fapa_adjustment_type" id="fapa-adjustment-type">
            <option value="fixed"><?php esc_html_e( 'Fixed Amount', 'fixed-attribute-price-adjustments' ); ?></option>
            <option value="percentage"><?php esc_html_e( 'Percentage', 'fixed-attribute-price-adjustments' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Select whether the adjustment is a fixed amount or a percentage.', 'fixed-attribute-price-adjustments' ); ?></p>
    </div>
    <?php
}

// Add custom fields to Edit Term form
function fapa_edit_attribute_field( $term, $taxonomy ) {
    $price_adjustment = get_term_meta( $term->term_id, 'fapa_price_adjustment', true );
    $adjustment_type = get_term_meta( $term->term_id, 'fapa_adjustment_type', true );
    if ( ! $adjustment_type ) {
        $adjustment_type = 'fixed';
    }
    ?>
    <tr class="form-field term-fapa-price-adjustment-wrap">
        <th scope="row">
            <label for="fapa-price-adjustment"><?php esc_html_e( 'Price Adjustment', 'fixed-attribute-price-adjustments' ); ?></label>
        </th>
        <td>
            <input type="number" step="0.01" name="fapa_price_adjustment" id="fapa-price-adjustment" value="<?php echo esc_attr( $price_adjustment ); ?>">
            <p class="description"><?php esc_html_e( 'Enter a fixed amount or percentage for the price adjustment.', 'fixed-attribute-price-adjustments' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-fapa-adjustment-type-wrap">
        <th scope="row">
            <label for="fapa-adjustment-type"><?php esc_html_e( 'Adjustment Type', 'fixed-attribute-price-adjustments' ); ?></label>
        </th>
        <td>
            <select name="fapa_adjustment_type" id="fapa-adjustment-type">
                <option value="fixed" <?php selected( $adjustment_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'fixed-attribute-price-adjustments' ); ?></option>
                <option value="percentage" <?php selected( $adjustment_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'fixed-attribute-price-adjustments' ); ?></option>
            </select>
            <p class="description"><?php esc_html_e( 'Select whether the adjustment is a fixed amount or a percentage.', 'fixed-attribute-price-adjustments' ); ?></p>
        </td>
    </tr>
    <?php
}

// Save the custom field values
function fapa_save_attribute_field( $term_id ) {
    if ( isset( $_POST['fapa_price_adjustment'] ) ) {
        update_term_meta( $term_id, 'fapa_price_adjustment', sanitize_text_field( $_POST['fapa_price_adjustment'] ) );
    }
    if ( isset( $_POST['fapa_adjustment_type'] ) ) {
        update_term_meta( $term_id, 'fapa_adjustment_type', sanitize_text_field( $_POST['fapa_adjustment_type'] ) );
    }
}

// Add custom column to attribute terms list
function fapa_add_custom_columns( $columns ) {
    $columns['fapa_price_adjustment'] = __( 'Price Adjustment', 'fixed-attribute-price-adjustments' );
    return $columns;
}

// Display custom column content
function fapa_custom_column_content( $content, $column_name, $term_id ) {
    if ( 'fapa_price_adjustment' === $column_name ) {
        $price_adjustment = get_term_meta( $term_id, 'fapa_price_adjustment', true );
        $adjustment_type = get_term_meta( $term_id, 'fapa_adjustment_type', true );
        if ( ! $adjustment_type ) {
            $adjustment_type = 'fixed';
        }
        if ( $price_adjustment !== '' ) {
            $display_value = esc_html( $price_adjustment );
            $display_value .= $adjustment_type === 'percentage' ? '%' : '';
            $content = $display_value;
        } else {
            $content = '—';
        }
        // Output hidden data for Quick Edit
        echo '<div id="inline_' . $term_id . '" class="hidden">';
        echo '<div class="fapa_price_adjustment">' . esc_attr( $price_adjustment ) . '</div>';
        echo '<div class="fapa_adjustment_type">' . esc_attr( $adjustment_type ) . '</div>';
        echo '</div>';
    }
    return $content;
}

// Add custom fields to Quick Edit form
function fapa_quick_edit_custom_box( $column_name, $screen, $taxonomy ) {
    if ( $column_name == 'fapa_price_adjustment' ) {
        ?>
        <fieldset>
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php esc_html_e( 'Price Adjustment', 'fixed-attribute-price-adjustments' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="number" step="0.01" name="fapa_price_adjustment" value="">
                    </span>
                </label>
                <label>
                    <span class="title"><?php esc_html_e( 'Adjustment Type', 'fixed-attribute-price-adjustments' ); ?></span>
                    <span class="input-text-wrap">
                        <select name="fapa_adjustment_type">
                            <option value="fixed"><?php esc_html_e( 'Fixed Amount', 'fixed-attribute-price-adjustments' ); ?></option>
                            <option value="percentage"><?php esc_html_e( 'Percentage', 'fixed-attribute-price-adjustments' ); ?></option>
                        </select>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }
}

// Save the custom field values on Quick Edit
add_action( 'created_term', 'fapa_save_attribute_field', 10, 2 );
add_action( 'edited_term', 'fapa_save_attribute_field', 10, 2 );

// Add menu item under WooCommerce for plugin settings
add_action( 'admin_menu', 'fapa_add_settings_page' );

function fapa_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __( 'Fixed Attribute Price Adjustments', 'fixed-attribute-price-adjustments' ),
        __( 'Fixed Attribute Price Adjustments', 'fixed-attribute-price-adjustments' ),
        'manage_options',
        'fapa-settings',
        'fapa_settings_page_content'
    );
}

// Display the settings page content
function fapa_settings_page_content() {
    // Save settings if form is submitted
    if ( isset( $_POST['fapa_settings_nonce'] ) && wp_verify_nonce( $_POST['fapa_settings_nonce'], 'fapa_save_settings' ) ) {
        $display_price_adjustments = isset( $_POST['fapa_display_price_adjustments'] ) ? 'yes' : 'no';
        update_option( 'fapa_display_price_adjustments', $display_price_adjustments );
        echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'fixed-attribute-price-adjustments' ) . '</p></div>';
    }

    // Get current setting
    $display_price_adjustments = get_option( 'fapa_display_price_adjustments', 'yes' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Fixed Attribute Price Adjustments', 'fixed-attribute-price-adjustments' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'fapa_save_settings', 'fapa_settings_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Display Price Adjustments', 'fixed-attribute-price-adjustments' ); ?></th>
                    <td>
                        <input type="checkbox" name="fapa_display_price_adjustments" value="yes" <?php checked( $display_price_adjustments, 'yes' ); ?> />
                        <label for="fapa_display_price_adjustments"><?php esc_html_e( 'Enable to display price adjustments next to attribute terms on the product page.', 'fixed-attribute-price-adjustments' ); ?></label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Modify attribute terms displayed on the product page, compatible with Swatches Variations plugin
add_filter( 'woocommerce_variation_option_name', 'fapa_adjust_variation_option_name', 10, 2 );

function fapa_adjust_variation_option_name( $term_name, $term ) {
    // Check if display is enabled
    $display_price_adjustments = get_option( 'fapa_display_price_adjustments', 'yes' );

    if ( 'no' === $display_price_adjustments ) {
        return $term_name; // Return original term name if display is disabled
    }

    if ( is_numeric( $term ) ) {
        $term = get_term( $term );
    }

    if ( $term && ! is_wp_error( $term ) ) {
        $price_adjustment = get_term_meta( $term->term_id, 'fapa_price_adjustment', true );
        $adjustment_type = get_term_meta( $term->term_id, 'fapa_adjustment_type', true );

        if ( ! $adjustment_type ) {
            $adjustment_type = 'fixed';
        }

        if ( $price_adjustment !== '' ) {
            if ( $adjustment_type === 'percentage' ) {
                $term_name .= ' (+' . floatval( $price_adjustment ) . '%)';
            } else {
                // Get the currency symbol
                $currency_symbol = get_woocommerce_currency_symbol();
                // Format the price adjustment as plain text
                $formatted_price = wc_format_localized_price( $price_adjustment );
                $term_name .= ' (+' . $formatted_price . ' ' . $currency_symbol . ')';
            }
        }
    }

    return $term_name;
}
