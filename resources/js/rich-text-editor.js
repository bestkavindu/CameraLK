import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'

/**
 * An Alpine component wrapping Tiptap.
 *
 * The editor lives inside a `wire:ignore` element so Livewire never diffs the
 * ProseMirror DOM. Content flows one way on each side: keystrokes push HTML into
 * the Livewire property, and the server pushes content back only when it
 * explicitly dispatches a `rich-text:set` browser event (opening a record for
 * editing, or resetting the form).
 */
export default function richTextEditor({ model, content = '' }) {
    return {
        editor: null,

        /** Mirrors the editor state so the toolbar can render active styles. */
        active: {},

        init() {
            this.editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [2, 3, 4] },
                        link: {
                            openOnClick: false,
                            autolink: true,
                            defaultProtocol: 'https',
                        },
                    }),
                ],
                content: content || '',
                editorProps: {
                    attributes: {
                        class: 'rich-text-content focus:outline-none',
                    },
                },
                onUpdate: ({ editor }) => {
                    // `false` keeps this off the network until the form is submitted.
                    this.$wire.set(model, editor.isEmpty ? '' : editor.getHTML(), false)
                    this.refreshActive(editor)
                },
                onSelectionUpdate: ({ editor }) => this.refreshActive(editor),
                onFocus: ({ editor }) => this.refreshActive(editor),
            })

            this.refreshActive(this.editor)

            this.onSet = (event) => {
                if (event.detail?.model !== model) return

                this.editor.commands.setContent(event.detail.content || '', { emitUpdate: false })
                this.refreshActive(this.editor)
            }

            window.addEventListener('rich-text:set', this.onSet)
        },

        destroy() {
            window.removeEventListener('rich-text:set', this.onSet)
            this.editor?.destroy()
        },

        refreshActive(editor) {
            this.active = {
                bold: editor.isActive('bold'),
                italic: editor.isActive('italic'),
                underline: editor.isActive('underline'),
                strike: editor.isActive('strike'),
                code: editor.isActive('code'),
                h2: editor.isActive('heading', { level: 2 }),
                h3: editor.isActive('heading', { level: 3 }),
                bulletList: editor.isActive('bulletList'),
                orderedList: editor.isActive('orderedList'),
                blockquote: editor.isActive('blockquote'),
                link: editor.isActive('link'),
            }
        },

        run(command) {
            const chain = this.editor.chain().focus()

            switch (command) {
                case 'bold': chain.toggleBold().run(); break
                case 'italic': chain.toggleItalic().run(); break
                case 'underline': chain.toggleUnderline().run(); break
                case 'strike': chain.toggleStrike().run(); break
                case 'code': chain.toggleCode().run(); break
                case 'h2': chain.toggleHeading({ level: 2 }).run(); break
                case 'h3': chain.toggleHeading({ level: 3 }).run(); break
                case 'bulletList': chain.toggleBulletList().run(); break
                case 'orderedList': chain.toggleOrderedList().run(); break
                case 'blockquote': chain.toggleBlockquote().run(); break
                case 'hr': chain.setHorizontalRule().run(); break
                case 'undo': chain.undo().run(); break
                case 'redo': chain.redo().run(); break
                case 'clear': chain.unsetAllMarks().clearNodes().run(); break
            }
        },

        toggleLink() {
            if (this.editor.isActive('link')) {
                this.editor.chain().focus().unsetLink().run()

                return
            }

            const url = window.prompt('Link URL', 'https://')

            if (!url) return

            this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
        },
    }
}
