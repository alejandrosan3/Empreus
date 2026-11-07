<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Company;
use App\Models\Organization;

/**
 * Knows which organization, business and division the current request works in.
 *
 * Models that use the BelongsTo* concerns read from here to only return the
 * current place's records. When nothing is set (console commands, queued jobs
 * without a division) no filtering happens.
 */
class Tenancy
{
    protected ?Company $division = null;

    protected bool $resolved = false;

    public function setDivision(?Company $division): void
    {
        $this->division = $division?->loadMissing('business.organization');
        $this->resolved = true;
    }

    public function forget(): void
    {
        $this->division = null;
        $this->resolved = true;
    }

    public function division(): ?Company
    {
        if (! $this->resolved) {
            $this->resolved = true;
            $this->resolveFromSession();
        }

        return $this->division;
    }

    public function business(): ?Business
    {
        return $this->division()?->business;
    }

    public function organization(): ?Organization
    {
        return $this->business()?->organization;
    }

    public function divisionId(): ?int
    {
        return $this->division()?->id;
    }

    public function businessId(): ?int
    {
        return $this->division()?->business_id;
    }

    public function organizationId(): ?int
    {
        return $this->business()?->organization_id;
    }

    /**
     * Run a callback as if the given division were selected, then restore.
     */
    public function runIn(?Company $division, callable $callback): mixed
    {
        [$previous, $wasResolved] = [$this->division, $this->resolved];
        $this->setDivision($division);

        try {
            return $callback();
        } finally {
            $this->division = $previous;
            $this->resolved = $wasResolved;
        }
    }

    /**
     * Livewire follow-up requests and other web requests that skip the middleware
     * still carry the selected division in the session.
     */
    protected function resolveFromSession(): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $request = request();
        if (! $request->hasSession() || ! ($id = $request->session()->get('company_id'))) {
            return;
        }

        $user = $request->user();
        $division = Company::with('business.organization')->find($id);

        if ($division && $user && $user->canAccessDivision($division)) {
            $this->division = $division;
        }
    }
}
