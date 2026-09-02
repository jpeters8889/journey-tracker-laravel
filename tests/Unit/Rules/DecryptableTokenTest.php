<?php

declare(strict_types=1);

use Illuminate\Encryption\Encrypter;
use Jpeters8889\JourneyTrackerLaravel\Rules\DecryptableToken;

beforeEach(function (): void {
    $this->encrypter = new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc');
    $this->rule = new DecryptableToken($this->encrypter);
});

it('passes a token carrying a visit id and a path', function (): void {
    $token = $this->encrypter->encrypt(['visit_id' => 'visit-abc', 'path' => 'blog/my-post']);

    expect(validationFailures($this->rule, $token))->toBeEmpty();
});

it('still passes a token minted before the visit id rename', function (): void {
    $token = $this->encrypter->encrypt(['session_id' => 'session-abc', 'path' => 'blog/my-post']);

    expect(validationFailures($this->rule, $token))->toBeEmpty();
});

it('passes a token carrying extra keys alongside the required ones', function (): void {
    $token = $this->encrypter->encrypt(['visit_id' => 'visit-abc', 'path' => 'blog', 'extra' => true]);

    expect(validationFailures($this->rule, $token))->toBeEmpty();
});

it('fails a value that will not decrypt', function (mixed $value): void {
    expect(validationFailures($this->rule, $value))->toHaveCount(1);
})->with([
    'garbage' => 'not-a-real-token',
    'empty string' => '',
    'base64 noise' => 'eyJpdiI6ImZha2UiLCJ2YWx1ZSI6ImZha2UifQ==',
]);

it('fails a value that is not a string at all', function (mixed $value): void {
    expect(validationFailures($this->rule, $value))->toHaveCount(1);
})->with([
    'int' => 123,
    'null' => null,
    'array' => [['token' => 'nope']],
    'bool' => true,
]);

it('fails a token encrypted with a different key', function (): void {
    $foreign = new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc');

    $token = $foreign->encrypt(['visit_id' => 'visit-abc', 'path' => 'blog']);

    expect(validationFailures($this->rule, $token))->toHaveCount(1);
});

it('fails a token whose payload is not an array', function (mixed $payload): void {
    expect(validationFailures($this->rule, $this->encrypter->encrypt($payload)))->toHaveCount(1);
})->with([
    'bare string' => 'session-abc',
    'int' => 42,
    'null' => null,
]);

it('fails a token missing a required key', function (array $payload): void {
    expect(validationFailures($this->rule, $this->encrypter->encrypt($payload)))->toHaveCount(1);
})->with([
    'no visit id' => [['path' => 'blog']],
    'no path' => [['visit_id' => 'visit-abc']],
    'no path on a legacy token' => [['session_id' => 'session-abc']],
    'neither' => [[]],
]);

it('fails a token whose required keys are not strings', function (array $payload): void {
    expect(validationFailures($this->rule, $this->encrypter->encrypt($payload)))->toHaveCount(1);
})->with([
    'int visit id' => [['visit_id' => 42, 'path' => 'blog']],
    'int session id on a legacy token' => [['session_id' => 42, 'path' => 'blog']],
    'null visit id falls through to a missing session id' => [['visit_id' => null, 'path' => 'blog']],
    'null path' => [['visit_id' => 'visit-abc', 'path' => null]],
    'array path' => [['visit_id' => 'visit-abc', 'path' => ['blog']]],
]);

it('names the attribute it failed on', function (): void {
    expect(validationFailures($this->rule, 'nope', 'journey_token'))
        ->toBe(['The :attribute is not a valid journey token.']);
});
