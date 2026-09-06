<?php

declare(strict_types=1);

use App\Http\Actions\Account\CreateAccountAction;
use App\Http\Actions\Account\CreateBlockAction;
use App\Http\Actions\Account\CreateFollowAction;
use App\Http\Actions\Account\GetFollowingPostsAction;
use App\Http\Actions\Authentication\GenerateLoginPasscodeAction;
use App\Http\Actions\Authentication\VerifyLoginPasscodeAction;
use App\Http\Actions\Like\GetMyLikesAction;
use App\Http\Actions\Report\CreateReportAction;
use App\Http\Actions\Routine\CreateRoutineAction;
use App\Http\Actions\RoutineExecution\CreateRoutineExecutionAction;
use App\Http\Actions\Support\GetMySupportsAction;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(static function (): void {
    Route::post('/accounts', CreateAccountAction::class);
    Route::post('/login-passcodes', GenerateLoginPasscodeAction::class);
    Route::post('/login-passcodes/verification', VerifyLoginPasscodeAction::class);
    Route::post('/routines', CreateRoutineAction::class);
    Route::post('/routine-executions', CreateRoutineExecutionAction::class);
    Route::post('/reports', CreateReportAction::class);
    Route::post('/follows', CreateFollowAction::class);
    Route::get('/following/posts', GetFollowingPostsAction::class);
    Route::post('/blocks', CreateBlockAction::class);
    Route::get('/my/likes', GetMyLikesAction::class);
    Route::get('/my/supports', GetMySupportsAction::class);
});
