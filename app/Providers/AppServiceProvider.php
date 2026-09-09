<?php

namespace App\Providers;

use App\Support\LaravelUuidServices;
use Illuminate\Support\ServiceProvider;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Application\Service\AccountRegistrationMailServiceInterface;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Application\Usecase\Command\ChangeAccountStatus\ChangeAccountStatus;
use Src\Account\Application\Usecase\Command\ChangeAccountStatus\ChangeAccountStatusInterface;
use Src\Account\Application\Usecase\Command\ChangeAccountVisibility\ChangeAccountVisibility;
use Src\Account\Application\Usecase\Command\ChangeAccountVisibility\ChangeAccountVisibilityInterface;
use Src\Account\Application\Usecase\Command\CreateAccount\CreateAccount;
use Src\Account\Application\Usecase\Command\CreateAccount\CreateAccountInterface;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlock;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlockInterface;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollow;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollowInterface;
use Src\Account\Application\Usecase\Command\RemoveBlock\RemoveBlock;
use Src\Account\Application\Usecase\Command\RemoveBlock\RemoveBlockInterface;
use Src\Account\Application\Usecase\Command\UpdateAccountProfile\UpdateAccountProfile;
use Src\Account\Application\Usecase\Command\UpdateAccountProfile\UpdateAccountProfileInterface;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInterface;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsInterface;
use Src\Account\Application\Usecase\Query\GetFollowingPosts\GetFollowingPostsInterface;
use Src\Account\Application\Usecase\Query\GetPopularRoutinePosts\GetPopularRoutinePostsInterface;
use Src\Account\Domain\Factory\AccountFactoryInterface;
use Src\Account\Domain\Factory\BlockFactoryInterface;
use Src\Account\Domain\Factory\FollowFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\Repository\BlockRepositoryInterface;
use Src\Account\Domain\Repository\FollowRepositoryInterface;
use Src\Account\Infrastructure\Factory\AccountFactory;
use Src\Account\Infrastructure\Factory\BlockFactory;
use Src\Account\Infrastructure\Factory\FollowFactory;
use Src\Account\Infrastructure\Query\GetAccountDetails\GetAccountDetails;
use Src\Account\Infrastructure\Query\GetAccountRoutinePosts\GetAccountRoutinePosts;
use Src\Account\Infrastructure\Query\GetFavoriteTagPosts\GetFavoriteTagPosts;
use Src\Account\Infrastructure\Query\GetFollowingPosts\GetFollowingPosts;
use Src\Account\Infrastructure\Query\GetPopularRoutinePosts\GetPopularRoutinePosts;
use Src\Account\Infrastructure\Repository\AccountRepository;
use Src\Account\Infrastructure\Repository\BlockRepository;
use Src\Account\Infrastructure\Repository\FollowRepository;
use Src\Account\Infrastructure\Service\AccountImageConverterService;
use Src\Account\Infrastructure\Service\LaravelAccountRegistrationMailService;
use Src\Account\Infrastructure\Service\LocalStorageService;
use Src\Account\Infrastructure\Service\S3StorageService;
use Src\Authentication\Application\Service\LoginPasscodeGeneratorServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeHashServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeMailServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeStateServiceInterface;
use Src\Authentication\Application\Service\PasscodeSessionServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeMailServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeStateServiceInterface;
use Src\Authentication\Application\UseCase\GenerateLoginPasscode\GenerateLoginPasscode;
use Src\Authentication\Application\UseCase\GenerateLoginPasscode\GenerateLoginPasscodeInterface;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscode;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInterface;
use Src\Authentication\Application\UseCase\VerifyLoginPasscode\VerifyLoginPasscode;
use Src\Authentication\Application\UseCase\VerifyLoginPasscode\VerifyLoginPasscodeInterface;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscode;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeInterface;
use Src\Authentication\Domain\Factory\LoginPasscodeChallengeFactoryInterface;
use Src\Authentication\Domain\Factory\RegistrationPasscodeChallengeFactoryInterface;
use Src\Authentication\Infrastructure\Factory\LoginPasscodeChallengeFactory;
use Src\Authentication\Infrastructure\Factory\RegistrationPasscodeChallengeFactory;
use Src\Authentication\Infrastructure\Service\LaravelPasscodeSessionService;
use Src\Authentication\Infrastructure\Service\LaravelRegistrationPasscodeSessionService;
use Src\Authentication\Infrastructure\Service\LoginPasscodeGeneratorService;
use Src\Authentication\Infrastructure\Service\LoginPasscodeHashService;
use Src\Authentication\Infrastructure\Service\LoginPasscodeMailService;
use Src\Authentication\Infrastructure\Service\RedisLoginPasscodeStateService;
use Src\Authentication\Infrastructure\Service\RedisRegistrationPasscodeStateService;
use Src\Authentication\Infrastructure\Service\RegistrationPasscodeMailService;
use Src\Like\Application\UseCase\CreateLike\CreateLike;
use Src\Like\Application\UseCase\CreateLike\CreateLikeInterface;
use Src\Like\Application\Usecase\Query\GetLikedRoutinePosts\GetLikedRoutinePostsInterface;
use Src\Like\Application\UseCase\RemoveLike\RemoveLike;
use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInterface;
use Src\Like\Domain\Factory\LikeFactoryInterface;
use Src\Like\Domain\Repository\LikeRepositoryInterface;
use Src\Like\Infrastructure\Factory\LikeFactory;
use Src\Like\Infrastructure\Query\GetLikedRoutinePosts\GetLikedRoutinePosts;
use Src\Like\Infrastructure\Repository\LikeRepository;
use Src\Post\Infrastructure\Factory\PostFactory;
use Src\Post\Infrastructure\Repository\PostRepository;
use Src\Report\Application\UseCase\CreateReport\CreateReport;
use Src\Report\Application\UseCase\CreateReport\CreateReportInterface;
use Src\Report\Domain\Factory\ReportFactoryInterface;
use Src\Report\Domain\Repository\AccountRepositoryInterface as ReportAccountRepositoryInterface;
use Src\Report\Domain\Repository\ReportRepositoryInterface;
use Src\Report\Infrastructure\Factory\ReportFactory;
use Src\Report\Infrastructure\Repository\AccountRepository as ReportAccountRepository;
use Src\Report\Infrastructure\Repository\ReportRepository;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutine;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutineInterface;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInterface;
use Src\Routine\Domain\Factory\RoutineActionFactoryInterface;
use Src\Routine\Domain\Factory\RoutineFactoryInterface;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Routine\Infrastructure\Factory\RoutineActionFactory;
use Src\Routine\Infrastructure\Factory\RoutineFactory;
use Src\Routine\Infrastructure\Query\GetCustomizedRoutines\GetCustomizedRoutines;
use Src\Routine\Infrastructure\Query\GetRoutineDetails\GetRoutineDetails;
use Src\Routine\Infrastructure\Query\GetRoutineExecutionPosts\GetRoutineExecutionPosts;
use Src\Routine\Infrastructure\Repository\RoutineActionRepository;
use Src\Routine\Infrastructure\Repository\RoutineRepository;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecution;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionRepositoryInterface;
use Src\RoutineExecution\Infrastructure\Factory\RoutineExecutionActionFactory;
use Src\RoutineExecution\Infrastructure\Factory\RoutineExecutionFactory;
use Src\RoutineExecution\Infrastructure\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutions;
use Src\RoutineExecution\Infrastructure\Repository\RoutineExecutionActionRepository;
use Src\RoutineExecution\Infrastructure\Repository\RoutineExecutionRepository;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Factory\PostFactoryInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Src\Shared\Infrastructure\Transaction\LaravelTransactionManager;
use Src\Support\Application\UseCase\CreateSupport\CreateSupport;
use Src\Support\Application\UseCase\CreateSupport\CreateSupportInterface;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInterface;
use Src\Support\Application\UseCase\RemoveSupport\RemoveSupport;
use Src\Support\Application\UseCase\RemoveSupport\RemoveSupportInterface;
use Src\Support\Domain\Factory\SupportFactoryInterface;
use Src\Support\Domain\Repository\SupportRepositoryInterface;
use Src\Support\Infrastructure\Factory\SupportFactory;
use Src\Support\Infrastructure\Query\GetMySupports\GetMySupports;
use Src\Support\Infrastructure\Repository\SupportRepository;
use Src\Tag\Application\UseCase\CreateTag\CreateTag;
use Src\Tag\Application\UseCase\CreateTag\CreateTagInterface;
use Src\Tag\Application\Usecase\Query\GetPickupTags\GetPickupTagsInterface;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsInterface;
use Src\Tag\Domain\Factory\TagFactoryInterface;
use Src\Tag\Domain\Repository\TagRepositoryInterface;
use Src\Tag\Infrastructure\Factory\TagFactory;
use Src\Tag\Infrastructure\Query\GetPickupTags\GetPickupTags;
use Src\Tag\Infrastructure\Query\GetTags\GetTags;
use Src\Tag\Infrastructure\Repository\TagRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, LaravelAuthService::class);
        $this->app->bind(LoginPasscodeGeneratorServiceInterface::class, LoginPasscodeGeneratorService::class);
        $this->app->bind(LoginPasscodeHashServiceInterface::class, LoginPasscodeHashService::class);
        $this->app->bind(LoginPasscodeMailServiceInterface::class, LoginPasscodeMailService::class);
        $this->app->bind(LoginPasscodeStateServiceInterface::class, RedisLoginPasscodeStateService::class);
        $this->app->bind(PasscodeSessionServiceInterface::class, LaravelPasscodeSessionService::class);
        $this->app->bind(LoginPasscodeChallengeFactoryInterface::class, LoginPasscodeChallengeFactory::class);
        $this->app->bind(GenerateLoginPasscodeInterface::class, GenerateLoginPasscode::class);
        $this->app->bind(VerifyLoginPasscodeInterface::class, VerifyLoginPasscode::class);
        $this->app->bind(RegistrationPasscodeMailServiceInterface::class, RegistrationPasscodeMailService::class);
        $this->app->bind(RegistrationPasscodeStateServiceInterface::class, RedisRegistrationPasscodeStateService::class);
        $this->app->bind(RegistrationPasscodeSessionServiceInterface::class, LaravelRegistrationPasscodeSessionService::class);
        $this->app->bind(RegistrationPasscodeChallengeFactoryInterface::class, RegistrationPasscodeChallengeFactory::class);
        $this->app->bind(GenerateRegistrationPasscodeInterface::class, GenerateRegistrationPasscode::class);
        $this->app->bind(VerifyRegistrationPasscodeInterface::class, VerifyRegistrationPasscode::class);
        $this->app->bind(UuidServiceInterface::class, LaravelUuidServices::class);
        $this->app->bind(AccountFactoryInterface::class, AccountFactory::class);
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(AccountRegistrationMailServiceInterface::class, LaravelAccountRegistrationMailService::class);
        $this->app->bind(AccountImageConverterServiceInterface::class, AccountImageConverterService::class);
        $this->app->bind(
            StorageServiceInterface::class,
            config('account.images.storage') === 's3'
                ? S3StorageService::class
                : LocalStorageService::class,
        );
        $this->app->bind(CreateAccountInterface::class, CreateAccount::class);
        $this->app->bind(UpdateAccountProfileInterface::class, UpdateAccountProfile::class);
        $this->app->bind(ChangeAccountStatusInterface::class, ChangeAccountStatus::class);
        $this->app->bind(ChangeAccountVisibilityInterface::class, ChangeAccountVisibility::class);
        $this->app->bind(GetAccountDetailsInterface::class, GetAccountDetails::class);
        $this->app->bind(GetAccountRoutinePostsInterface::class, GetAccountRoutinePosts::class);
        $this->app->bind(TagFactoryInterface::class, TagFactory::class);
        $this->app->bind(TagRepositoryInterface::class, TagRepository::class);
        $this->app->bind(CreateTagInterface::class, CreateTag::class);
        $this->app->bind(GetPickupTagsInterface::class, GetPickupTags::class);
        $this->app->bind(GetTagsInterface::class, GetTags::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->bind(LikeFactoryInterface::class, LikeFactory::class);
        $this->app->bind(LikeRepositoryInterface::class, LikeRepository::class);
        $this->app->bind(CreateLikeInterface::class, CreateLike::class);
        $this->app->bind(RemoveLikeInterface::class, RemoveLike::class);

        $this->app->bind(GetLikedRoutinePostsInterface::class, GetLikedRoutinePosts::class);
        $this->app->bind(FollowFactoryInterface::class, FollowFactory::class);
        $this->app->bind(FollowRepositoryInterface::class, FollowRepository::class);

        $this->app->bind(CreateFollowInterface::class, CreateFollow::class);
        $this->app->bind(GetFollowingPostsInterface::class, GetFollowingPosts::class);
        $this->app->bind(GetPopularRoutinePostsInterface::class, GetPopularRoutinePosts::class);
        $this->app->bind(GetFavoriteTagPostsInterface::class, GetFavoriteTagPosts::class);
        $this->app->bind(BlockFactoryInterface::class, BlockFactory::class);
        $this->app->bind(BlockRepositoryInterface::class, BlockRepository::class);
        $this->app->bind(CreateBlockInterface::class, CreateBlock::class);
        $this->app->bind(RemoveBlockInterface::class, RemoveBlock::class);
        $this->app->bind(SupportFactoryInterface::class, SupportFactory::class);
        $this->app->bind(SupportRepositoryInterface::class, SupportRepository::class);
        $this->app->bind(CreateSupportInterface::class, CreateSupport::class);
        $this->app->bind(RemoveSupportInterface::class, RemoveSupport::class);
        $this->app->bind(GetMySupportsInterface::class, GetMySupports::class);
        $this->app->bind(RoutineFactoryInterface::class, RoutineFactory::class);
        $this->app->bind(RoutineRepositoryInterface::class, RoutineRepository::class);
        $this->app->bind(RoutineActionFactoryInterface::class, RoutineActionFactory::class);
        $this->app->bind(RoutineActionRepositoryInterface::class, RoutineActionRepository::class);
        $this->app->bind(PostFactoryInterface::class, PostFactory::class);
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(CreateRoutineInterface::class, CreateRoutine::class);
        $this->app->bind(GetCustomizedRoutinesInterface::class, GetCustomizedRoutines::class);
        $this->app->bind(GetRoutineDetailsInterface::class, GetRoutineDetails::class);
        $this->app->bind(GetRoutineExecutionPostsInterface::class, GetRoutineExecutionPosts::class);
        $this->app->bind(RoutineExecutionFactoryInterface::class, RoutineExecutionFactory::class);
        $this->app->bind(RoutineExecutionActionFactoryInterface::class, RoutineExecutionActionFactory::class);
        $this->app->bind(RoutineExecutionRepositoryInterface::class, RoutineExecutionRepository::class);
        $this->app->bind(RoutineExecutionActionRepositoryInterface::class, RoutineExecutionActionRepository::class);
        $this->app->bind(CreateRoutineExecutionInterface::class, CreateRoutineExecution::class);
        $this->app->bind(GetAccountRoutineExecutionsInterface::class, GetAccountRoutineExecutions::class);
        $this->app->bind(ReportFactoryInterface::class, ReportFactory::class);
        $this->app->bind(ReportAccountRepositoryInterface::class, ReportAccountRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(CreateReportInterface::class, CreateReport::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
