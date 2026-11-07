<?php

namespace App\Livewire\Settings;

use App\Models\Business;
use App\Models\Company;
use App\Services\StructureService;
use Flux\Flux;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › Businesses & divisions: the organization's structure.
 * Organization (the account) › Business (line of work) › Division (branch).
 */
#[Layout('layouts.app')]
class Structure extends Component
{
    public bool $showArchived = false;

    public string $newBusiness = '';

    /** @var array<int, string> business id => new division name */
    public array $newDivision = [];

    // Rename modal
    public string $renameType = '';

    public ?int $renameId = null;

    public string $renameTo = '';

    #[Computed]
    public function businesses(): Collection
    {
        return Business::query()
            ->when($this->showArchived, fn ($q) => $q->withTrashed())
            ->where('organization_id', tenancy()->organizationId())
            ->with(['divisions' => fn ($q) => $q->when($this->showArchived, fn ($q) => $q->withTrashed())->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function can(string $level, Business|Company|null $place = null): bool
    {
        return app(StructureService::class)->can(auth()->user(), $place ?? tenancy()->organization(), $level);
    }

    public function addBusiness(StructureService $structure): void
    {
        $this->authorize('edit-settings');
        $this->attempt(fn () => $structure->addBusiness(auth()->user(), tenancy()->organization(), $this->newBusiness), 'Business added.', 'newBusiness');
        $this->newBusiness = '';
    }

    public function addDivision(int $businessId, StructureService $structure): void
    {
        $this->authorize('edit-settings');
        $business = $this->findBusiness($businessId);
        $this->attempt(fn () => $structure->addDivision(auth()->user(), $business, $this->newDivision[$businessId] ?? ''), 'Division added. Give people access to it on the People page.', "newDivision.$businessId");
        unset($this->newDivision[$businessId]);
    }

    public function startRename(string $type, int $id): void
    {
        $model = $type === 'business' ? $this->findBusiness($id) : $this->findDivision($id);
        [$this->renameType, $this->renameId, $this->renameTo] = [$type, $id, $model->name];
        Flux::modal('rename')->show();
    }

    public function rename(StructureService $structure): void
    {
        $this->authorize('edit-settings');
        $this->attempt(function () use ($structure) {
            $this->renameType === 'business'
                ? $structure->renameBusiness(auth()->user(), $this->findBusiness($this->renameId), $this->renameTo)
                : $structure->renameDivision(auth()->user(), $this->findDivision($this->renameId), $this->renameTo);
            Flux::modal('rename')->close();
        }, 'Renamed.', 'renameTo');
    }

    public function archive(string $type, int $id, StructureService $structure): void
    {
        $this->authorize('full-settings');
        $this->attempt(fn () => $type === 'business'
            ? $structure->archiveBusiness(auth()->user(), $this->findBusiness($id))
            : $structure->archiveDivision(auth()->user(), $this->findDivision($id)), 'Archived. Its records are kept and it can be restored.');
    }

    public function restore(string $type, int $id, StructureService $structure): void
    {
        $this->authorize('full-settings');
        $this->attempt(fn () => $type === 'business'
            ? $structure->restoreBusiness(auth()->user(), $this->findBusiness($id))
            : $structure->restoreDivision(auth()->user(), $this->findDivision($id)), 'Restored.');
    }

    private function findBusiness(int $id): Business
    {
        return Business::withTrashed()->where('organization_id', tenancy()->organizationId())->findOrFail($id);
    }

    private function findDivision(int $id): Company
    {
        return Company::withTrashed()
            ->whereIn('business_id', Business::withTrashed()->where('organization_id', tenancy()->organizationId())->select('id'))
            ->findOrFail($id);
    }

    private function attempt(callable $action, string $done, string $errorField = 'structure'): void
    {
        try {
            $action();
            unset($this->businesses);
            Flux::toast(text: $done, variant: 'success');
        } catch (InvalidArgumentException $e) {
            $this->addError($errorField, $e->getMessage());
            Flux::toast(text: $e->getMessage(), heading: 'Not done', variant: 'warning');
        }
    }

    public function render()
    {
        return view('livewire.settings.structure');
    }
}
