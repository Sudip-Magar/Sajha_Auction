import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableCell } from '@tiptap/extension-table-cell';

const TOOLBAR_ACTIONS = [
    { label: 'Bold', text: 'B', run: (editor) => editor.chain().focus().toggleBold().run(), active: (editor) => editor.isActive('bold') },
    { label: 'Italic', text: 'I', run: (editor) => editor.chain().focus().toggleItalic().run(), active: (editor) => editor.isActive('italic') },
    { label: 'Strikethrough', text: 'S', run: (editor) => editor.chain().focus().toggleStrike().run(), active: (editor) => editor.isActive('strike') },
    { label: 'Heading', text: 'H2', run: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run(), active: (editor) => editor.isActive('heading', { level: 2 }) },
    { label: 'Subheading', text: 'H3', run: (editor) => editor.chain().focus().toggleHeading({ level: 3 }).run(), active: (editor) => editor.isActive('heading', { level: 3 }) },
    { label: 'Bullet List', text: '••', run: (editor) => editor.chain().focus().toggleBulletList().run(), active: (editor) => editor.isActive('bulletList') },
    { label: 'Numbered List', text: '1.', run: (editor) => editor.chain().focus().toggleOrderedList().run(), active: (editor) => editor.isActive('orderedList') },
    { label: 'Quote', text: '“”', run: (editor) => editor.chain().focus().toggleBlockquote().run(), active: (editor) => editor.isActive('blockquote') },
    { label: 'Undo', text: '↶', run: (editor) => editor.chain().focus().undo().run(), active: null },
    { label: 'Redo', text: '↷', run: (editor) => editor.chain().focus().redo().run(), active: null },
    { divider: true },
    {
        label: 'Insert Table',
        text: '⊞',
        run: (editor) => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        active: (editor) => editor.isActive('table'),
    },
    {
        label: 'Add Column',
        text: '+Col',
        run: (editor) => editor.chain().focus().addColumnAfter().run(),
        active: null,
        enabled: (editor) => editor.can().addColumnAfter(),
    },
    {
        label: 'Delete Column',
        text: '-Col',
        run: (editor) => editor.chain().focus().deleteColumn().run(),
        active: null,
        enabled: (editor) => editor.can().deleteColumn(),
    },
    {
        label: 'Add Row',
        text: '+Row',
        run: (editor) => editor.chain().focus().addRowAfter().run(),
        active: null,
        enabled: (editor) => editor.can().addRowAfter(),
    },
    {
        label: 'Delete Row',
        text: '-Row',
        run: (editor) => editor.chain().focus().deleteRow().run(),
        active: null,
        enabled: (editor) => editor.can().deleteRow(),
    },
    {
        label: 'Delete Table',
        text: '⌫Tbl',
        run: (editor) => editor.chain().focus().deleteTable().run(),
        active: null,
        enabled: (editor) => editor.can().deleteTable(),
    },
];

function buildToolbar(toolbarEl, editor) {
    TOOLBAR_ACTIONS.forEach((action) => {
        if (action.divider) {
            const divider = document.createElement('span');
            divider.className = 'tiptap-toolbar-divider';
            toolbarEl.appendChild(divider);

            return;
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.title = action.label;
        btn.textContent = action.text;
        btn.className = 'tiptap-toolbar-btn';
        // Prevent the button from stealing DOM focus on mousedown, which would
        // collapse the editor's current selection before the click handler runs.
        btn.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            action.run(editor);
        });
        toolbarEl.appendChild(btn);

        if (action.active || action.enabled) {
            editor.on('transaction', () => {
                if (action.active) {
                    btn.classList.toggle('is-active', action.active(editor));
                }
                if (action.enabled) {
                    btn.disabled = ! action.enabled(editor);
                }
            });
        }
    });
}

function attachEditor(root) {
    if (root.dataset.tiptapInitialized) {
        return;
    }

    const key = root.dataset.tiptapEditor;
    const hiddenInput = document.querySelector(`[data-tiptap-input="${key}"]`);
    const contentEl = root.querySelector('[data-tiptap-content]');
    const toolbarEl = root.querySelector('[data-tiptap-toolbar]');

    if (!hiddenInput || !contentEl) {
        return;
    }

    const editor = new Editor({
        element: contentEl,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
            }),
            Placeholder.configure({
                placeholder: root.dataset.tiptapPlaceholder || '',
            }),
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        content: hiddenInput.value || '',
        onUpdate: ({ editor }) => {
            hiddenInput.value = editor.isEmpty ? '' : editor.getHTML();
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        },
    });

    root._tiptapEditor = editor;
    root.dataset.tiptapInitialized = 'true';

    if (toolbarEl) {
        buildToolbar(toolbarEl, editor);
    }
}

function destroyEditors() {
    document.querySelectorAll('[data-tiptap-editor]').forEach((root) => {
        if (root._tiptapEditor) {
            root._tiptapEditor.destroy();
            delete root._tiptapEditor;
            delete root.dataset.tiptapInitialized;
        }
    });
}

window.initTiptapEditors = () => {
    document.querySelectorAll('[data-tiptap-editor]').forEach(attachEditor);
};

document.addEventListener('DOMContentLoaded', window.initTiptapEditors);
document.addEventListener('livewire:navigated', window.initTiptapEditors);
document.addEventListener('livewire:navigating', destroyEditors);
