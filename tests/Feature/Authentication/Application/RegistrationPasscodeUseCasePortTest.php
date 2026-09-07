<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Application;

use ReflectionMethod;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscode;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInput;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInputPort;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInterface;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeOutput;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeOutputPort;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscode;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeInput;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeInputPort;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeInterface;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeOutput;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeOutputPort;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;
use Tests\TestCase;

final class RegistrationPasscodeUseCasePortTest extends TestCase
{
    public function test_generate_registration_passcode_uses_input_and_output_ports(): void
    {
        self::assertInstanceOf(GenerateRegistrationPasscodeInputPort::class, new GenerateRegistrationPasscodeInput(new EmailAddress('new@example.com')));
        self::assertInstanceOf(GenerateRegistrationPasscodeOutputPort::class, new GenerateRegistrationPasscodeOutput('challenge-identifier'));

        $this->assertExecuteSignature(
            GenerateRegistrationPasscodeInterface::class,
            GenerateRegistrationPasscode::class,
            GenerateRegistrationPasscodeInputPort::class,
            GenerateRegistrationPasscodeOutputPort::class,
        );
    }

    public function test_verify_registration_passcode_uses_input_and_output_ports(): void
    {
        self::assertInstanceOf(
            VerifyRegistrationPasscodeInputPort::class,
            new VerifyRegistrationPasscodeInput(new RegistrationPasscodeChallengeIdentifier('challenge-identifier'), new LoginPasscode('123456')),
        );
        self::assertInstanceOf(VerifyRegistrationPasscodeOutputPort::class, VerifyRegistrationPasscodeOutput::rejected());

        $this->assertExecuteSignature(
            VerifyRegistrationPasscodeInterface::class,
            VerifyRegistrationPasscode::class,
            VerifyRegistrationPasscodeInputPort::class,
            VerifyRegistrationPasscodeOutputPort::class,
        );
    }

    /** @param class-string $interface @param class-string $useCase */
    private function assertExecuteSignature(string $interface, string $useCase, string $inputPort, string $outputPort): void
    {
        foreach ([$interface, $useCase] as $class) {
            $method = new ReflectionMethod($class, 'execute');

            self::assertSame($inputPort, $method->getParameters()[0]->getType()?->getName());
            self::assertSame($outputPort, $method->getReturnType()?->getName());
        }
    }
}
