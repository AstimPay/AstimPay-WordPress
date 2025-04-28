const astimpay_settings = window.wc.wcSettings.getSetting('astimpay_data', {});

const astimpay_label = window.wp.htmlEntities.decodeEntities(astimpay_settings.title) || 'Mobile Banking';
const AstimPayContent = () => {
    return window.wp.htmlEntities.decodeEntities(astimpay_settings.description || '');
};

const AstimPayBlock = {
    name: 'astimpay',
    label: astimpay_label,
    content: Object(window.wp.element.createElement)(AstimPayContent, null),
    edit: Object(window.wp.element.createElement)(AstimPayContent, null),
    canMakePayment: () => true,
    ariaLabel: astimpay_label,
    supports: astimpay_settings.supports,
};

window.wc.wcBlocksRegistry.registerPaymentMethod(AstimPayBlock);