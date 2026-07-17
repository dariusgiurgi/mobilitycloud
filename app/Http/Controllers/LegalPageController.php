<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalPageController extends Controller
{
    public function terms(): View
    {
        return $this->page('Terms of Service', 'terms');
    }

    public function privacy(): View
    {
        return $this->page('Privacy Policy', 'privacy');
    }

    public function cookies(): View
    {
        return $this->page('Cookie Policy', 'cookies');
    }

    public function security(): View
    {
        return $this->page('Security', 'security');
    }

    private function page(string $title, string $type): View
    {
        return view('legal.page', [
            'title' => $title,
            'type' => $type,
            'company' => config('mobilitycloud.company'),
            'emails' => config('mobilitycloud.emails'),
        ]);
    }
}
