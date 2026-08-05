export default (configuration = {}) => ({
    html: configuration.initialHtml || '',
    uploadUrl: configuration.uploadUrl || '',
    csrfToken: configuration.csrfToken || '',
    uploading: false,
    uploadError: '',
    savedRange: null,
    selectedImage: null,

    init() {
        this.$refs.editor.innerHTML = this.initialDocumentHtml(
            this.html
        );
        this.sync();

        this.$refs.editor.addEventListener('click', (event) => {
            const image = event.target.closest('img');
            this.selectImage(image || null);
        });
    },

    initialDocumentHtml(value) {
        const source = value || '';

        if (/<\/?[A-Za-z][^>]*>/.test(source)) {
            return source;
        }

        if (source.trim() === '') {
            return '<p><br></p>';
        }

        const escape = (text) => {
            const node = document.createElement('div');
            node.textContent = text;
            return node.innerHTML;
        };

        return source
            .replace(/\r\n?/g, '\n')
            .split(/\n{2,}/)
            .map((paragraph) =>
                `<p>${escape(paragraph).replace(/\n/g, '<br>')}</p>`
            )
            .join('');
    },

    rememberSelection() {
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);

        if (this.$refs.editor.contains(range.commonAncestorContainer)) {
            this.savedRange = range.cloneRange();
        }
    },

    restoreSelection() {
        if (!this.savedRange) {
            this.$refs.editor.focus();
            return;
        }

        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(this.savedRange);
    },

    command(name, value = null) {
        this.restoreSelection();
        document.execCommand(name, false, value);
        this.normalizeFontElements();
        this.sync();
        this.rememberSelection();
    },

    formatBlock(tag) {
        this.command('formatBlock', tag);
    },

    applyFontSize(size) {
        if (!size) {
            return;
        }

        this.restoreSelection();
        document.execCommand('fontSize', false, '7');

        this.$refs.editor
            .querySelectorAll('font[size="7"]')
            .forEach((font) => {
                const span = document.createElement('span');
                span.style.fontSize = `${size}px`;
                span.innerHTML = font.innerHTML;
                font.replaceWith(span);
            });

        this.sync();
        this.rememberSelection();
    },

    applyFontFamily(family) {
        if (!family) {
            return;
        }

        this.command('fontName', family);
    },

    addLink() {
        const href = window.prompt(
            'Enter a web address, email link or phone link:'
        );

        if (!href) {
            return;
        }

        if (!/^(https?:\/\/|mailto:|tel:|#)/i.test(href.trim())) {
            this.uploadError =
                'Links must begin with http://, https://, mailto:, tel: or #.';
            return;
        }

        this.uploadError = '';
        this.command('createLink', href.trim());
    },

    normalizeFontElements() {
        this.$refs.editor.querySelectorAll('font').forEach((font) => {
            const span = document.createElement('span');

            if (font.getAttribute('face')) {
                span.style.fontFamily = font.getAttribute('face');
            }

            if (font.getAttribute('size')) {
                const sizeMap = {
                    1: 10,
                    2: 13,
                    3: 16,
                    4: 18,
                    5: 24,
                    6: 32,
                    7: 48,
                };

                span.style.fontSize =
                    `${sizeMap[font.getAttribute('size')] || 16}px`;
            }

            span.innerHTML = font.innerHTML;
            font.replaceWith(span);
        });
    },

    sync() {
        this.normalizeFontElements();
        this.html = this.$refs.editor.innerHTML;
        this.$refs.input.value = this.html;
        this.$refs.input.dispatchEvent(
            new Event('input', { bubbles: true })
        );
    },

    setHtml(html) {
        this.html = html || '<p><br></p>';
        this.$refs.editor.innerHTML = this.initialDocumentHtml(
            this.html
        );
        this.selectImage(null);
        this.sync();
        this.$refs.editor.focus();
    },

    selectImage(image) {
        if (this.selectedImage) {
            this.selectedImage.classList.remove(
                'ec-rich-editor-selected-image'
            );
        }

        this.selectedImage = image;

        if (image) {
            image.classList.add(
                'ec-rich-editor-selected-image'
            );
        }
    },

    setImageWidth(width) {
        if (!this.selectedImage) {
            return;
        }

        this.selectedImage.style.width = width;
        this.selectedImage.style.maxWidth = '100%';
        this.selectedImage.style.height = 'auto';
        this.sync();
    },

    removeSelectedImage() {
        if (!this.selectedImage) {
            return;
        }

        this.selectedImage.remove();
        this.selectedImage = null;
        this.sync();
    },

    async uploadImage(file) {
        this.uploadError = '';

        if (!file) {
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            this.uploadError =
                'Only JPG, PNG and WEBP images are supported.';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            this.uploadError =
                'The image must not exceed 5 MB.';
            return;
        }

        this.rememberSelection();
        this.uploading = true;

        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                const validationError =
                    data.errors?.image?.[0];

                throw new Error(
                    validationError
                    || data.message
                    || 'The image could not be uploaded.'
                );
            }

            const assetPath = String(data.asset_path || '')
                .split('/')
                .map((part) => encodeURIComponent(part))
                .join('/');

            const imageUrl =
                data.asset_disk === 'public' && assetPath
                    ? `${window.location.origin}/storage/${assetPath}`
                    : data.url;

            const image = document.createElement('img');
            image.src = imageUrl;
            image.alt = data.alt || '';
            image.width = data.width;
            image.height = data.height;
            image.dataset.consentAssetPath =
                data.asset_path;
            image.dataset.consentAssetDisk =
                data.asset_disk;
            image.style.maxWidth = '100%';
            image.style.height = 'auto';

            this.restoreSelection();
            document.execCommand(
                'insertHTML',
                false,
                image.outerHTML
            );
            this.sync();
        } catch (error) {
            this.uploadError =
                error.message
                || 'The image could not be uploaded.';
        } finally {
            this.uploading = false;

            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
        }
    },

    handlePaste(event) {
        const files = Array.from(
            event.clipboardData?.files || []
        );

        const image = files.find((file) =>
            file.type.startsWith('image/')
        );

        if (!image) {
            window.setTimeout(() => this.sync(), 0);
            return;
        }

        event.preventDefault();
        this.uploadImage(image);
    },

    handleDrop(event) {
        event.preventDefault();
        event.stopPropagation();

        const files = Array.from(
            event.dataTransfer?.files || []
        );

        const image = files.find((file) =>
            file.type.startsWith('image/')
        );

        if (!image) {
            this.uploadError =
                'Drop a JPG, PNG or WEBP image.';
            return;
        }

        this.uploadImage(image);
    },
});
