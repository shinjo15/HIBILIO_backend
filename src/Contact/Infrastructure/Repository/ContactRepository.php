<?php

declare(strict_types=1);

namespace Src\Contact\Infrastructure\Repository;

use App\Models\ContactModel;
use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\Repository\ContactRepositoryInterface;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactIdentifier;
use Src\Contact\Domain\ValueObject\ContactSendStatus;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ContactRepository implements ContactRepositoryInterface
{
    public function find(ContactIdentifier $contactIdentifier): ?Contact
    {
        $model = ContactModel::query()->find($contactIdentifier->value());

        return $model === null ? null : $this->restore($model);
    }

    public function save(Contact $contact): void
    {
        ContactModel::query()->updateOrCreate(
            ['contact_identifier' => $contact->contactIdentifier()->value()],
            [
                'account_identifier' => $contact->accountIdentifier()->value(),
                'title' => $contact->title()->value(),
                'content' => $contact->content()->value(),
                'status' => $contact->sendStatus()->value,
                'sent_at' => $contact->sentAt(),
            ],
        );
    }

    private function restore(ContactModel $model): Contact
    {
        return Contact::restore(
            new ContactIdentifier($model->contact_identifier),
            new AccountIdentifier($model->account_identifier),
            new ContactTitle($model->title),
            new ContactContent($model->content),
            ContactSendStatus::from($model->status),
            $model->sent_at,
        );
    }
}
