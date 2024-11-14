jQuery(document).ready(function($) {
    var $inlineEdit = inlineEditTax.edit;
    inlineEditTax.edit = function( id ) {
        $inlineEdit.apply( this, arguments );
        var termId = 0;
        if ( typeof( id ) === 'object' ) {
            termId = parseInt( this.getId( id ) );
        }
        if ( termId > 0 ) {
            var $editRow = $( '#edit-' + termId );
            var $dataRow = $( '#inline_' + termId );
            var priceAdjustment = $dataRow.find( '.fapa_price_adjustment' ).text();
            var adjustmentType = $dataRow.find( '.fapa_adjustment_type' ).text();

            // Check if the fields exist, if not, add them
            if ( $editRow.find( 'input[name="fapa_price_adjustment"]' ).length === 0 ) {
                var $fields = $(
                    '<label class="inline-edit-group">' +
                        '<span class="title">Price Adjustment</span>' +
                        '<span class="input-text-wrap">' +
                            '<input type="number" step="0.01" name="fapa_price_adjustment" value="">' +
                        '</span>' +
                    '</label>' +
                    '<label class="inline-edit-group">' +
                        '<span class="title">Adjustment Type</span>' +
                        '<span class="input-text-wrap">' +
                            '<select name="fapa_adjustment_type">' +
                                '<option value="fixed">Fixed Amount</option>' +
                                '<option value="percentage">Percentage</option>' +
                            '</select>' +
                        '</span>' +
                    '</label>'
                );
                $editRow.find( '.inline-edit-col-left' ).append( $fields );
            }

            $editRow.find( 'input[name="fapa_price_adjustment"]' ).val( priceAdjustment );
            $editRow.find( 'select[name="fapa_adjustment_type"]' ).val( adjustmentType );
        }
    };
});
