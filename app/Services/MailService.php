<?php

namespace App\Services;

use Mail;
use View;
use Log;

class MailService
{
    /**
     * @var $to array
     */
    private $to;

    /**
     * @var $data array
     */
    private $data;

    /**
     * @var $layout string
     */
    private $layout;

    /**
     * @var $subject string
     */
    private $subject;

    /**
     * @var $cc array
     */
    private $cc;

    /**
     * @var $bcc array
     */
    private $bcc;

    /**
     * @var replyTo array
     */
    private $replyTo;

    public function __construct()
    {
        $this->data = [];
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->replyTo = [];
    }

    /**
     * Set send to email address
     *
     * @param string $to Email address
     * @return \App\Services\MailService
     */
    public function setTo($to)
    {
        $this->to[] = $to;

        return $this;
    }

    /**
     * Get send to email address
     *
     * @return array List to email address
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set data parse to email layout
     *
     * @param array $data Data array
     * @return \App\Services\MailService
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get view data
     *
     * @return array View data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set email layout
     *
     * @param $layout string Path to email layout
     * @return \App\Services\MailService
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get email layout
     *
     * @return string Path to email layout
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set email subject
     *
     * @param $subject string Email subject
     * @return \App\Services\MailService
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
     * Set CC
     *
     * @param string $cc CC address
     * @return \App\Services\MailService
     */
    public function setCC($cc)
    {
        $this->cc[] = $cc;

        return $this;
    }

    /**
     * Get list CC email
     *
     * @return array List CC email
     */
    public function getCC()
    {
        return $this->cc;
    }

    /**
     * Set BCC
     *
     * @param string $bcc BCC address
     * @return \App\Services\MailService
     */
    public function setBCC($bcc)
    {
        $this->bcc[] = $bcc;

        return $this;
    }

    /**
     * Get BCC
     *
     * @return array BCC address
     */
    public function getBCC()
    {
        return $this->bcc;
    }

    /**
     * Set reply to email
     *
     * @param string $replyTo Email address
     * @return \App\Services\MailService
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo[] = $replyTo;

        return $this;
    }

    /**
     * Get reply to
     *
     * @return array Email reply to
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Send email
     *
     * @return bool Send status
     */
    public function send()
    {
        try {
            Mail::send($this->layout, $this->data, function($message) {
                if (count($this->cc)) {
                    $message->cc($this->cc);
                }

                if (count($this->bcc)) {
                    $message->bcc($this->bcc);
                }

                if (count($this->replyTo)) {
                    $message->replyTo($this->replyTo);
                }

                $message->to($this->to)->subject($this->subject);
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }

        return true;
    }
}
