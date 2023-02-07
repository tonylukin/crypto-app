<?php

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

trait ReturnUrlTrait
{
    protected function redirectToReturnUrl(Request $request, string $defaultRoute): RedirectResponse
    {
        $url = $request->get('returnUrl');
        return $url ? $this->redirect($url) : $this->redirectToRoute($defaultRoute);
    }
}