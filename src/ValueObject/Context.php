<?php declare(strict_types=1);

namespace WeDevelop\Audit\ValueObject;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeDevelop\Audit\Entity\AuditEntityInterface;
use WeDevelop\Audit\Enum\AuditSource;
use WeDevelop\Audit\RenderAuditLogInterface;
use WeDevelop\Audit\Security\AuditImpersonationToken;
use WeDevelop\Audit\Security\AuditToken;
use WeDevelop\Audit\Util\TranslationHelper;

readonly class Context implements TranslatableInterface
{
    final protected function __construct(
        public AuditSource $source,
        public ?TokenInterface $token = null,
        public ?IpAddress $ip = null,
    ) {}

    public static function fromEntity(AuditEntityInterface $entity): self
    {
        return new self(
            $entity->getSource(),
            self::constructToken($entity->getUser(), $entity->getImpersonatedBy()),
            IpAddress::fromEntity($entity),
        );
    }

    private static function constructToken(
        ?UserInterface $user,
        ?UserInterface $impersonatedBy,
        ?string $firewallName = null,
    ): ?TokenInterface
    {
        if (null !== $user && null !== $impersonatedBy) {
            return new AuditImpersonationToken($user, new AuditToken($impersonatedBy), $firewallName);
        } elseif (null !== $user) {
            return new AuditToken($user, $firewallName);
        }
        return null;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(TranslationHelper::inNamespace(
            $this->source->value,
            RenderAuditLogInterface::TRANSLATION_NAMESPACE_CONTEXT,
        ), [], RenderAuditLogInterface::TRANSLATION_DOMAIN, $locale);
    }

    public static function console(): self
    {
        return new self(AuditSource::CONSOLE, null, null);
    }

    public static function ui(Request $request, ?TokenInterface $token): self
    {
        return new self(AuditSource::UI, $token, IpAddress::fromRequest($request));
    }

    public static function api(Request $request, ?TokenInterface $token): self
    {
        return new self(AuditSource::API, $token, IpAddress::fromRequest($request));
    }

    /** Webhooks are generally unauthenticated, but custom authentication could be set up. */
    public static function webhook(Request $request, ?TokenInterface $token = null): self
    {
        return new self(AuditSource::WEBHOOK, $token, IpAddress::fromRequest($request));
    }

    public static function job(): self
    {
        return new self(AuditSource::JOB, null, null);
    }

    public static function unknown(): self
    {
        throw new \RuntimeException(
            sprintf(
                'Unknown Audit Context; add new options to both %s and %s',
                AuditSource::class,
                static::class,
            ),
        );
    }

    /**
     * Sometimes, when using Mongo ODM or the token is unserialized from session
     * storage, the token's user objects are detached from Doctrine's Unit of
     * Work. Replace the objects that reference the original data in the database,
     * without making a database call to fetch them. They'll act as proxy objects
     * that will load as soon as you access any of their properties.
     */
    public function refDb(DocumentManager|EntityManagerInterface $manager): self
    {
        $getReference = function (?object $document) use ($manager): ?object {
            if (null === $document || $manager->contains($document)) {
                return $document;
            }
            $metadata = $manager->getClassMetadata(get_class($document));
            $identifier = $manager instanceof EntityManagerInterface
                ? $metadata->getIdentifierValues($document)
                // @phpstan-ignore-next-line method.notFound (getIdentifierValues really does exist)
                : $metadata->getIdentifierValue($document);
            return $manager->getReference($metadata->getName(), $identifier);
        };

        /** @var array{user: ?UserInterface, impersonatedBy: ?UserInterface} $tokens */
        $tokens = array_map(fn (?UserInterface $user): ?object => $getReference($user), [
            'user' => $this->token?->getUser(),
            'impersonatedBy' => $this->token instanceof SwitchUserToken
                ? $this->token->getOriginalToken()->getUser()
                : null,
        ]);

        // Modifying readonly properties on clone isn't supported until
        // PHP 8.3, construct new object.
        return new self(
            $this->source,
            self::constructToken(
                $tokens['user'],
                $tokens['impersonatedBy'],
                // TokenInterface::getFirewallName() does not exist, but does
                // on the concrete implementations we extend.
                $this->token !== null && method_exists($this->token, 'getFirewallName')
                    ? $this->token->getFirewallName()
                    : null,
            ),
            $this->ip,
        );
    }
}
