<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;

class LandingController extends Controller
{
    public function index()
    {
        return Inertia::render('LandingPage/Index');
    }
}
