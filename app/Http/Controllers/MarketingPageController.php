<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MarketingPageController extends Controller
{
    public function home(): View
    {
        return $this->page('home');
    }

    public function features(): View
    {
        return $this->page('features');
    }

    public function pricing(): View
    {
        return $this->page('pricing');
    }

    public function guide(): View
    {
        return $this->page('guide');
    }

    public function help(): View
    {
        return $this->page('help');
    }

    public function contact(): View
    {
        return $this->page('contact');
    }

    private function page(string $page): View
    {
        return view('public.marketing', [
            'page' => $page,
            'company' => config('mobilitycloud.company'),
            'emails' => config('mobilitycloud.emails'),
        ]);
    }
}
