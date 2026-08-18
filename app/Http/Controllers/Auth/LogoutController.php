<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LogoutController extends Controller
{
    public function __invoke(LogoutAction $action): RedirectResponse
    {
        $action->execute();

        return redirect()->route('login');
    }
}
