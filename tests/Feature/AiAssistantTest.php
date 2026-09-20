<?php

use App\Livewire\User\AiAssistant;
use App\Models\AiChatMessage;
use App\Models\User;
use App\Services\AiAssistantPromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.ai.provider' => 'gemini',
        'services.ai.api_key' => 'test-key',
        'services.ai.model' => 'test-model',
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
        ->assertDontSee('Need assistance?');
});

test('the floating widget is embedded sitewide on other pages', function () {
    $this->get(route('faqs'))->assertOk()->assertSee('Need assistance?');
});

test('a guest question is answered in the background and stored against their session', function () {
    fakeGeminiReply('Bidding works in steps.');

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')
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
    Livewire::test(AiAssistant::class)->set('message', 'Hello?')->call('send');

    expect(AiChatMessage::where('user_id', $asha->id)->count())->toBe(2);

    $this->actingAs($bikash);
    Livewire::test(AiAssistant::class)->assertDontSee('Private to Asha.')->assertDontSee('Hello?');
});

test('an API failure stores a graceful fallback instead of breaking', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('could not reach the assistant');

    expect(AiChatMessage::where('role', 'assistant')->first()->is_error)->toBeTrue();
});

test('a missing api key falls back without calling the provider', function () {
    config(['services.ai.api_key' => null]);
    Http::fake();

    Livewire::test(AiAssistant::class)
        ->set('message', 'How does bidding work?')
        ->call('send')
        ->assertSee('could not reach the assistant');

    Http::assertNothingSent();
});

test('fallback replies are not sent back to the model as history', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push([], 500)
        ->push(['candidates' => [['content' => ['parts' => [['text' => 'Second answer.']]]]]]),
    ]);

    $component = Livewire::test(AiAssistant::class);
    $component->set('message', 'First question')->call('send');
    $component->set('message', 'Second question')->call('send');

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
        $component->set('message', "Question {$i}")->call('send')->assertHasNoErrors();
    }

    $component->set('message', 'One too many')->call('send')->assertHasErrors('message');
});

test('the drawer only loads the conversation once opened', function () {
    fakeGeminiReply('Earlier answer.');
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AiAssistant::class)->set('message', 'Earlier question')->call('send');

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
        ->toContain('Meetup-only listings')
        ->toContain('do not tell users those options complete a payment');
});
