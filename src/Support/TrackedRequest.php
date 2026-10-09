<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;

/**
 * @internal
 */
class TrackedRequest
{
    public function __construct(
        protected Request $request,
        protected Encrypter $encrypter,
        protected VisitKey $visitKey,
        protected TrackingPolicy $trackingPolicy,
    ) {
        //
    }

    public function observe(): void
    {
        $this->request->attributes->set($this->observedKey(), true);
    }

    public function start(): void
    {
        $visit = $this->visitKey->resolve();

        $this->request->attributes->set($this->visitAttributeKey(), $visit);

        $this->request->attributes->set($this->payloadKey(), [
            'visit_id' => $visit->id,
            'path' => $this->request->path(),
        ]);
    }

    public function persistVisit(): void
    {
        $visit = $this->visit();

        if ( ! $visit instanceof Visit) {
            return;
        }

        $this->visitKey->persist($visit);
    }

    public function visitKeyWasNew(): bool
    {
        return $this->visit()?->wasNew === true;
    }

    public function expectConfirmation(): void
    {
        $this->request->attributes->set($this->confirmationKey(), true);
    }

    public function confirmationExpected(): bool
    {
        return $this->request->attributes->getBoolean($this->confirmationKey());
    }

    public function isTracking(): bool
    {
        return $this->payload() !== null;
    }

    public function visitId(): ?string
    {
        return $this->payload()['visit_id'] ?? null;
    }

    public function token(): ?string
    {
        $payload = $this->payload();

        if ($payload === null) {
            return null;
        }

        /** @var string|null $token */
        $token = $this->request->attributes->get($this->tokenKey());

        if ($token === null) {
            $token = $this->encrypter->encrypt($payload);

            $this->request->attributes->set($this->tokenKey(), $token);
        }

        return $token;
    }

    public function visitToken(): ?string
    {
        $token = $this->token();

        if ($token !== null) {
            return $token;
        }

        /** @var string|null $cached */
        $cached = $this->request->attributes->get($this->tokenKey());

        if ($cached !== null) {
            return $cached;
        }

        if ( ! $this->request->attributes->getBoolean($this->observedKey())) {
            return null;
        }

        if ( ! $this->trackingPolicy->shouldTrackPath(
            $this->request->path(),
            $this->request->route()?->getName(),
            $this->request->route()?->uri(),
        )) {
            return null;
        }

        $visit = $this->visitKey->stored();

        if ( ! $visit instanceof Visit) {
            return null;
        }

        $minted = $this->encrypter->encrypt([
            'visit_id' => $visit->id,
            'path' => $this->request->path(),
        ]);

        $this->request->attributes->set($this->tokenKey(), $minted);

        return $minted;
    }

    /** @return array{visit_id: string, path: string}|null */
    private function payload(): ?array
    {
        /** @var array{visit_id: string, path: string}|null $payload */
        $payload = $this->request->attributes->get($this->payloadKey());

        return $payload;
    }

    private function visit(): ?Visit
    {
        $visit = $this->request->attributes->get($this->visitAttributeKey());

        return $visit instanceof Visit ? $visit : null;
    }

    private function payloadKey(): string
    {
        return 'journey-tracker.payload';
    }

    private function visitAttributeKey(): string
    {
        return 'journey-tracker.visit';
    }

    private function confirmationKey(): string
    {
        return 'journey-tracker.confirmation-expected';
    }

    private function tokenKey(): string
    {
        return 'journey-tracker.token';
    }

    private function observedKey(): string
    {
        return 'journey-tracker.observed';
    }
}
