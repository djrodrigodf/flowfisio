<?php

use App\Livewire\Admin\UserForm;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Public\PreCadastro;
use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Livewire\Admin\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('welcome');

// Público
Route::get('/pre-cadastro/{token}', PreCadastro::class)
    ->name('pre-cadastro');

// Rotas protegidas
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // 🔗 Gerar link de pré-cadastro
    Route::get('/links/pre-cadastro', \App\Livewire\Admin\PreRegistrationLink::class)
        ->middleware('can:pacientes.criar')
        ->name('links.pre-cadastro');

    // 📋 Listagem de pré-agendamentos
    Route::get('/pre-agendamentos', \App\Livewire\Admin\PreRegistrationList::class)
        ->middleware('can:agendamentos.ver')
        ->name('pre-registrations');

    // 📆 Calendário de pré-agendamentos
    Route::get('/pre-registrations/calendar', \App\Livewire\Admin\PreRegistrationCalendar::class)
        ->middleware('can:agendamentos.ver')
        ->name('pre-registrations.calendar');

    // 👤 Listagem de usuários
    Route::get('/usuarios', \App\Livewire\Admin\UserList::class)
        ->middleware('can:usuarios.ver')
        ->name('users.index');

    // ✍️ Formulário de criação/edição de usuários
    Route::get('/usuarios/form/{user?}', UserForm::class)
        ->middleware('can:usuarios.gerenciar')
        ->name('usuarios.form');

    // 🛡️ Gestão de permissões
    Route::get('/permissoes', \App\Livewire\Admin\PermissionManager::class)
        ->middleware('can:usuarios.gerenciar')
        ->name('permissoes');

    Route::get('/parceiros', \App\Livewire\Admin\Partners::class)
        ->name('partners');

    Route::get('/parceiros/{partner}', \App\Livewire\Admin\PartnerShow::class)
        ->name('partners.show');

    Route::get('/admin/document-categories', \App\Livewire\Config\DocumentCategoryManager::class)
        ->name('admin.document-categories');

    Route::get('/pre-agendamento/{preRegistration}', \App\Livewire\Admin\PreRegistrationShow::class)
        ->name('pre-registration.show');

    Route::post('/whisper/transcribe', [\App\Http\Controllers\Admin\WhisperController::class, 'transcribe'])
        ->name('whisper.transcribe');
});

// Autenticação
Route::get('login', Login::class)
    ->middleware('guest')
    ->name('login');

Route::get('/forgot-password', ForgotPassword::class)
    ->middleware('guest')
    ->name('password.request');

Route::get('/reset-password/{token}', ResetPassword::class)
    ->middleware('guest')
    ->name('password.reset');

// Logout
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');
