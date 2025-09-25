<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/1iofnr8a88xsz7s9eg51ygh4cvqjd1vrg3929172rdsbzebu/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    @stack('styles')
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main-custom full-width="true" collapse-text="Recolher" custom-class="border-r border-base-200 px-5 lg:static lg:translate-x-0 bg-base-200 -translate-x-full">

        <x-slot:header>
            <livewire:header-flow />
        </x-slot:header>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100">

            {{-- BRAND --}}
            <x-app-brand class="px-5 pt-4" />

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User --}}
                @if($user = auth()->user())
                    <x-menu-separator />

                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded">
                        <x-slot:actions>
                            <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="logoff" no-wire-navigate link="/logout" />
                        </x-slot:actions>
                    </x-list-item>

                    <x-menu-separator />
                @endif


                <x-menu-sub title="Gestão de Usuarios" icon="fas.users">
                    <x-menu-item title="Usuarios" icon="fas.user" link="{{route('admin.users.index')}}" />
                    <x-menu-item title="Definir Permissões" icon="fas.users-cog" link="{{route('admin.permissoes')}}" />
                </x-menu-sub>

                <x-menu-item title="Parceiros" icon="fas.user-cog" link="{{route('admin.partners')}}" />

                <x-menu-sub title="Pré-Agendamento" icon="fas.user-clock">
                    <x-menu-item title="Gerar Link" icon="fas.user-tag" link="{{route('admin.links.pre-cadastro')}}" />
                    <x-menu-item title="Agendamentos" icon="fas.user-edit" link="{{route('admin.pre-registrations')}}" />
                    <x-menu-item title="Calendario" icon="fas.address-book" link="{{route('admin.pre-registrations.calendar')}}" />
                </x-menu-sub>

                <x-menu-sub title="Configuração" icon="fas.cogs">
                    <x-menu-item title="Categoria Documentos" icon="fas.user-tag" link="{{route('admin.admin.document-categories')}}" />
                </x-menu-sub>

                {{-- === Pacientes === --}}
                <x-menu-sub title="Pacientes" icon="fas.user-injured">
                    <x-menu-item title="Lista" icon="fas.list" link="{{ route('admin.patients.index') }}" />
                    <x-menu-item title="Novo cadastro" icon="fas.user-plus" link="{{ route('admin.patients.create') }}" />
                </x-menu-sub>

                {{-- === Agenda / Atendimentos === --}}
                <x-menu-sub title="Agenda" icon="fas.calendar-days">

                    <x-menu-item title="Agendar" icon="fas.calendar-check" link="{{ route('admin.appointments.new') }}" />
                    <x-menu-item icon="fas.calendar-days" title="Lista" link="{{ route('admin.appointments.lista') }}" />
                    <x-menu-item title="Calendário" icon="fas.calendar-check" link="{{ route('admin.appointments.calendar') }}" />
                    <x-menu-item title="Atendimentos" icon="fas.table-list" link="{{ route('admin.appointments.index') }}" />
                    <x-menu-item title="Presenças" icon="fas.user-check" link="{{ route('admin.attendance.board') }}" />
                    <x-menu-item title="Reagendamentos" icon="fas.right-left" link="{{ route('admin.reschedule.board') }}" />
                </x-menu-sub>

                {{-- === Financeiro === --}}
                <x-menu-sub title="Financeiro" icon="fas.wallet">
                    <x-menu-item title="Pagamentos" icon="fas.money-bill-wave" link="{{ route('admin.payments.index') }}" />
                    <x-menu-item title="Repasses" icon="fas.hand-holding-usd" link="{{ route('admin.payouts.index') }}" />
                    <x-menu-item title="Rel. Financeiros" icon="fas.file-invoice-dollar" link="{{ route('admin.reports.finance') }}" />
                </x-menu-sub>

                {{-- === Produção / BI === --}}
                <x-menu-sub title="BI & Produção" icon="fas.chart-line">
                    <x-menu-item title="Dashboard BI" icon="fas.chart-pie" link="{{ route('admin.bi.dashboard') }}" />
                    <x-menu-item title="Rel. Operacionais" icon="fas.clipboard-list" link="{{ route('admin.reports.operational') }}" />
                </x-menu-sub>

                {{-- === Cadastros === --}}
                <x-menu-sub title="Cadastros" icon="fas.toolbox">
                    <x-menu-item title="Tratamentos" icon="fas.briefcase-medical" link="{{ route('admin.treatments.index') }}" />
                    <x-menu-item title="Tabelas de Preço/Repasse" icon="fas.tags" link="{{ route('admin.treatments.tables') }}" />
                    <x-menu-item title="Convênios" icon="fas.id-card" link="{{ route('admin.insurances.index') }}" />
                    <x-menu-item title="Unidades" icon="fas.building" link="{{ route('admin.locations.index') }}" />
                    <x-menu-item title="Salas" icon="fas.chair" link="{{ route('admin.rooms.index') }}" />
                    <x-menu-item title="Restrições" icon="fas.ban" link="{{ route('admin.restrictions.index') }}" />
                    <x-menu-item title="Feriados" icon="fas.calendar-xmark" link="{{ route('admin.holidays.index') }}" />
                </x-menu-sub>
            </x-menu>
        </x-slot:sidebar>


            {{-- BRAND --}}


        <x-slot:content>
            <div class="md:mt-25">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main-custom>

    {{--  TOAST area --}}
    <x-toast />
    @stack('scripts')
</body>
</html>
