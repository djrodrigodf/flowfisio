<div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
    <div class="relative flex flex-col justify-center w-full h-screen dark:bg-gray-900 sm:p-0 lg:flex-row">
        <!-- Form -->

        @if (Session::has('message'))
            <span style="color: green;">{{ Session::get('message') }}</span>
        @endif
        @if (Session::has('email'))
            <span style="color: red;">{{ Session::get('email') }}</span>
        @endif

        <div class="flex flex-col flex-1 w-full lg:w-1/2">
            <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
                <div>
                    <div class="mb-5 sm:mb-8">
                        <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                            Esqueci minha senha?
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Digite o endereço de e-mail vinculado à sua conta e lhe enviaremos um link para redefinir sua senha.
                        </p>
                    </div>
                    <div>

                        <x-form wire:submit="sendVerificationMail">
                            <x-input label="Email" icon="o-envelope" wire:model="email" />
                            <x-slot:actions>
                                <x-button class="btn-primary" label="Enviar" type="submit" spinner="save" />
                            </x-slot:actions>
                        </x-form>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative items-center hidden w-full h-full bg-brand-950 dark:bg-white/5 lg:grid lg:w-1/2">
            <div class="flex items-center justify-center z-1">
                <!-- ===== Common Grid Shape Start ===== -->
                <div class="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
                    <img src="https://demo.tailadmin.com/src/images/shape/grid-01.svg" alt="grid">
                </div>
                <div class="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
                    <img src="https://demo.tailadmin.com/src/images/shape/grid-01.svg" alt="grid">
                </div>

                <div class="flex flex-col items-center max-w-xs">
                    <div class="text-center mb-4">
                        <svg width="300" height="100" viewBox="0 0 600 200" xmlns="http://www.w3.org/2000/svg" font-family="Arial, sans-serif">
                            <style>
                                :root {
                                    --flow-color: #0097a7;
                                    --fisio-color: #ff9800;
                                    --text-color: #333333;
                                }
                                @media (prefers-color-scheme: dark) {
                                    :root {
                                        --flow-color: #80deea;
                                        --fisio-color: #ffcc80;
                                        --text-color: #ffffff;
                                    }
                                }
                            </style>

                            <text x="30" y="90" font-size="60" font-weight="bold">
                                <tspan fill="var(--flow-color)">Flow</tspan>
                                <tspan fill="var(--fisio-color)">Fisio</tspan>
                            </text>
                            <text x="32" y="135" font-size="28" fill="var(--text-color)">sistema de fisioterapia</text>
                        </svg>

                    </div>
                    <p class="text-center text-gray-400 dark:text-white/60">
                        Sistema de gestão para clínicas de fisioterapia, com agendamento online, prontuário digital e muito mais!
                    </p>
                </div>
            </div>
        </div>
        <!-- Toggler -->

        <div class="fixed z-50 bottom-6 right-6">
            <x-theme-toggle class="btn btn-circle btn-ghost" darkTheme="flowfisio-dark" lightTheme="flowfisio-light" />
        </div>
    </div>
</div>
