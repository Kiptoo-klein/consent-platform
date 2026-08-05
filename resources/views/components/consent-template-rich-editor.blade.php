@props([
    'value' => '',
    'label' => 'Consent Text',
])

@php
    $csrfToken = csrf_token();
@endphp

<div
    class="ec-rich-editor"
    x-data="consentTemplateRichEditor({
        initialHtml: @js((string) $value),
        uploadUrl: @js(route('consent-templates.upload-image')),
        csrfToken: @js($csrfToken),
    })"
    x-init="init()"
    @econsent-set-rich-content.window="
        if (
            !$event.detail.id
            || $event.detail.id === 'content'
        ) {
            setHtml($event.detail.html);
        }
    "
>
    <label
        for="content-editor"
        class="block font-semibold mb-2"
    >
        {{ $label }}
    </label>

    <div
        class="ec-rich-editor-toolbar"
        role="toolbar"
        aria-label="Consent document formatting"
    >
        <select
            aria-label="Text style"
            @change="
                formatBlock($event.target.value);
                $event.target.value = '';
            "
        >
            <option value="">Text style</option>
            <option value="p">Paragraph</option>
            <option value="h1">Heading 1</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
        </select>

        <select
            aria-label="Font size"
            @change="
                applyFontSize($event.target.value);
                $event.target.value = '';
            "
        >
            <option value="">Font size</option>
            <option value="10">10</option>
            <option value="12">12</option>
            <option value="14">14</option>
            <option value="16">16</option>
            <option value="18">18</option>
            <option value="24">24</option>
            <option value="32">32</option>
            <option value="48">48</option>
        </select>

        <select
            aria-label="Font family"
            @change="
                applyFontFamily($event.target.value);
                $event.target.value = '';
            "
        >
            <option value="">Font</option>
            <option value="Arial">Arial</option>
            <option value="Georgia">Georgia</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Verdana">Verdana</option>
        </select>

        <button type="button" title="Bold" @mousedown.prevent="command('bold')">
            <strong>B</strong>
        </button>

        <button type="button" title="Italic" @mousedown.prevent="command('italic')">
            <em>I</em>
        </button>

        <button type="button" title="Underline" @mousedown.prevent="command('underline')">
            <u>U</u>
        </button>

        <button type="button" title="Strikethrough" @mousedown.prevent="command('strikeThrough')">
            <s>S</s>
        </button>

        <button type="button" title="Bulleted list" @mousedown.prevent="command('insertUnorderedList')">
            • List
        </button>

        <button type="button" title="Numbered list" @mousedown.prevent="command('insertOrderedList')">
            1. List
        </button>

        <button type="button" title="Align left" @mousedown.prevent="command('justifyLeft')">
            Left
        </button>

        <button type="button" title="Align center" @mousedown.prevent="command('justifyCenter')">
            Center
        </button>

        <button type="button" title="Align right" @mousedown.prevent="command('justifyRight')">
            Right
        </button>

        <button type="button" title="Add link" @mousedown.prevent="addLink()">
            Link
        </button>

        <button type="button" title="Remove link" @mousedown.prevent="command('unlink')">
            Unlink
        </button>

        <button type="button" title="Undo" @mousedown.prevent="command('undo')">
            Undo
        </button>

        <button type="button" title="Redo" @mousedown.prevent="command('redo')">
            Redo
        </button>

        <input
            x-ref="imageInput"
            type="file"
            class="hidden"
            accept="image/jpeg,image/png,image/webp"
            @change="uploadImage($event.target.files[0])"
        >

        <button
            type="button"
            title="Insert image"
            :disabled="uploading"
            @mousedown.prevent="
                rememberSelection();
                $refs.imageInput.click();
            "
        >
            <span x-show="!uploading">Image</span>
            <span x-show="uploading" x-cloak>Uploading…</span>
        </button>
    </div>

    <div
        x-show="selectedImage"
        x-cloak
        class="ec-rich-editor-image-controls"
    >
        <span>Selected image:</span>
        <button type="button" @click="setImageWidth('25%')">Small</button>
        <button type="button" @click="setImageWidth('50%')">Medium</button>
        <button type="button" @click="setImageWidth('75%')">Large</button>
        <button type="button" @click="setImageWidth('100%')">Full width</button>
        <button type="button" class="text-red-700" @click="removeSelectedImage()">
            Remove
        </button>
    </div>

    <div
        id="content-editor"
        x-ref="editor"
        class="ec-rich-editor-content consent-rich-content"
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
        spellcheck="true"
        @input="sync(); rememberSelection()"
        @keyup="rememberSelection()"
        @mouseup="rememberSelection()"
        @focus="rememberSelection()"
        @paste="handlePaste($event)"
        @dragover.prevent="
            $event.dataTransfer.dropEffect = 'copy'
        "
        @drop.prevent.stop="handleDrop($event)"
        @blur="sync()"
    ></div>

    <textarea
        id="content"
        name="content"
        x-ref="input"
        x-model="html"
        class="hidden"
        aria-hidden="true"
        tabindex="-1"
    ></textarea>

    <p class="mt-2 text-xs text-gray-500">
        Format the document like a word processor. Images may be uploaded directly or imported from a Word document.
    </p>

    <p
        x-show="uploadError"
        x-cloak
        class="mt-2 text-sm font-medium text-red-600"
        x-text="uploadError"
    ></p>
</div>
