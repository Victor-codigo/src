<?php

declare(strict_types=1);

namespace Common\Adapter\Validation\Constraints\AlphanumericWithWhiteSpace;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AlphanumericWithWhiteSpaceValidator extends RegexValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AlphanumericWithWhitespace) {
            throw new UnexpectedTypeException($constraint, Alphanumeric::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if ($constraint->match xor preg_match($constraint->pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(AlphanumericWithWhitespace::ALPHANUMERIC_WITH_WHITESPACE_FAILED_ERROR)
                ->addViolation();
        }
    }
}
