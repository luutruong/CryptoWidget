!function ($, window, document, _undefined) {
    // noinspection SpellCheckingInspection
    XF.CryptoWidget_Search = XF.extend(XF.TokenInput, {
        __backup: {
            'init': '_baseInit'
        },

        options: $.extend({}, XF.TokenInput.prototype.options, {
            allowCryptos: [],
            value: []
        }),

        init: function () {
            // noinspection JSUnresolvedFunction
            this._baseInit();

            var $tokensSelect = this.$hiddenInput.prev().prev(), _this = this;
            if ($tokensSelect.attr('name') !== 'tokens_select') {
                return;
            }

            $.each(this.options.allowCryptos, function (index, item) {
                var $option = $('<option />').val(item.id)
                                             .text(item.name)
                                             .attr('data-select2-id', item.id);

                $option.prop('selected', _this.options.value.indexOf(item.id) >= 0);
                $option.appendTo($tokensSelect);
            });

            var api = $tokensSelect.data('select2'),
                existingOptions = api.options.options;

            existingOptions.tags = false;
            $tokensSelect.select2(existingOptions);

            $tokensSelect.on('select2:selecting', $.proxy(this, 'verifySelectValue'));

            var $results = $tokensSelect.data('select2').$results;
            $results.css({
                maxHeight: '200px',
                overflowY: 'auto'
            });
        },

        verifySelectValue: function (event) {
            var params = event.params.args;

            if (params.data) {
                var cryptos = this.options.allowCryptos.filter(function (item) {
                    return item.id == params.data.id;
                });

                if (cryptos.length === 0) {
                    event.preventDefault();
                }
            }
        }
    });

    // noinspection SpellCheckingInspection
    XF.Element.register('crypto-widget-search', 'XF.CryptoWidget_Search');
}
(jQuery, this, document);