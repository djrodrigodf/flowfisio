<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;
use Mary\Traits\Toast;

class Login extends Component
{
    use Toast;

    public $title = 'Welcome to the future !';

    public ?string $email;

    public ?string $password;

    public function authenticate()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'E-mail é obrigatório',
            'email.email' => 'E-mail inválido',
            'password.required' => 'Senha é obrigatória',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            request()->session()->regenerate();
            $this->success('Login efetuado com sucesso!', position: 'toast-top');

            return Redirect::route('welcome');
        } else {
            $this->error('Dados invalidos!', position: 'toast-top');
        }
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.appNoSideBar');
    }
}
