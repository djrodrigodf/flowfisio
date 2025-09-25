<?php

use App\Http\Controllers\Admin\ReportsDownloadController;
use App\Http\Controllers\Admin\WhisperController;
/* ===== Livewire (Admin) ===== */

use App\Livewire\Admin\AppointmentIndex;
use App\Livewire\Admin\Appointments;
use App\Livewire\Admin\Appointments\AppointmentForm;
use App\Livewire\Admin\AppointmentsCalendar;
use App\Livewire\Admin\AttendanceBoard;
use App\Livewire\Admin\BiDashboard;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Holidays;
use App\Livewire\Admin\InsuranceForm;
use App\Livewire\Admin\Insurances;
use App\Livewire\Admin\LocationForm;
use App\Livewire\Admin\Locations;
use App\Livewire\Admin\Partners;
use App\Livewire\Admin\PartnerShow;
use App\Livewire\Admin\PatientForm;
use App\Livewire\Admin\Patients;
use App\Livewire\Admin\PatientShow;
use App\Livewire\Admin\PaymentForm;
use App\Livewire\Admin\Payments;
use App\Livewire\Admin\Payouts;
use App\Livewire\Admin\PayoutShow;
use App\Livewire\Admin\PermissionManager;
use App\Livewire\Admin\PreRegistrationCalendar;
use App\Livewire\Admin\PreRegistrationLink;
use App\Livewire\Admin\PreRegistrationList;
use App\Livewire\Admin\PreRegistrationShow;
use App\Livewire\Admin\ReportsFinance;
use App\Livewire\Admin\ReportsOperational;
use App\Livewire\Admin\RescheduleBoard;
use App\Livewire\Admin\Restrictions;
use App\Livewire\Admin\RoomForm;
use App\Livewire\Admin\Rooms;
use App\Livewire\Admin\TreatmentForm;
use App\Livewire\Admin\Treatments;
use App\Livewire\Admin\TreatmentTables;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserList;
/* ===== Livewire (Auth/Public) ===== */
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Public\PreCadastro;
/* ===== Controllers ===== */
use Illuminate\Support\Facades\Route;

/* ===================== HOME ===================== */
Route::get('/', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('welcome');

/* ===================== PÚBLICO ===================== */
Route::get('/pre-cadastro/{token}', PreCadastro::class)->name('pre-cadastro');

/* ===================== ADMIN ===================== */
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    /* --- Pré-cadastro --- */
    Route::get('/links/pre-cadastro', PreRegistrationLink::class)
        ->middleware('can:pacientes.criar')
        ->name('links.pre-cadastro');

    Route::get('/pre-registrations', PreRegistrationList::class)
        ->middleware('can:agendamentos.ver')
        ->name('pre-registrations');

    Route::get('/pre-registrations/calendar', PreRegistrationCalendar::class)
        ->middleware('can:agendamentos.ver')
        ->name('pre-registrations.calendar');

    Route::get('/pre-registration/{preRegistration}', PreRegistrationShow::class)
        ->name('pre-registration.show');

    /* --- Usuários & Permissões --- */
    Route::get('/users', UserList::class)->middleware('can:usuarios.ver')->name('users.index');
    Route::get('/users/form/{user?}', UserForm::class)->middleware('can:usuarios.gerenciar')->name('usuarios.form');
    Route::get('/permissions', PermissionManager::class)->middleware('can:usuarios.gerenciar')->name('permissoes');

    /* --- Parceiros --- */
    Route::get('/partners', Partners::class)->name('partners');
    Route::get('/partners/{partner}', PartnerShow::class)->name('partners.show');

    /* --- Config / Documentos --- */
    // (corrigido o path duplicado: era "/admin/admin/document-categories")
    Route::get('/document-categories', \App\Livewire\Config\DocumentCategoryManager::class)
        ->name('admin.document-categories');

    /* --- Whisper --- */
    Route::post('/whisper/transcribe', [WhisperController::class, 'transcribe'])->name('whisper.transcribe');

    /* --- Pacientes --- */
    Route::get('/patients', Patients::class)->name('patients.index');
    Route::get('/patients/new', PatientForm::class)->name('patients.create');
    Route::get('/patients/edit/{patient}', PatientForm::class)->whereNumber('patient')->name('patients.edit');
    Route::get('/patients/show/{patient}', PatientShow::class)->name('patients.show');

    /* --- Tratamentos --- */
    Route::get('/treatments', Treatments::class)->name('treatments.index');
    Route::get('/treatments/new', TreatmentForm::class)->name('treatments.create');
    Route::get('/treatments/tables', TreatmentTables::class)->name('treatments.tables');

    /* --- Convênios --- */
    Route::get('/insurances', Insurances::class)->name('insurances.index');
    Route::get('/insurances/new', InsuranceForm::class)->name('insurances.create');

    /* --- Agenda / Atendimentos --- */
    Route::get('/appointments', Appointments::class)->name('appointments.index');
    Route::get('/appointments/lista', AppointmentIndex::class)->name('appointments.lista');
    Route::get('/appointments/calendar', AppointmentsCalendar::class)->name('appointments.calendar');
    Route::get('/appointments/show/{appointment}', \App\Livewire\Admin\AppointmentShow::class)->name('appointments.show');

    Route::get('/appointments/new', AppointmentForm::class)->name('appointments.new');
    Route::get('/appointments/edit/{appointment}', AppointmentForm::class)->whereNumber('appointment')->name('appointments.edit');

    Route::get('/attendance', AttendanceBoard::class)->name('attendance.board');
    Route::get('/reschedule', RescheduleBoard::class)->name('reschedule.board');

    /* --- Pagamentos --- */
    Route::get('/payments', Payments::class)->name('payments.index');
    Route::get('/payments/new', PaymentForm::class)->name('payments.create');

    /* --- Repasses --- */
    Route::get('/payouts', Payouts::class)->name('payouts.index');
    Route::get('/payouts/{payout}', PayoutShow::class)->name('payouts.show');

    /* --- BI / Relatórios --- */
    Route::get('/bi', BiDashboard::class)->name('bi.dashboard');
    Route::get('/reports/operational', ReportsOperational::class)->name('reports.operational');
    Route::get('/reports/finance', ReportsFinance::class)->name('reports.finance');

    /* --- Infra: Unidades / Salas / Restrições / Feriados --- */
    Route::get('/locations', Locations::class)->name('locations.index');
    Route::get('/locations/new', LocationForm::class)->name('locations.create');
    Route::get('/rooms', Rooms::class)->name('rooms.index');
    Route::get('/rooms/new', RoomForm::class)->name('rooms.create');
    Route::get('/restrictions', Restrictions::class)->name('restrictions.index');
    Route::get('/holidays', Holidays::class)->name('holidays.index');

    Route::get('/reports/export/appointments', [ReportsDownloadController::class, 'appointmentsCsv'])
        ->name('reports.export.appointments');

    Route::get('/reports/export/payments', [ReportsDownloadController::class, 'paymentsCsv'])
        ->name('reports.export.payments');

});

/* ===================== AUTH ===================== */
Route::get('login', Login::class)->middleware('guest')->name('login');
Route::get('/forgot-password', ForgotPassword::class)->middleware('guest')->name('password.request');
Route::get('/reset-password/{token}', ResetPassword::class)->middleware('guest')->name('password.reset');

/* ===================== LOGOUT ===================== */
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');
