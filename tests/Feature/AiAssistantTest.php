<?php

use App\Jobs\GenerateAiAssistantReply;
use App\Livewire\User\AiAssistant;
use App\Models\AiChatMessage;
use App\Models\User;
use App\Services\AiAssistantPromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.ai.provider' => 'gemini',
        'services.ai.api_key' => 'test-key',
        'services.ai.model' => 'test-model',
        'services.ai.fallback_model' => null,
        'services.ai.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'services.esewa.deposit_percentage' => 10,
    ]);
});

function fakeGeminiReply(string $text = 'Bidding works in steps.'): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        ]),
    ]);
}

test('the dedicated assistant page is public and hides the floating widget', function () {
    $this->get(route('user.ai-assistant'))
        ->assertOk()
        ->assertSee('How does this work?')
        ->assertDontSee('Ask our AI assistant');
});

test('the floating widget is embedded sitewide on other pages', function () {
    $this->get(route('faqs'))->assertOk()->assertSee('Ask our AI assistant')->assertSee('Need help? Ask AI');
});

test('a guest question is answered in the background and stored against their session', function () {
    fakeGeminiReply('Bidding works in steps.');

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertHasNoErrors()
        ->assertSet('message', '')
        ->assertSee('Bidding works in steps.');

    $messages = AiChatMessage::orderBy('id')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->role)->toBe('user')
        ->and($messages[0]->user_id)->toBeNull()
        ->and($messages[0]->session_id)->not->toBeNull()
        ->and($messages[1]->role)->toBe('assistant')
        ->and($messages[1]->is_error)->toBeFalse();

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->hasHeader('x-goog-api-key', 'test-key')
            && str_contains($request->url(), '/models/test-model:generateContent')
            && str_contains($payload['system_instruction']['parts'][0]['text'], 'Sajha Auction Guide')
            && $payload['contents'][0]['role'] === 'user'
            && $payload['contents'][0]['parts'][0]['text'] === 'How does bidding work?';
    });
});

test('a logged-in user gets their own conversation that other users cannot see', function () {
    fakeGeminiReply('Private to Asha.');
    $asha = User::factory()->create();
    $bikash = User::factory()->create();

    $this->actingAs($asha);
    Livewire::test(AiAssistant::class)->set('message', 'Hello?')->call('send')->call('answerPending');

    expect(AiChatMessage::where('user_id', $asha->id)->count())->toBe(2);

    $this->actingAs($bikash);
    Livewire::test(AiAssistant::class)->assertDontSee('Private to Asha.')->assertDontSee('Hello?');
});

test('an API failure stores a graceful fallback instead of breaking', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertHasNoErrors()
        ->assertSee('could not reach the assistant');

    expect(AiChatMessage::where('role', 'assistant')->first()->is_error)->toBeTrue();
});

test('a missing api key falls back without calling the provider', function () {
    config(['services.ai.api_key' => null]);
    Http::fake();

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertSee('could not reach the assistant');

    Http::assertNothingSent();
});

test('fallback replies are not sent back to the model as history', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push([], 500)
        ->push([], 500) // the client retries once, so the first question fails on both tries
        ->push(['candidates' => [['content' => ['parts' => [['text' => 'Second answer.']]]]]]),
    ]);

    $component = Livewire::test(AiAssistant::class);
    $component->set('message', 'First question')->call('send')->call('answerPending');
    $component->set('message', 'Second question')->call('send')->call('answerPending');

    Http::assertSent(function (Request $request): bool {
        $turns = collect($request->data()['contents'])->pluck('parts.0.text');

        return $turns->contains('Second question') && ! $turns->contains(
            fn (string $text): bool => str_contains($text, 'could not reach the assistant')
        );
    });
});

test('questions are validated and rate limited', function () {
    fakeGeminiReply();

    Livewire::test(AiAssistant::class)
        ->set('message', '')
        ->call('send')
        ->assertHasErrors(['message' => 'required']);

    Livewire::test(AiAssistant::class)
        ->set('message', str_repeat('a', 501))
        ->call('send')
        ->assertHasErrors(['message' => 'max']);

    $component = Livewire::test(AiAssistant::class);
    foreach (range(1, 10) as $i) {
        $component->set('message', "Question {$i}")->call('send')->call('answerPending')->assertHasNoErrors();
    }

    $component->set('message', 'One too many')->call('send')->assertHasErrors('message');
});

test('the drawer only loads the conversation once opened', function () {
    fakeGeminiReply('Earlier answer.');
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AiAssistant::class)->set('message', 'Earlier question')->call('send')->call('answerPending');

    Livewire::test(AiAssistant::class, ['mode' => 'drawer'])
        ->assertDontSee('Earlier answer.')
        ->set('opened', true)
        ->assertSee('Earlier answer.');
});

test('the system prompt reflects real platform rules and forbids data access', function () {
    config(['services.esewa.deposit_percentage' => 15]);

    $prompt = app(AiAssistantPromptService::class)->systemPrompt();

    expect($prompt)
        ->toContain('15% deposit')
        ->toContain('minimum step is Rs. 50')
        ->toContain('minimum step is Rs. 1,000')
        ->toContain('NO access to any user\'s account')
        ->toContain('every second-hand order is an in-person meetup')
        ->toContain('cash on meetup / handover, which is the only option');
});

test('if the immediate answer never arrives, the polling safety net answers it', function () {
    fakeGeminiReply('Answered by the safety net.');

    $component = Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send');

    // Too soon: the immediate answer request normally gets there first.
    $component->call('checkReply');
    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(0);

    $this->travel(10)->seconds();

    $component->call('checkReply')->assertSee('Answered by the safety net.');

    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(1);
});

test('the try again button answers a question that timed out', function () {
    fakeGeminiReply('Here is the answer now.');
    Queue::fake();

    $component = Livewire::test(AiAssistant::class)
        ->set('message', 'What is proxy bidding?')
        ->call('send');

    $this->travel(2)->minutes();

    $component->call('retryReply')->assertSee('Here is the answer now.');
});

test('a message is never answered twice even if a worker and the fallback race', function () {
    fakeGeminiReply('Only once.');
    Queue::fake();

    Livewire::test(AiAssistant::class)->set('message', 'Hello there')->call('send');
    $messageId = AiChatMessage::where('role', 'user')->value('id');

    // Another process is already generating this reply.
    $lock = Cache::lock('ai-reply:'.$messageId, 90);
    expect($lock->get())->toBeTrue();

    app()->call([new GenerateAiAssistantReply($messageId), 'handle']);
    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(0);

    $lock->release();

    app()->call([new GenerateAiAssistantReply($messageId), 'handle']);
    app()->call([new GenerateAiAssistantReply($messageId), 'handle']);
    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(1);
});

test('an overloaded main model is retried on the fallback model', function () {
    config(['services.ai.model' => 'main-model', 'services.ai.fallback_model' => 'backup-model']);

    Http::fake([
        '*models/main-model:*' => Http::response(['error' => ['message' => 'high demand']], 503),
        '*models/backup-model:*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'From the backup model.']]]]]]),
    ]);

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertSee('From the backup model.');

    Http::assertSentCount(2);
    expect(AiChatMessage::where('role', 'assistant')->first()->is_error)->toBeFalse();
});

test('a busy AI service shows a clear message and try again answers the question', function () {
    config(['services.ai.model' => 'main-model', 'services.ai.fallback_model' => null]);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => ['message' => 'high demand']], 503)
        ->push(['error' => ['message' => 'high demand']], 503)
        ->push(['candidates' => [['content' => ['parts' => [['text' => 'Now it works.']]]]]]),
    ]);

    $component = Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertSee('The AI service is very busy right now')
        ->assertSee('Try again');

    $component->call('retryReply')
        ->assertSee('Now it works.')
        ->assertDontSee('The AI service is very busy right now');

    // The error was replaced by the real answer (one user message, one assistant message).
    expect(AiChatMessage::count())->toBe(2)
        ->and(AiChatMessage::where('role', 'assistant')->first()->is_error)->toBeFalse();
});

test('a retired or overloaded model is skipped and the next fallback model answers', function () {
    config(['services.ai.model' => 'main-model', 'services.ai.fallback_model' => 'retired-model, good-model']);

    Http::fake([
        '*models/main-model:*' => Http::response(['error' => ['message' => 'high demand']], 503),
        '*models/retired-model:*' => Http::response(['error' => ['message' => 'no longer available to new users']], 404),
        '*models/good-model:*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Third time lucky.']]]]]]),
    ]);

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertSee('Third time lucky.');

    Http::assertSentCount(3);
});

test('when every model fails the real first cause is reported, not a later 404', function () {
    config(['services.ai.model' => 'main-model', 'services.ai.fallback_model' => 'retired-model']);

    Http::fake([
        '*models/main-model:*' => Http::response(['error' => ['message' => 'high demand']], 503),
        '*models/retired-model:*' => Http::response(['error' => ['message' => 'no longer available']], 404),
    ]);

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')->call('answerPending')
        ->assertSee('The AI service is very busy right now');
});

test('sending only stores the question; the answer comes from an immediate second step, no queue involved', function () {
    fakeGeminiReply('Instant answer.');
    Queue::fake();

    $component = Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')
        ->assertSee('How does bidding work?')
        ->assertDontSee('Instant answer.');

    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(0);

    $component->call('answerPending')->assertSee('Instant answer.');

    Queue::assertNothingPushed();
    expect(AiChatMessage::where('role', 'assistant')->count())->toBe(1);
});

test('the same first question is answered from cache for the next person', function () {
    fakeGeminiReply('Cached answer.');

    $this->actingAs(User::factory()->create());
    Livewire::test(AiAssistant::class)->set('message', 'How does bidding work?')->call('send')->call('answerPending');

    $this->actingAs(User::factory()->create());
    Livewire::test(AiAssistant::class)
        ->set('message', '  how does BIDDING work?  ')
        ->call('send')
        ->call('answerPending')
        ->assertSee('Cached answer.');

    // Only the first person caused a call to Gemini.
    Http::assertSentCount(1);
});

test('follow-up questions are never served from cache', function () {
    fakeGeminiReply('An answer.');

    $component = Livewire::test(AiAssistant::class);
    $component->set('message', 'How does bidding work?')->call('send')->call('answerPending');
    $component->set('message', 'And what about proxy bids?')->call('send')->call('answerPending');

    Http::assertSentCount(2);
});

test('the chat tells the page to scroll to the newest message when it changes', function () {
    fakeGeminiReply('Scroll me into view.');

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')
        ->assertDispatched('ai-chat-updated')
        ->call('answerPending')
        ->assertDispatched('ai-chat-updated')
        ->assertSee('Scroll me into view.');

    Livewire::test(AiAssistant::class, ['mode' => 'drawer'])
        ->set('opened', true)
        ->assertDispatched('ai-chat-updated');
});
