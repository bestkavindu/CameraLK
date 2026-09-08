@props([
    'model',
    'content' => null,
    'label' => null,
    'description' => null,
])

@php
    $buttonClasses = 'flex h-8 min-w-8 items-center justify-center rounded-md px-1.5 text-sm text-zinc-600 hover:bg-zinc-200/70 dark:text-zinc-300 dark:hover:bg-zinc-700';
    $activeClasses = 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white';
@endphp

<div class="grid gap-2">
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <div
        wire:ignore
        x-data="richTextEditor({ model: @js($model), content: @js($content) })"
        class="overflow-hidden rounded-lg border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-accent dark:border-zinc-600 dark:bg-zinc-700/30"
    >
        <div class="flex flex-wrap items-center gap-0.5 border-b border-zinc-200 bg-zinc-50 p-1.5 dark:border-zinc-600 dark:bg-zinc-800">
            <button type="button" @click="run('bold')" :class="active.bold && '{{ $activeClasses }}'" class="{{ $buttonClasses }} font-bold" title="{{ __('Bold') }}">B</button>
            <button type="button" @click="run('italic')" :class="active.italic && '{{ $activeClasses }}'" class="{{ $buttonClasses }} italic" title="{{ __('Italic') }}">I</button>
            <button type="button" @click="run('underline')" :class="active.underline && '{{ $activeClasses }}'" class="{{ $buttonClasses }} underline" title="{{ __('Underline') }}">U</button>
            <button type="button" @click="run('strike')" :class="active.strike && '{{ $activeClasses }}'" class="{{ $buttonClasses }} line-through" title="{{ __('Strikethrough') }}">S</button>

            <div class="mx-1 h-5 w-px bg-zinc-200 dark:bg-zinc-600"></div>

            <button type="button" @click="run('h2')" :class="active.h2 && '{{ $activeClasses }}'" class="{{ $buttonClasses }} font-semibold" title="{{ __('Heading') }}">H2</button>
            <button type="button" @click="run('h3')" :class="active.h3 && '{{ $activeClasses }}'" class="{{ $buttonClasses }} font-semibold" title="{{ __('Subheading') }}">H3</button>

            <div class="mx-1 h-5 w-px bg-zinc-200 dark:bg-zinc-600"></div>

            <button type="button" @click="run('bulletList')" :class="active.bulletList && '{{ $activeClasses }}'" class="{{ $buttonClasses }}" title="{{ __('Bullet list') }}">
                <flux:icon.list-bullet variant="micro" />
            </button>
            <button type="button" @click="run('orderedList')" :class="active.orderedList && '{{ $activeClasses }}'" class="{{ $buttonClasses }}" title="{{ __('Numbered list') }}">
                <flux:icon.numbered-list variant="micro" />
            </button>
            <button type="button" @click="run('blockquote')" :class="active.blockquote && '{{ $activeClasses }}'" class="{{ $buttonClasses }}" title="{{ __('Quote') }}">
                <flux:icon.chat-bubble-bottom-center-text variant="micro" />
            </button>
            <button type="button" @click="run('code')" :class="active.code && '{{ $activeClasses }}'" class="{{ $buttonClasses }}" title="{{ __('Inline code') }}">
                <flux:icon.code-bracket variant="micro" />
            </button>
            <button type="button" @click="toggleLink()" :class="active.link && '{{ $activeClasses }}'" class="{{ $buttonClasses }}" title="{{ __('Link') }}">
                <flux:icon.link variant="micro" />
            </button>
            <button type="button" @click="run('hr')" class="{{ $buttonClasses }}" title="{{ __('Divider') }}">
                <flux:icon.minus variant="micro" />
            </button>

            <div class="mx-1 h-5 w-px bg-zinc-200 dark:bg-zinc-600"></div>

            <button type="button" @click="run('undo')" class="{{ $buttonClasses }}" title="{{ __('Undo') }}">
                <flux:icon.arrow-uturn-left variant="micro" />
            </button>
            <button type="button" @click="run('redo')" class="{{ $buttonClasses }}" title="{{ __('Redo') }}">
                <flux:icon.arrow-uturn-right variant="micro" />
            </button>
            <button type="button" @click="run('clear')" class="{{ $buttonClasses }}" title="{{ __('Clear formatting') }}">
                <flux:icon.x-mark variant="micro" />
            </button>
        </div>

        <div x-ref="editor" class="max-h-96 min-h-40 overflow-y-auto px-3 py-2.5"></div>
    </div>

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <flux:error :name="$model" />
</div>
