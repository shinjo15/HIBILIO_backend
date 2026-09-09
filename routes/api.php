<?php

declare(strict_types=1);

use App\Http\Actions\Account\ChangeAccountVisibilityAction;
use App\Http\Actions\Account\CreateAccountAction;
use App\Http\Actions\Account\CreateBlockAction;
use App\Http\Actions\Account\CreateFollowAction;
use App\Http\Actions\Account\GetAccountDetailsAction;
use App\Http\Actions\Account\GetAccountRoutinePostsAction;
use App\Http\Actions\Account\GetFavoriteTagPostsAction;
use App\Http\Actions\Account\GetFollowingPostsAction;
use App\Http\Actions\Account\GetMyAccountAction;
use App\Http\Actions\Account\GetMyRoutinePostsAction;
use App\Http\Actions\Account\GetPopularRoutinePostsAction;
use App\Http\Actions\Account\UpdateAccountProfileAction;
use App\Http\Actions\Authentication\GenerateLoginPasscodeAction;
use App\Http\Actions\Authentication\GenerateRegistrationPasscodeAction;
use App\Http\Actions\Authentication\VerifyLoginPasscodeAction;
use App\Http\Actions\Authentication\VerifyRegistrationPasscodeAction;
use App\Http\Actions\Like\CreateLikeAction;
use App\Http\Actions\Like\GetAccountLikedRoutinePostsAction;
use App\Http\Actions\Like\GetMyLikedRoutinePostsAction;
use App\Http\Actions\Report\CreateReportAction;
use App\Http\Actions\Routine\CreateRoutineAction;
use App\Http\Actions\Routine\GetCustomizedRoutinesAction;
use App\Http\Actions\Routine\GetRoutineDetailsAction;
use App\Http\Actions\Routine\GetRoutineExecutionPostsAction;
use App\Http\Actions\RoutineExecution\CreateRoutineExecutionAction;
use App\Http\Actions\RoutineExecution\GetAccountRoutineExecutionsAction;
use App\Http\Actions\RoutineExecution\GetMyRoutineExecutionsAction;
use App\Http\Actions\Support\GetMySupportsAction;
use App\Http\Actions\Tag\GetPickupTagsAction;
use App\Http\Actions\Tag\GetTagsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(static function (): void {
    Route::get('/csrf-token', static fn (Request $request): JsonResponse => response()->json([
        'csrf_token' => $request->session()->token(),
    ]));

    Route::post('/accounts', CreateAccountAction::class);
    Route::get('/accounts/{account_identifier}', GetAccountDetailsAction::class);
    Route::get('/accounts/{account_identifier}/likes', GetAccountLikedRoutinePostsAction::class);
    Route::get('/accounts/{account_identifier}/posts', GetAccountRoutinePostsAction::class);
    Route::get('/accounts/{account_identifier}/routine-executions', GetAccountRoutineExecutionsAction::class);
    Route::get('/my/account', GetMyAccountAction::class);
    Route::patch('/my/account', UpdateAccountProfileAction::class);
    Route::patch('/my/account/visibility', ChangeAccountVisibilityAction::class);
    Route::get('/my/posts', GetMyRoutinePostsAction::class);
    Route::get('/my/routine-executions', GetMyRoutineExecutionsAction::class);
    Route::post('/login-passcodes', GenerateLoginPasscodeAction::class);
    Route::post('/login-passcodes/verification', VerifyLoginPasscodeAction::class);
    Route::post('/registration-passcodes', GenerateRegistrationPasscodeAction::class);
    Route::post('/registration-passcodes/verification', VerifyRegistrationPasscodeAction::class);
    Route::post('/routines', CreateRoutineAction::class);
    Route::get('/routines/{routine_identifier}/customized', GetCustomizedRoutinesAction::class);
    Route::get('/routines/{routine_identifier}/execution-posts', GetRoutineExecutionPostsAction::class);
    Route::get('/routines/{routine_identifier}', GetRoutineDetailsAction::class);
    Route::post('/routine-executions', CreateRoutineExecutionAction::class);
    Route::get('/tags', GetTagsAction::class);
    Route::get('/tags/pickup', GetPickupTagsAction::class);
    Route::post('/reports', CreateReportAction::class);
    Route::post('/follows', CreateFollowAction::class);
    Route::get('/following/posts', GetFollowingPostsAction::class);
    Route::get('/posts/popular', GetPopularRoutinePostsAction::class);
    Route::get('/posts/favorite_tags', GetFavoriteTagPostsAction::class);
    Route::post('/blocks', CreateBlockAction::class);
    Route::post('/likes', CreateLikeAction::class);
    Route::get('/my/likes', GetMyLikedRoutinePostsAction::class);
    Route::get('/my/supports', GetMySupportsAction::class);
});
