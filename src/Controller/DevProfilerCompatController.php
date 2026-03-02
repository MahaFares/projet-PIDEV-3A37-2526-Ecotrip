<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class DevProfilerCompatController extends AbstractController
{
    public function profiler(string $path, Request $request): RedirectResponse
    {
        $target = '/_profiler';
        if ($path !== '') {
            $target .= '/' . ltrim($path, '/');
        }

        $query = $request->getQueryString();
        if ($query !== null && $query !== '') {
            $target .= '?' . $query;
        }

        return $this->redirect($target, 302);
    }

    public function wdt(string $token, Request $request): RedirectResponse
    {
        $target = '/_wdt/' . ltrim($token, '/');

        $query = $request->getQueryString();
        if ($query !== null && $query !== '') {
            $target .= '?' . $query;
        }

        return $this->redirect($target, 302);
    }
}
