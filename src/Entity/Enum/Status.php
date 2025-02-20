<?php

namespace App\Entity\Enum;

enum Status: string
{
    case Deleted = "DELETED";
    case Pending = "PENDING";
    case Published = "PUBLISHED";
    case Closed = "CLOSED";
}
