<?php

namespace App\Livewire;

use Gravatar\Gravatar;
use Laravolt\Avatar\Avatar;
use Livewire\Component;

class HeaderFlow extends Component
{

    public function sair() {
        return redirect()->route('logout');
    }

    public function render()

    {
        $user = auth()->user();
        $avatar = new Avatar();
        $avatar->create($user->name)->setBackground('#001122')->setForeground('#999999')->toBase64();


        return view('livewire.header-flow', compact('user', 'avatar'));
    }
}
