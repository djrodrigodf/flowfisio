<div class="relative p-6 bg-white z-1 bg-base-300 sm:p-0">
    <div class="relative flex flex-col justify-center w-full h-screen bg-base-300 sm:p-0 lg:flex-row">
        <!-- Form -->
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
            <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
                <div>
                    <div class="mb-5 sm:mb-8">
                        <h1 class="mb-2 font-semibold text-title-sm sm:text-title-md">
                            Entrar na sua conta
                        </h1>
                        <p class="text-sm">
                            Entre com seu e-mail e senha!
                        </p>
                    </div>
                    <div>

                        <x-form wire:submit="authenticate">
                            <div class="space-y-5">

                                <div>
                                    <x-input label="E-mail" icon="o-envelope" wire:model="email" />
                                </div>
                                <!-- Password -->
                                <div>
                                    <x-input label="Senha" wire:model="password" icon="o-key" type="password" />

                                </div>
                                <!-- Checkbox -->
                                <div class="flex items-center justify-between gap-4">
                                    <div class=" w-full">
                                        <x-button link="{{ route('password.request') }}" class="btn-secondary  w-full" label="Esqueci minha senha" />
                                    </div>
                                    <div class=" w-full">
                                        <x-button label="Login" class="btn-primary  w-full" type="submit" spinner="save" />
                                    </div>
                                </div>
                                <!-- Button -->
                                <div>


                                </div>
                            </div>
                        </x-form>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative items-center hidden w-full h-full bg-primary lg:grid lg:w-1/2">
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
                        <svg width="400" xmlns="http://www.w3.org/2000/svg" font-family="Arial, sans-serif">
                            <style>
                                :root {
                                    --flow-color: #04525d;
                                    --fisio-color: #ff9800;
                                    --text-color: #333333;
                                }
                                @media (prefers-color-scheme: dark) {
                                    :root {
                                        --flow-color: #04525d;
                                        --fisio-color: #ff9800;
                                        --text-color: #333333;
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
                    <p class="text-center">
                        Sistema de gestão para clínicas de fisioterapia, com agendamento online, prontuário digital e muito mais!
                    </p>
                </div>
            </div>
        </div>
        <!-- Toggler -->

        <div class="fixed z-50 bottom-6 right-6">
            <x-theme-toggle class="btn btn-circle btn-ghost" />
        </div>
    </div>
</div>
