# WeDevelop Audit Scaffold

**A boilerplate/scaffolding library for audit logs in your Symfony
application, persistable to your database layer.**

### Motivation

Audit logging is the process of documenting _activity_ within your application,
with additional context such as: the responsible user/actor, the entry point to
the application, the subject that was acted upon, and any other relevant domain
logic for that activity.
A series of audit logs is called an audit trail because it shows a sequential,
chronological record of activity in your application.

Audit logs answer a simple question: who did what, to what, when and where?

> If your project is more interested in a historical log of how your data
> changed, rather than actions or activity, perhaps try
> [Loggable behaviour][de-loggable] for your entities and/or documents by
> [Doctrine Extensions][de].

## Getting Started

#### Requirements

- Symfony 7
- Doctrine persistence layer: either [ORM][orm]
  or [Mongo ODM][mongo-odm].

#### Installation

This project is installed through Composer:

```shell
composer require "wedevelopnl/audit-scaffold"
```

### Limitations

This library does as much for you as it can, without knowing any of your
application's domain or implementation logic.

> This library could hook itself into Symfony's bundle system but any
> modifications it would make within Symfony's configuration and container would
> purely be assumptions about how your application may or may not work.

While this library provides the base entity/document to extend from, and various
DTOs, Enums and Value Objects, your application must implement the following:

- A single **Entity or Document** (including references to your user
  implementation, metadata such as table/collection name and indexes, and
  migration if applicable) implementing `AuditEntityInterface`.
- Service **container configuration**.
- Doctrine **mapping configuration**.
- **Translations** for whatever languages your application supports.
- and, of course, **audit classes** (that implement `AuditLogInterface`) for the
  things you want to audit in your application.

It is recommended that you extend from
[`AbstractAuditEntity`](src/Entity/AbstractAuditEntity.php) and
[`AbstractAuditLog`](src/AbstractAuditLog.php), which are provided for your
convenience.

### Example Usage

For a thorough explanation of the setup required to use this library with either
Doctrine ORM or Mongo ODM, please refer to the [tutorial](docs/tutorial.md).

<details>
<summary>Example Audit Log</summary>

```php
<?php declare(strict_types=1);

namespace App\Audit\Account;

use App\Entity\AuditLog as AuditLogEntity;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use WeDevelop\Audit\AbstractAuditLog;
use WeDevelop\Audit\Entity\AuditEntityInterface;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\ValueObject\Context;

final readonly class UserBanned extends AbstractAuditLog
{
    public static function create(
        Context $context,
        User $bannedUser,
        ?\DateTimeInterface $bannedUntil = null,
    ): self {
        return new self($context, Subject::fromObject($bannedUser), new \DateTimeImmutable, [
            'bannedUntil' => $bannedUntil?->format(\DateTimeInterface::RFC3339),
        ]);
    }

    public function createEntity(): AuditEntityInterface
    {
        return AuditLogEntity::fromAuditLog($this);
    }

    public function getMessage(): string
    {
        return 'user.banned';
    }

    public function getParameters(): array
    {
        return [];
    }

    public function getAdditionalInfo(): iterable
    {
        if (is_string($this->data['bannedUntil'] ?? null)) {
            yield 'until' => ['datetime' => $this->data['bannedUntil']];
        } else {
            yield 'indefinitely' => [];
        }
    }
}
```

</details>

<details>
<summary>Example Audit Logging</summary>

```php
<?php declare(strict_types=1);

use App\Audit\Account\UserBanned;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use WeDevelop\Audit\ValueObject\Context;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    #[Route('/users/{user}/ban', name: 'admin_user_ban')]
    public function banUserAction(Request $request, User $user): Response
    {
        $form = $this->createForm(BanConfirmationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setActive(false);

            $auditLog = UserBanned::create(
                Context::ui($request, $this->tokenStorage->getToken()),
                $user,
            );

            $this->em->persist($user);
            $this->em->persist($auditLog->createEntity());
            $this->em->flush();

            return $this->redirectToRoute('admin_user_view', ['user' => $user->getId()]);
        }

        return $this->render('admin/users/confirm-ban.twig.html', [
            'form' => $form,
        ]);
    }
}
```

</details>

## Meta

### Code of Conduct

This project includes and adheres to the [Contributor Covenant as a Code of
Conduct](CODE_OF_CONDUCT.md).

### License

Please see the [separate license file](LICENSE.md) included in this repository
for a full copy of the MIT license, which this project is licensed under.

[de-loggable]: https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/loggable.md "Loggable behavioral extension for Doctrine"
[de]: https://github.com/doctrine-extensions/DoctrineExtensions "Doctrine Extensions on GitHub"
[mongo-odm]: https://packagist.org/packages/doctrine/mongodb-odm "Mongo ODM on Packagist"
[orm]: https://packagist.org/packages/doctrine/orm "Doctrine ORM on Packagist"
