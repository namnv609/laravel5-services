<?php

namespace App\Services;

use View;
use Log;
use SendGrid;
use SendGrid\Email as SendGridEmail;
use SendGrid\Exception as SendGridException;

class SendGridService
{
    /**
     * @var $sendGrid
     */
    private $sendGrid;

    /**
     * @var $sendGridEmail
     */
    private $sendGridEmail;

    /**
     * @var $to
     */
    private $to;

    /**
     * @var $to
     */
    private $from;

    /**
     * @var $to
     */
    private $fromName;

    /**
     * @var $subject
     */
    private $subject;

    /**
     * @var $cc
     */
    private $cc;

    /**
     * @var $bcc
     */
    private $bcc;

    /**
     * @var $replyTo
     */
    private $replyTo;

    /**
     * @var $data
     */
    private $data;

    /**
     * @var $layout
     */
    private $layout;

    /**
     * @var $isHtml
     */
    private $isHtml;

    /**
     * @var $content
     */
    private $content;

    public function __construct()
    {
        $this->sendGrid = new SendGrid(env('SENDGRID_API_KEY'), [
            'raise_exceptions' => env('SENDGRID_EXCEPTION', true)
        ]);

        $this->sendGridEmail = new SendGridEmail();

        $this->to           = [];
        $this->from         = env('MAIL_FROM');
        $this->fromName     = env('MAIL_NAME');
        $this->cc           = [];
        $this->bcc          = [];
        $this->data         = [];
        $this->isHtml       = true;
        $this->content      = '';
    }

    /**
     * Set to email address
     *
     * @var mixed $to String or array email address
     * @return App\Services\SendGridService
     */
    public function setTo($to)
    {
        if (is_array($to)) {
            $this->to = array_merge($to, $this->to);
        } else {
            $this->to[] = $to;
        }

        return $this;
    }

    /**
     * Get email to address
     *
     * @return array Email address
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get email send from
     *
     * @return string Email address
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get Email from name
     *
     * @return string From name
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * Set CC
     *
     * @var mixed $cc String or array email address
     * @return App\Services\SendGridService
     */

    public function setCC($cc)
    {
        if (is_array($cc)) {
            $this->cc = array_merge($cc, $this->cc);
        } else {
            $this->cc[] = $cc;
        }

        return $this;
    }

    /**
     * Get CC
     *
     * @return array CC email address
     */
    public function getCC()
    {
        return $this->cc;
    }

    /**
     * Set BCC
     *
     * @param mixed $bcc String or array email address
     * @return App\Services\SendGridService
     */
    public function setBCC($bcc)
    {
        if (is_array($bcc)) {
            $this->bcc = array_merge($bcc, $this->bcc);
        } else {
            $this->bcc[] = $bcc;
        }

        return $this;
    }

    /**
     * Get BCC
     *
     * @return array BCC email address
     */
    public function getBCC()
    {
        return $this->bcc;
    }

    /**
     * Set reply to
     *
     * @param string $replyTo Reply to email address
     * @return App\Services\SendGridService
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * Get reply to address
     *
     * @return string Reply to email address
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Set email subject
     *
     * @param string $subject Email subject
     * @return App\Services\SendGridService
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get email subject
     *
     * @return string Email subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set email layout variable data
     *
     * @var array $data Email data
     * @return App\Services\SendGridService
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get email data
     *
     * @return array Email layout variable data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set email layout (template)
     *
     * @param string $layoutPath Path to email layout
     * @return App\Services\SendGridService
     */
    public function setLayout($layoutPath)
    {
        $this->layout = $layoutPath;

        return $this;
    }

    /**
     * Get email template layout
     *
     * @return string Path to email template layout
     */
    public function getLayout()
    {
        return $this->layout();
    }

    /**
     * Set email type (HTML or Text only)
     *
     * @param bool $isHtml True or false
     * @return App\Services\SendGridService
     */
    public function setIsHtml($isHtml)
    {
        $this->isHtml = $isHtml;

        return $this;
    }

    /**
     * Get email type
     *
     * @return bool Email type is HTML?
     */
    public function getIsHtml()
    {
        return $this->isHtml;
    }

    /**
     * Set email content (for email type isHtml is false)
     *
     * @param string Email content
     * @return App\Services\SendGridService
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get email content (for both HTML and Text)
     *
     * @return string Email content (HTML or Text)
     */
    public function getContent()
    {
        $content = $this->content;

        if ($this->isHtml) {
            $content = View::make($this->layout, $this->data)->render();
        }

        return $content;
    }

    /**
     * Send email
     *
     * @return bool Send email status
     */
    public function send()
    {
        $content = $this->getContent();

        $this->sendGridEmail->setTos($this->to)
                            ->setFrom($this->from)
                            ->setFromName($this->fromName)
                            ->setSubject($this->subject);
        if ($this->isHtml) {
            $this->sendGridEmail->setHtml($content);
        } else {
            $this->sendGridEmail->setText($content);
        }

        if (count($this->cc)) {
            $this->sendGridEmail->setCcs($this->cc);
        }

        if (count($this->bcc)) {
            $this->sendGridEmail->setBccs($this->bcc);
        }

        if ($this->replyTo) {
            $this->sendGridEmail->setReplyTo($this->replyTo);
        }

        try {
            $this->sendGrid->send($this->sendGridEmail);

            return true;
        } catch (SendGridException $e) {
            Log::error('SendGrid error code: ' . $e->getCode());
            Log::error($e->getErrors());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return false;
    }
}
