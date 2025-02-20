<?php

namespace App\Entity\User;

use App\Common\Constants\UserConstants;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;

#[Entity]
class Particular extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->setRoles([UserConstants::ROLE_USER]);
    }

}
