# WeDevelop Audit Scaffolding

A basic tutorial of what code needs to be written in your Symfony application to
use this library.

## Setup

1. Configure Doctrine entities/documents.
2. Configure the Presenter service.
3. Write some audit log classes.
4. Add translations.

### Configure Doctrine Entities/Documents

#### Doctrine Configuration

In order for your entity/document to extend the base audit entity/document, you
must configure Doctrine to read mapping information from the library.

<details>
<summary>Using ORM</summary>

```yaml
doctrine:
    orm:
        mappings:
            WeDevelopAudit:
                is_bundle: false
                dir: '%kernel.project_dir%/vendor/webdevelopnl/audit-scaffold/src/Entity'
                prefix: 'WeDevelop\Audit\Entity'
                alias: 'Audit'
```

</details>

<details>
<summary>Using MongoDB ODM</summary>

```yaml
doctrine_mongodb:
    document_managers:
        default: # <- replace with your document manager name if different.
            mappings:
                WeDevelopAudit:
                    is_bundle: false
                    type: attribute
                    # The classes in `WeDevelop\Audit\Entity` namespace can be used as both entities and documents.
                    dir: '%kernel.project_dir%/vendor/webdevelopnl/audit-scaffold/src/Entity'
                    prefix: 'WeDevelop\Audit\Entity'
                    alias: 'Audit'
```

</details>

#### Userland Doctrine Entities/Documents

This way you can control the entity/document repository, table/collection name,
override properties, etc.

<details>
<summary>Using ORM</summary>

```php
<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use WeDevelop\Audit\AuditLogInterface;
use WeDevelop\Audit\Entity\AbstractAuditEntity;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\ValueObject\IpAddress;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog extends AbstractAuditEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?MyUser $user;

    /**
     * @param class-string<AuditLogInterface> $action
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        string $action,
        AuditSource $source,
        ?MyUser $user,
        ?Subject $subject = null,
        ?IpAddress $ipAddress = null,
        ?MyUser $impersonatedBy = null,
        ?array $data = null,
    ) {
        $this->id = Uuid::v7();
        $this->action = $action;
        $this->source = $source;
        $this->user = $user;
        // Individual columns when using Doctrine ORM.
        $this->subjectClass = $subject?->class;
        $this->subjectIdentifier = $subject?->identifier;
        $this->ipAddress = $ipAddress?->address;
        $this->impersonatedBy = $impersonatedBy;
        $this->data = $data ?? [];
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function fromAuditLog(AuditLogInterface $auditLog): self
    {
        $token = $auditLog->getContext()->token;
        return new self(
            get_class($auditLog),
            $auditLog->getContext()->source,
            $token?->getUser(),
            $auditLog->getSubject(),
            $auditLog->getContext()->ip,
            $token instanceof SwitchUserToken
                ? $token->getOriginalToken()->getUser()
                : null,
            $auditLog->getData(),
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getImpersonatedBy(): ?UserInterface
    {
        return $this->impersonatedBy;
    }
}
```

</details>

<details>
<summary>Using MongoDB ODM</summary>

````php
<?php declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use WeDevelop\Audit\AuditLogInterface;
use WeDevelop\Audit\Entity\AbstractAuditEntity;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\ValueObject\IpAddress;

// AbstractAuditEntity can be used for both ORM Entities and Mongo ODM Documents.
#[ODM\Document(collection: 'AuditLogs', readOnly: true)]
class AuditLog extends AbstractAuditEntity
{
    #[ODM\Id]
    private ?string $id;

    #[ODM\ReferenceOne(storeAs: ClassMetadata::REFERENCE_STORE_AS_ID, targetDocument: MyUser::class)]
    private ?MyUser $user;

    #[ODM\ReferenceOne(storeAs: ClassMetadata::REFERENCE_STORE_AS_ID, targetDocument: MyUser::class)]
    private ?MyUser $impersonatedBy

    /**
     * @param class-string<AuditLogInterface> $action
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        string $action,
        AuditSource $source,
        ?MyUser $user,
        ?Subject $subject = null,
        ?IpAddress $ipAddress = null,
        ?MyUser $impersonatedBy = null,
        ?array $data = null,
    ) {
        $this->id = Uuid::v7();
        $this->action = $action;
        $this->source = $source;
        $this->user = $user;
        // Embeddable object when using Mongo ODM.
        $this->subject = $subject;
        $this->ipAddress = $ipAddress?->address;
        $this->impersonatedBy = $impersonatedBy;
        $this->data = $data ?? [];
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function fromAuditLog(AuditLogInterface $auditLog): self
    {
        $token = $auditLog->getContext()->token;
        return new self(
            get_class($auditLog),
            $auditLog->getContext()->source,
            $token?->getUser(),
            $auditLog->getSubject(),
            $auditLog->getContext()->ip,
            $token instanceof SwitchUserToken
                ? $token->getOriginalToken()->getUser()
                : null,
            $auditLog->getData(),
        );
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getImpersonatedBy(): ?UserInterface
    {
        return $this->impersonatedBy;
    }
}
````

</details>

### Configure the Presenter Service

<details>
<summary>config/services.yaml</summary>

```yaml
services:
    # ...

    # If using Doctrine ORM:
    WeDevelop\Audit\Presenter:
        arguments:
            $entityClass: 'App\Entity\AuditLog'

    # If using Doctrine Mongo ODM:
    WeDevelop\Audit\Presenter:
        arguments:
            $entityClass: 'App\Document\AuditLog'
```

</details>

<details>
<summary>config/services.php</summary>

```php
<?php declare(strict_types=1);

use App\Service\SiteUpdateManager;

return function (ContainerConfigurator $container): void {
    // ...

    if ($usingDoctrineORM) {
        $services
            ->set(\WeDevelop\Audit\Presenter::class)
            ->arg('$entityClass', \App\Entity\AuditLog::class);
    }

    if ($usingDoctrineMongoODM) {
        $services
            ->set(\WeDevelop\Audit\Presenter::class)
            ->arg('$entityClass', \App\Document\AuditLog::class);
    }

};
```

</details>

### Start Writing Audit Classes

<details>

<summary>src/Audit/User/EnableMfa.php</summary>

```php
<?php declare(strict_types=1);

namespace App\Audit\User;

use Symfony\Component\Security\Core\User\UserInterface;
use WeDevelop\Audit\AbstractAuditLog;
use WeDevelop\Audit\Entity\AuditEntityInterface;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\ValueObject\Context;

final readonly class EnableMfa extends AbstractAuditLog
{
    /** @param 'totp'|'hotp'|'sms'|'email'|'push' $method */
    public static function create(
        Context $context,
        MyUserEntityOrDocument $updatedUser,
        string $method,
    ): self {
        return new self($context, Subject::fromObject($updatedUser), new \DateTimeImmutable, [
            'method' => $method,
        ]);
    }

    public function createEntity(): AuditEntityInterface
    {
        return AuditLogEntityOrDocument::fromAuditLog($this);
    }

    public function getMessage(): string
    {
        return 'user.mfa.enabled';
    }

    public function getParameters(): array
    {
        return [];
    }

    public function getAdditionalInfo(): iterable
    {
        return [
            $this->data['method'] => [],
        ];
    }
}
```

</details>

### Add Translations

You must add translations for the `audit` domain, with three main keys:
`source`, `action` and `extra`.

<details>
<summary>translations/audit.en.yaml</summary>

```yaml
source:
    console: 'Console Terminal'
    ui: 'Web (User: %user%)'
    api: 'API'
    webhook: 'Webhook'
    job: 'Background Worker'
    unknown: 'Unknown'
action:
    # Example main translation for App\Audit\User\EnableMfa audit class
    user:
        mfa:
            enabled: 'Multi-factor authentication was enabled for the user account "%user%"'
extra:
    # Example "additional info" translations for App\Audit\User\EnableMfa audit class
    user:
        mfa:
            enabled:
                totp: 'MFA will require a time-based single-use passcode'
                hotp: 'MFA will require a HMAC-based single-use passcode'
                sms: 'MFA will require a single-use passcode delivered via SMS'
                email: 'MFA will require clicking a single-use link delivered via email'
                push: 'MFA will require acknowledgement via a push notification from the mobile app'
```

</details>

## Start Logging

<details>
<summary>Using ORM</summary>

```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\Audit\User\EnableMfa;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use WeDevelop\Audit\ValueObject\Context;

class UserMfaService
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $em,
    ) {}

    public function enableMfa(Request $request, MyUserEntity $userToUpdate, string $totpSecret): void
    {
        $userToUpdate->setTotpSecret($totpSecret);
        $audit = EnableMfa::create(
            Context::ui($request, $this->tokenStorage->getToken()),
            $userToUpdate,
            'totp',
        );

        $this->em->persist($userToUpdate);
        $this->em->persist($audit->createEntity());
        $this->em->flush();
    }
}
```

</details>

<details>
<summary>Using MongoDB ODM</summary>

```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\Audit\User\EnableMfa;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use WeDevelop\Audit\ValueObject\Context;

class UserMfaService
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private DocumentManager $dm,
    ) {}

    public function enableMfa(Request $request, MyUserDocument $userToUpdate, string $totpSecret): void
    {
        $userToUpdate->setTotpSecret($totpSecret);
        $audit = EnableMfa::create(
            Context::ui($request, $this->tokenStorage->getToken()),
            $userToUpdate,
            'totp',
        );

        $this->dm->persist($userToUpdate);
        $this->dm->persist($audit->createEntity());
        $this->dm->flush();
    }
}
```

</details>
