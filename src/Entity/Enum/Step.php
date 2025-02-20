<?php

namespace App\Entity\Enum;

enum Step: string
{
    case Void = "";
    case Info = "info";
    case Phone = "phone";
    case SmsCode = "smsCode";
    case Email = "email";
    case EmailCode = "emailCode";
    case Pin = "pin";
}
