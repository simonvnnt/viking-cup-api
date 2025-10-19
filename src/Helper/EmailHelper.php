<?php

namespace App\Helper;

use App\Business\EmailBusiness;
use App\Dto\EmailDto;
use App\Entity\Round;
use Twig\Environment;

readonly class EmailHelper
{
    public function __construct(
        private EmailBusiness $emailBusiness,
        private ConfigHelper $config,
        private Environment $environment
    )
    {}

    public function sendPreselectedEmail(string $email, Round $round, string $firstName): void
    {
        $subject = $this->config->getValue('EMAIL_SUBJECT_PRESELECTED');
        $subject = $this->environment->createTemplate($subject)->render(["roundName" => $round->getName()]);

        $template = $this->config->getValue('EMAIL_TEMPLATE_PRESELECTED');
        $template = $this->environment->createTemplate($template)->render(["firstName" => $firstName]);


        $emailDto = new EmailDto(
            fromName: 'Viking Cup',
            to: $email,
            subject: $subject,
            message: $template,
            attachment: 'https://viking-cup.fr/viking-cup-reglement-media.pdf'
        );

        $this->emailBusiness->sendEmail($emailDto);
    }

    public function sendSelectedEmail(string $email, Round $round, string $firstName): void
    {
        $subject = $this->config->getValue('EMAIL_SUBJECT_SELECTED');
        $subject = $this->environment->createTemplate($subject)->render(["roundName" => $round->getName()]);

        $template = $this->config->getValue('EMAIL_TEMPLATE_SELECTED');
        $template = $this->environment->createTemplate($template)->render(["firstName" => $firstName]);

        $emailDto = new EmailDto(
            fromName: 'Viking Cup',
            to: $email,
            subject: $subject,
            message: $template,
            attachment: 'https://viking-cup.fr/viking-cup-reglement-media.pdf'
        );

        $this->emailBusiness->sendEmail($emailDto);
    }

    public function sendELearningEmail(string $email, Round $round, string $firstName, string $uniqueId): void
    {
        $mediaUrl = $this->config->getValue('MEDIA_URL');

        $subject = $this->config->getValue('EMAIL_SUBJECT_ELEARNING');
        $subject = $this->environment->createTemplate($subject)->render(["roundName" => $round->getName()]);

        $template = $this->config->getValue('EMAIL_TEMPLATE_ELEARNING');
        $template = $this->environment->createTemplate($template)->render(["firstName" => $firstName, "mediaUrl" => $mediaUrl, "uniqueId" => $uniqueId]);

        $emailDto = new EmailDto(
            fromName: 'Viking Cup',
            to: $email,
            subject: $subject,
            message: $template
        );

        $this->emailBusiness->sendEmail($emailDto);
    }
}