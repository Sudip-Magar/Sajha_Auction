@php
    $suggestions = [
        'How does bidding work?',
        'What is proxy bidding?',
        'How is the auction winner decided?',
        'How do I buy a second-hand item?',
        'How does the meetup work?',
    ];
@endphp

<div
    @if($pending) wire:poll.2s="checkReply" @endif
    x-data
    x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
    @ai-chat-updated.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
    @open-ai-assistant.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 250)"
    class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4"
>
    @if($messages->isEmpty())
        <div class="rounded-2xl bg-emerald-50/60 p-4 text-sm text-emerald-950 dark:bg-emerald-950/20 dark:text-emerald-200">
            <p class="font-bold">Hi! I explain how Sajha Auction works.</p>
            <p class="mt-1 text-xs opacity-80">Try one of these, or type your own question:</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($suggestions as $suggestion)
                    <button type="button" wire:click="send(@js($suggestion))"
                            class="rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-[#1F6F5F] transition hover:bg-emerald-100 dark:border-emerald-900 dark:bg-transparent dark:text-[#7CE0C5] dark:hover:bg-emerald-950/40">
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @foreach($messages as $chatMessage)
        <div wire:key="ai-msg-{{ $chatMessage->id }}" class="flex {{ $chatMessage->role === 'user' ? 'justify-end' : 'justify-start' }}">
            @if($chatMessage->role === 'user')
                <div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-[#1F6F5F] px-3.5 py-2 text-sm text-white">{{ $chatMessage->body }}</div>
            @elseif($chatMessage->is_error)
                <div class="max-w-[85%] rounded-2xl rounded-bl-md border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    {{ $chatMessage->body }}
                    @if($loop->last)
                        <button type="button" wire:click="retryReply" wire:loading.attr="disabled" wire:target="retryReply"
                                class="ml-1 font-black underline">Try again</button>
                    @endif
                </div>
            @else
                <div class="max-w-[85%] rounded-2xl rounded-bl-md bg-gray-100 px-3.5 py-2 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100 [&_ol]:ml-4 [&_ol]:list-decimal [&_p]:my-1 [&_ul]:ml-4 [&_ul]:list-disc">
                    {!! \Illuminate\Support\Str::markdown($chatMessage->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>
            @endif
        </div>
    @endforeach

    @if($pending)
        <div class="flex justify-start" wire:key="ai-typing" wire:init="answerPending">
            <div class="flex items-center gap-1 rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3 dark:bg-gray-800" aria-label="The assistant is typing">
                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:150ms]"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:300ms]"></span>
            </div>
        </div>
    @elseif($timedOut)
        <div class="flex justify-start" wire:key="ai-timeout">
            <div class="max-w-[85%] rounded-2xl rounded-bl-md border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                The assistant is taking longer than usual.
                <button type="button" wire:click="retryReply" wire:loading.attr="disabled" wire:target="retryReply"
                        class="ml-1 font-black underline">Try again</button>
            </div>
        </div>
    @endif
</div>

<div class="border-t border-gray-100 p-3 dark:border-gray-800"
     x-data
     x-init="$nextTick(() => $refs.chatInput?.focus())"
     @ai-chat-updated.window="$nextTick(() => $refs.chatInput?.focus())"
     @open-ai-assistant.window="setTimeout(() => $refs.chatInput?.focus(), 300)">
    <form wire:submit="send" class="flex items-end gap-2">
        {{-- Stays enabled while the assistant answers so the cursor is never lost;
             only the Send button waits (send() ignores messages while one is pending). --}}
        <input type="text" wire:model="message" maxlength="500" autocomplete="off" x-ref="chatInput"
               placeholder="{{ $pending ? 'Waiting for the answer...' : 'Ask how something works...' }}"
               class="input input-bordered w-full text-sm" />
        <button type="submit" wire:loading.attr="disabled" @disabled($pending)
                class="btn btn-primary shrink-0" aria-label="Send question">
            <x-icon name="o-paper-airplane" class="h-5 w-5" />
        </button>
    </form>
    @error('message') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    <p class="mt-2 text-[10px] leading-snug text-gray-400">
        An AI explains how the site works. It can't see your account, orders or bids, and it can make mistakes.
    </p>
</div>
