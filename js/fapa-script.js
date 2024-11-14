jQuery(document).ready(function($){
    var $variationForm = $('form.variations_form');

    $variationForm.on('change', '.variations select', function(){
        var data = {
            action: 'fapa_update_price',
            product_id: $variationForm.find('input[name="product_id"]').val(),
            attributes: $variationForm.serialize()
        };

        $.post(fapa_ajax_obj.ajax_url, data, function(response) {
            if ( response.success ) {
                // Update the price displayed on the product page
                $('.woocommerce-Price-amount.amount').first().html(response.data.new_price_html);
            }
        });
    });
});
