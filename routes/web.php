<?php

use App\Http\Middleware\SetCurrentCompany;
use App\Http\Middleware\CheckUserRole;
use App\Livewire\Builders\{CreateBuilder, ListBuilder};
use App\Livewire\Subdivisions\ListSubdivision;
use App\Livewire\Supervisors\{CreateSupervisor, ListSupervisor};
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Lot\{LotProgress, AddLotForm};
use App\Livewire\Task\{TaskProgress, AddTaskForm} ;
use App\Livewire\Pages\{LotDetails, TaskDetails};
use App\Models\Task;

Route::get('/', function () {
    return redirect()->route('login'); // Redirect to login or a public landing page
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout');

Route::middleware(['auth', 'verified', SetCurrentCompany::class])
    ->group(function () {

        Route::view('dashboard', 'dashboard')
            ->name('dashboard')
            ->defaults('label', 'Dashboard')
            ->defaults('icon', 'home');

        Route::view('profile', 'profile')
            ->name('profile')
            ->defaults('label', 'Profile')
            ->defaults('icon', 'user-circle');

        Route::middleware(['role:admin'])->group(function () {
            Route::get('builders', ListBuilder::class)
                ->name('builders')
                ->defaults('label', 'Builders')
                ->defaults('icon', 'building-office-2');

            Route::get('builders/create', CreateBuilder::class)
                ->name('builder-create')
                ->defaults('label', 'Add Builder')
                ->defaults('icon', 'user-plus');

            Route::get('subdivisions', ListSubdivision::class)
                ->name('subdivisions')
                ->defaults('label', 'Subdivisions')
                ->defaults('icon', 'rectangle-stack');


            Route::get('supervisors', ListSupervisor::class)
                ->name('supervisors')
                ->defaults('label', 'Supervisors')
                ->defaults('icon', 'user');

            Route::get('workers', \App\Livewire\Workers\ListWorker::class)
                ->name('workers')
                ->defaults('label', 'Workers')
                ->defaults('icon', 'user-group');

            Route::get('supervisors/create', CreateSupervisor::class)
                ->name('supervisor-create')
                ->defaults('label', 'Add Supervisor')
                ->defaults('icon', 'user-plus');

            Route::get('lot-progress', LotProgress::class)
                ->name('lot-progress')
                ->defaults('label', 'Lots Progress')
                ->defaults('icon', 'clipboard-document-check');

            Route::get('/lots/create', AddLotForm::class)
                ->name('lot-create')
                ->defaults('label', 'Create Lot')
                ->defaults('icon', 'plus');

            Route::get('task-progress', TaskProgress::class)
                ->name('task-progress')
                ->defaults('label', 'Tasks Progress')
                ->defaults('icon', 'queue-list');

            Route::get('task-board', \App\Livewire\TaskBoard::class)
                ->name('task-board')
                ->defaults('label', 'Tasks Board')
                ->defaults('icon', 'view-columns');

            Route::get('/lots/{lot_id}/details', LotDetails::class)
                ->name('lot-details')
                ->defaults('label', 'Lot Details view example')
                ->defaults('icon', 'clipboard-document-check');

            Route::get('/tasks/{task_id}/details', TaskDetails::class)
                ->name('task-details')
                ->defaults('label', 'Task Details view example')
                ->defaults('icon', 'queue-list');

            Route::get('/tasks/create', AddTaskForm::class)
                ->name('task-create')
                ->defaults('label', 'Create Task')
                ->defaults('icon', 'clipboard-document-check');
        });


});

require __DIR__.'/auth.php';
