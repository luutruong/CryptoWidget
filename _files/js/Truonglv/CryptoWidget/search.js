!(function($, window, document, _undefined) {
    // noinspection SpellCheckingInspection
    XF.CryptoWidget_Search = XF.extend(XF.TokenInput, {
        __backup: {
            init: '_baseInit',
            updateInput: '_updateInput',
        },

        options: $.extend({}, XF.TokenInput.prototype.options, {
            allowCryptos: [],
            value: [],
            iconTemplate: '',
            iconSelector: '',
        }),

        $icons: [],
        $iconsHtml: null,

        init: function() {
            // noinspection JSUnresolvedFunction
            this._baseInit();

            var $tokensSelect = this.$hiddenInput.prev().prev(),
                _this = this;
            if ($tokensSelect.attr('name') !== 'tokens_select') {
                return;
            }

            this.$iconsHtml = $(this.options.iconSelector);
            this.$icons = this.$iconsHtml.children();

            $.each(this.options.allowCryptos, function(index, item) {
                var $option = $('<option />')
                    .val(item.id)
                    .text(item.name)
                    .attr('data-select2-id', item.id);

                $option.prop('selected', _this.options.value.indexOf(item.id) >= 0);
                $option.appendTo($tokensSelect);
                $tokensSelect.trigger('change');
            });

            var api = $tokensSelect.data('select2'),
                existingOptions = api.options.options;

            existingOptions.tags = false;
            $tokensSelect.select2(existingOptions);

            $tokensSelect.on('select2:selecting', $.proxy(this, 'verifySelectValue'));

            var $results = $tokensSelect.data('select2').$results;
            $results.css({
                maxHeight: '200px',
                overflowY: 'auto',
            });
        },

        verifySelectValue: function(event) {
            var params = event.params.args;

            if (params.data) {
                var cryptos = this.options.allowCryptos.filter(function(item) {
                    return item.id === params.data.id;
                });

                if (cryptos.length === 0) {
                    event.preventDefault();
                }
            }
        },

        updateInput: function(e) {
            this._updateInput(e);

            var values = $(e.target).val(),
                $newIcons = [],
                _this = this;

            for (var i = 0; i < this.$icons.length; i++) {
                var $icon = $(this.$icons[i]),
                    id = String($icon.data('id')),
                    index = values.indexOf(id);

                if (index === -1) {
                    // need remove.
                    $icon.remove();
                } else {
                    $newIcons.push($icon);
                    values.splice(index, 1);
                }
            }

            if (values.length > 0) {
                // some values has added
                for (var j = 0; j < values.length; j++) {
                    var items = this.options.allowCryptos.filter(function(element) {
                        return element.id === values[j];
                    });

                    items.forEach(function(item) {
                        var template = _this.options.iconTemplate,
                            $newIcon;

                        template = template
                            .replace('{name}', item.name)
                            .replace('{id}', item.id)
                            .replace('{input}', 'options[icons][' + item.id + ']');

                        $newIcon = $(template);
                        $newIcon.appendTo(_this.$iconsHtml);
                        $newIcons.push($newIcon);
                    });
                }
            }

            this.$icons = $newIcons;
        },
    });

    // noinspection SpellCheckingInspection
    XF.Element.register('crypto-widget-search', 'XF.CryptoWidget_Search');
})(jQuery, this, document);
