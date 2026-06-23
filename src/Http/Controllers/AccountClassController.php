<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Routing\Controller;
use Numerar\Contable\Http\Resources\AccountClassResource;
use Numerar\Contable\Models\AccountClass;

class AccountClassController extends Controller
{
    public function index()
    {
        $classes = AccountClass::orderBy('code')->get();

        if (request()->expectsJson()) {
            return AccountClassResource::collection($classes);
        }

        return view('contable::account-classes.index', compact('classes'));
    }
}
