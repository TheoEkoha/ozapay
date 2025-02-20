<?php

namespace App\Email;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

class BrevoEmail extends Email
{
    public function __construct()
    {
        parent::__construct();
        $this->text(' '); // set a default text body to avoid errors
    }

    public function setTemplateId(string $templateId): self
    {
        $this->getHeaders()->addTextHeader('X-Template-Id', $templateId);
        return $this;
    }

    public function setTemplateVars(array $vars): self
    {
        $this->getHeaders()->addTextHeader('params', json_encode($vars));
        return $this;
    }
}
