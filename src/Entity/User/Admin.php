<?php

namespace App\Entity\User;

use App\Common\Constants\UserConstants;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Admin extends User
{
    public function __construct()
    {
        $this->setRoles([UserConstants::ROLE_ADMIN]);
    }

}
