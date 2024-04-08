<?php declare(strict_types=1);

namespace WeDevelop\Audit;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use WeDevelop\Audit\Entity\AuditEntityInterface;
use WeDevelop\Audit\Entity\Subject;
use WeDevelop\Audit\Util\TranslationHelper;
use WeDevelop\Audit\ValueObject\Context;

abstract readonly class AbstractAuditLog implements AuditLogInterface, RenderAuditLogInterface
{
    /**
     * The constructor is kept private for outside use on purpose.
     * Use a static, named constructor on your Audit Log classes to take whatever
     * domain-specific argument you need to construct your additional data (the
     * subject is an implementation detail and should be encoded into a Subject
     * object inside the named controller instead of left to the end-user).
     * Additional data *MUST* be JSON-serializable in order to be stored in the
     * database.
     *
     * As good practice, it is recommended you use something like:
     *   public static function create(Context $c, Project $subject, ...): self {}
     *
     * @param array<string, mixed> $data
     */
    final protected function __construct(
        protected Context $context,
        protected ?Subject $subject,
        protected \DateTimeImmutable $loggedAt,
        protected ?array $data,
    ) {}

    public static function fromEntity(AuditEntityInterface $entity): AuditLogInterface
    {
        return new static(
            Context::fromEntity($entity),
            $entity->getSubject(),
            $entity->getCreatedAt(),
            $entity->getData(),
        );
    }

    public function getLoggedAt(): \DateTimeImmutable
    {
        return $this->loggedAt;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $message = TranslationHelper::inNamespace($this->getMessage(), self::TRANSLATION_NAMESPACE_ACTION);
        $parameters = TranslationHelper::prepareTranslationParameters($this->getParameters());
        return $translator->trans($message, $parameters, self::TRANSLATION_DOMAIN, $locale);
    }

    /**
     * Return a list of additional translatable message identifiers that, when
     * translated, provide additional pieces of information that compliment the
     * main audit message.
     *
     * If the iteration value is a string, it is returned as-is. Otherwise, it
     * should be an array of translation parameters to be passed when
     * translating the key.
     *
     * The iteration key is combined with a namespace defined in
     * RenderAuditLogInterface::TRANSLATION_NAMESPACE_EXTRA, then with the audit
     * log message as an additional namespace. For example, given that an audit
     * log class defines the following additional info:
     *
     * ```php
     * return ['button' => ['name' => 'Red']];
     * ```
     *
     * Then the following is what gets psuedo-rendered in Twig:
     *
     * ```twig
     * {% set namespace = RenderAuditLogInterface::TRANSLATION_NAMESPACE_EXTRA ~ '.' ~ log.getMessage() ~ '.' %}
     * {% set key = namespace ~ 'button' %}
     * {% set params = {name: 'Red'} %}
     * {{ key|trans(params) }}
     * ```
     *
     * @return iterable<string, string|array<string, string>>
     */
    abstract protected function defineAdditionalInfo(): iterable;

    /** @return iterable<string|TranslatableMessage> */
    public function getInfo(): iterable
    {
        foreach ($this->defineAdditionalInfo() as $translationKey => $translationParameters) {
            if (is_string($translationParameters)) {
                yield $translationParameters;
            } elseif (is_string($translationKey) && is_array($translationParameters)) {
                yield new TranslatableMessage(
                    TranslationHelper::inNamespace($translationKey, [self::TRANSLATION_NAMESPACE_EXTRA, $this->getMessage()]),
                    TranslationHelper::prepareTranslationParameters($translationParameters),
                    self::TRANSLATION_DOMAIN,
                );
            }
        }
    }
}
