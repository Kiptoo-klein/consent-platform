

import Alpine from 'alpinejs';
import consentTemplateRichEditor from './consent-template-rich-editor';

window.Alpine = Alpine;

Alpine.data(
    'consentTemplateRichEditor',
    consentTemplateRichEditor
);

Alpine.start();
import './econsent-analytics-theme';
import './bulk-consent-recipients';
import './bulk-consent-deadline';
