<?php

declare(strict_types=1);

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;

it('reads a visit id and a path out of a payload', function (): void {
    $token = JourneyToken::fromPayload(['visit_id' => 'visit-abc', 'path' => 'blog/my-post']);

    expect($token)->not->toBeNull()
        ->and($token->visitId)->toBe('visit-abc')
        ->and($token->path)->toBe('blog/my-post');
});

it('still reads a payload minted before the visit id rename', function (): void {
    $token = JourneyToken::fromPayload(['session_id' => 'session-abc', 'path' => 'blog']);

    expect($token->visitId)->toBe('session-abc');
});

it('prefers the visit id when a payload carries both keys', function (): void {
    $token = JourneyToken::fromPayload([
        'visit_id' => 'visit-abc',
        'session_id' => 'session-abc',
        'path' => 'blog',
    ]);

    expect($token->visitId)->toBe('visit-abc');
});

it('refuses a payload it cannot read', function (mixed $payload): void {
    expect(JourneyToken::fromPayload($payload))->toBeNull();
})->with([
    'not an array' => ['visit-abc'],
    'null' => [null],
    'no identifier' => [['path' => 'blog']],
    'no path' => [['visit_id' => 'visit-abc']],
    'identifier is not a string' => [['visit_id' => 42, 'path' => 'blog']],
    'legacy identifier is not a string' => [['session_id' => 42, 'path' => 'blog']],
    'path is not a string' => [['visit_id' => 'visit-abc', 'path' => ['blog']]],
    'empty' => [[]],
]);

it('decrypts a token this application minted', function (): void {
    $token = JourneyToken::decrypt(Crypt::encrypt(['visit_id' => 'visit-abc', 'path' => 'blog']));

    expect($token->visitId)->toBe('visit-abc')
        ->and($token->path)->toBe('blog');
});

it('refuses to decrypt a value it cannot read', function (string $value): void {
    JourneyToken::decrypt($value);
})->with([
    'garbage' => 'not-a-real-token',
    'empty string' => '',
])->throws(InvalidArgumentException::class, 'The journey token could not be decrypted.');

it('refuses to decrypt a token encrypted by another application', function (): void {
    $foreign = new Encrypter(random_bytes(32), 'aes-256-cbc');

    JourneyToken::decrypt($foreign->encrypt(['visit_id' => 'visit-abc', 'path' => 'blog']));
})->throws(InvalidArgumentException::class, 'The journey token could not be decrypted.');

it('refuses a decryptable token that carries no journey', function (): void {
    JourneyToken::decrypt(Crypt::encrypt(['path' => 'blog']));
})->throws(InvalidArgumentException::class, 'The journey token does not carry a journey.');
