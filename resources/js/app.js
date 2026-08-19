

import Alpine from 'alpinejs';
import anchor from '@alpinejs/anchor';
import consentTemplateRichEditor from './consent-template-rich-editor';

window.Alpine = Alpine;

Alpine.plugin(anchor);

Alpine.data(
    'consentTemplateRichEditor',
    consentTemplateRichEditor
);

Alpine.start();
import './econsent-analytics-theme';
import './bulk-consent-recipients';
import './bulk-consent-deadline';
