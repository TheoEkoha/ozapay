<?php

namespace App\ApiResource\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;

class CustomSearchUserFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {

        if ($property !== 'search') {
            return;
        }

        // Clean the value by removing any URL parameters
        $cleanValue = explode('?', $value)[0];

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('search');

        $queryBuilder
            ->andWhere(sprintf(
                'LOWER(%s.firstName) LIKE :%s OR 
                        LOWER(%s.lastName) LIKE :%s OR 
                        LOWER(%s.email) LIKE :%s OR 
                        LOWER(%s.phone) LIKE :%s',
                $alias,
                $parameterName,
                $alias,
                $parameterName,
                $alias,
                $parameterName,
                $alias,
                $parameterName
            ))
            ->setParameter($parameterName, '%' . strtolower($cleanValue) . '%');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Search users by name, email, or phone number',
                    'name' => 'Search',
                    'type' => 'string',
                ],
            ],
        ];
    }
}
