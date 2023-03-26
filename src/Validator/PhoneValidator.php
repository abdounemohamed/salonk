<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PhoneValidator extends ConstraintValidator
{
    /**
     * @param $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }
        $pattern = "/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/";
        if (preg_match($pattern, $value) == 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

    }
}
