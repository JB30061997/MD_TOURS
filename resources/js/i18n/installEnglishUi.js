import generatedTranslations from './en-ui.json';

const manualTranslations = {
    'Occupé': 'Busy',
    'occupé': 'busy',
    'Payée': 'Paid',
    'Non payée': 'Unpaid',
    'Espèce': 'Cash',
    'Facturé': 'Invoiced',
    'Facturée': 'Invoiced',
    'Non facturé': 'Not invoiced',
    'Non facturée': 'Not invoiced',
    'À compléter': 'To complete',
    'Validée': 'Approved',
    'Validées': 'Approved',
    'Annulée': 'Cancelled',
    'Annulées': 'Cancelled',
    'Désactiver': 'Deactivate',
    'Réactiver': 'Reactivate',
    'Chargement...': 'Loading...',
    'Rechercher...': 'Search...',
    'Annuler': 'Cancel',
    'Enregistrer': 'Save',
    'Supprimer': 'Delete',
    'Modifier': 'Edit',
    'Fermer': 'Close',
    'Aucun': 'None',
};

const translations = { ...generatedTranslations, ...manualTranslations };
const translatedAttributes = ['placeholder', 'title', 'aria-label'];
const unsafeTranslation = /\{\{|\}\}|\$\{|\b(?:route|props|planning\.|form\.|can\()|[<>;]/i;
const translatedTextValues = new WeakMap();
const translatedAttributeValues = new WeakMap();

function translate(value) {
    const trimmed = String(value || '').trim();
    const translated = translations[trimmed];
    if (!translated || translated === trimmed || unsafeTranslation.test(translated)) return null;
    return translated;
}

function translateTextNode(node) {
    if (translatedTextValues.get(node) === node.nodeValue) return;
    const translated = translate(node.nodeValue);
    if (!translated) return;
    const leading = node.nodeValue.match(/^\s*/)?.[0] || '';
    const trailing = node.nodeValue.match(/\s*$/)?.[0] || '';
    node.nodeValue = `${leading}${translated}${trailing}`;
    translatedTextValues.set(node, node.nodeValue);
}

function translateElement(element) {
    translatedAttributes.forEach((attribute) => {
        if (!element.hasAttribute(attribute)) return;
        const value = element.getAttribute(attribute);
        const previous = translatedAttributeValues.get(element) || {};
        if (previous[attribute] === value) return;
        const translated = translate(value);
        if (translated) {
            element.setAttribute(attribute, translated);
            translatedAttributeValues.set(element, { ...previous, [attribute]: translated });
        }
    });

    if (element.matches('input[type="button"], input[type="submit"]')) {
        const translated = translate(element.value);
        if (translated) element.value = translated;
    }
}

function translateTree(root) {
    if (root.nodeType === Node.TEXT_NODE) return translateTextNode(root);
    if (root.nodeType !== Node.ELEMENT_NODE && root.nodeType !== Node.DOCUMENT_FRAGMENT_NODE) return;
    if (root.nodeType === Node.ELEMENT_NODE) translateElement(root);

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
        if (node.nodeType === Node.TEXT_NODE) translateTextNode(node);
        else translateElement(node);
    }
}

export function installEnglishUi(root = document.documentElement) {
    document.documentElement.lang = 'en';
    translateTree(root);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'characterData') translateTextNode(mutation.target);
            mutation.addedNodes.forEach(translateTree);
            if (mutation.type === 'attributes') translateElement(mutation.target);
        });
    });

    observer.observe(root, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true,
        attributeFilter: translatedAttributes,
    });

    return () => observer.disconnect();
}
