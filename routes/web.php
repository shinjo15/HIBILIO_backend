<?php

use App\Http\Actions\Authentication\CompleteSocialLoginAction;
use App\Http\Actions\Authentication\StartSocialLoginAction;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/social/{provider}', StartSocialLoginAction::class)
    ->whereIn('provider', ['google', 'apple']);
Route::get('/auth/social/{provider}/callback', CompleteSocialLoginAction::class)
    ->whereIn('provider', ['google', 'apple']);
