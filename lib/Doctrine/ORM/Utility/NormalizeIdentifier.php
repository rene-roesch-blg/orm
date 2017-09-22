<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;

final class NormalizeIdentifier
{
    public function __invoke(
        EntityManagerInterface $entityManager,
        ClassMetadata $targetClass,
        array $flatIdentifier
    ) : array {
        $normalizedAssociatedId = [];

        foreach ($targetClass->getDeclaredPropertiesIterator() as $name => $declaredProperty) {
            if (! \array_key_exists($name, $flatIdentifier)) {
                continue;
            }

            if ($declaredProperty instanceof FieldMetadata) {
                $normalizedAssociatedId[$name] = $flatIdentifier[$name];

                continue;
            }

            if ($declaredProperty instanceof ToOneAssociationMetadata) {
                $targetIdMetadata = $entityManager->getClassMetadata($declaredProperty->getTargetEntity());

                // Note: the ORM prevents using an entity with a composite identifier as an identifier association
                //       therefore, reset($targetIdMetadata->identifier) is always correct
                $normalizedNested = $this->__invoke(
                    $entityManager,
                    $targetIdMetadata,
                    [reset($targetIdMetadata->identifier) => $flatIdentifier[$name]]
                );
                $reference =  $entityManager->getReference(
                    $targetIdMetadata->getClassName(),
                    $normalizedNested
                );
                $normalizedAssociatedId[$name] = $reference;
            }
        }

        return $normalizedAssociatedId;
    }
}
