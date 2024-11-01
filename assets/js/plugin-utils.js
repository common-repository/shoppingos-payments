function formatResult (state) {
    if (!state.id) { return state.text; }

    default_logo = '/wp-content/plugins/shoppingos-payments/assets/images/banks/bank-default-logo.png';
    var $state = jQuery(
        '<div class="bank-icon"><img src="' + state.logoUri + '" onerror="this.onerror=null; this.src=\'' + default_logo + '\'" class="img-flag" /></div><div class="bank-name"> ' + state.name + '</div>'
      );

   return $state;
};

function formatSelection (state) {
    if (!state.id) { return "Select your bank"; }

    return state.text;
};

function setHidden(checkoutBank) {
    var element = document.getElementById('bank-value');
    element.value = checkoutBank;
}

function initSosSelector() {
    jQuery('.sos-selector-input').select2({
        dropdownCssClass: 'sos-select2',
        placeholder: 'Select your bank',
        width: '100%',
        templateResult: formatResult,
        templateSelection: formatSelection,
        ajax: {
            url: 'https://api.token.io/v2/banks',
            dataType: 'json',
            type: 'GET',
            delay: 300,
            cache: true,
            data: function (params) {
                return {
                    search: params.term,
                    countries: 'GB',
                    supported_payment_networks: 'FASTER_PAYMENTS'
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data['banks'],
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            }
        }
    });

    jQuery('.sos-selector-input').on('select2:select', function (e) {
        var data = e.params.data;
        setHidden(data.id);

        jQuery('form[name="checkout"]').submit();
    });
}

jQuery('body').on('updated_checkout', initSosSelector);
jQuery('body').on('init_checkout', initSosSelector);
document.addEventListener('DOMContentLoaded', initSosSelector);

