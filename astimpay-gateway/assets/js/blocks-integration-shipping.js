const astimpay_shipping_only_settings = window.wc.wcSettings.getSetting('astimpay_shipping_only_data', {});

const astimpay_shipping_only_label = window.wp.htmlEntities.decodeEntities(astimpay_shipping_only_settings.title) || 'Mobile Banking (Shipping Only)';
const AstimPayShippingOnlyContent = () => {
    return window.wp.htmlEntities.decodeEntities(astimpay_shipping_only_settings.description || '');
};

const AstimPayShippingOnlyBlock = {
    name: 'astimpay_shipping_only',
    label: astimpay_shipping_only_label,
    content: Object(window.wp.element.createElement)(AstimPayShippingOnlyContent, null),
    edit: Object(window.wp.element.createElement)(AstimPayShippingOnlyContent, null),
    canMakePayment: () => true,
    ariaLabel: astimpay_shipping_only_label,
    supports: astimpay_shipping_only_settings.supports,
};

// Register both payment methods
window.wc.wcBlocksRegistry.registerPaymentMethod(AstimPayShippingOnlyBlock);