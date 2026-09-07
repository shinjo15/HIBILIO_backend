<?php

declare(strict_types=1);

use App\Http\Actions\Account\CreateAccountAction;
use App\Http\Actions\Account\CreateBlockAction;
use App\Http\Actions\Account\CreateFollowAction;
use App\Http\Actions\Account\GetFavoriteTagPostsAction;
use App\Http\Actions\Account\GetFollowingPostsAction;
use App\Http\Actions\Account\GetPopularRoutinePostsAction;
use App\Http\Actions\Authentication\GenerateLoginPasscodeAction;
use App\Http\Actions\Authentication\GenerateRegistrationPasscodeAction;
use App\Http\Actions\Authentication\VerifyLoginPasscodeAction;
use App\Http\Actions\Authentication\VerifyRegistrationPasscodeAction;
use App\Http\Actions\Like\GetMyLikesAction;
use App\Http\Actions\Report\CreateReportAction;
use App\Http\Actions\Routine\CreateRoutineAction;
use App\Http\Actions\RoutineExecution\CreateRoutineExecutionAction;
use App\Http\Actions\Support\GetMySupportsAction;
use App\Http\Actions\Tag\GetTagsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(static function (): void {
    Route::get('/csrf-token', static fn (Request $request): JsonResponse => response()->json([
        'csrf_token' => $request->session()->token(),
    ]));

    Route::post('/accounts', CreateAccountAction::class);
    Route::post('/login-passcodes', GenerateLoginPasscodeAction::class);
    Route::post('/login-passcodes/verification', VerifyLoginPasscodeAction::class);
    Route::post('/registration-passcodes', GenerateRegistrationPasscodeAction::class);
    Route::post('/registration-passcodes/verification', VerifyRegistrationPasscodeAction::class);
    Route::post('/routines', CreateRoutineAction::class);
    Route::post('/routine-executions', CreateRoutineExecutionAction::class);
    Route::get('/tags', GetTagsAction::class);
    Route::post('/reports', CreateReportAction::class);
    Route::post('/follows', CreateFollowAction::class);
    Route::get('/following/posts', GetFollowingPostsAction::class);
    Route::get('/posts/popular', GetPopularRoutinePostsAction::class);
    Route::get('/posts/favorite_tags', GetFavoriteTagPostsAction::class);
    Route::post('/blocks', CreateBlockAction::class);
    Route::get('/my/likes', GetMyLikesAction::class);
    Route::get('/my/supports', GetMySupportsAction::class);
});
