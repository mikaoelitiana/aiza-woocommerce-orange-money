const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
const { __ } = window.wp.i18n;

const Label = () => {
    return createElement('span', { className: 'wc-block-components-payment-method-label' }, __('Orange Money', 'woocommerce-orange-money'));
};

const Content = () => {
    return createElement('div', { className: 'wc-block-components-payment-method-content' }, __('Pay securely using Orange Money.', 'woocommerce-orange-money'));
};

registerPaymentMethod({
    name: 'orange_money',
    label: createElement(Label),
    content: createElement(Content),
    edit: createElement(Content),
    canMakePayment: () => true,
    ariaLabel: __('Orange Money', 'woocommerce-orange-money'),
    supports: {
        features: ['products']
    }
});
