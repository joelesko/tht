<?php

namespace o;

require_once(Tht::getCoreVendorPath('php/Smtp.php'));

class u_Email extends OStdModule {

    private static $DEFAULT_PORT = 587;
    private $lastLogs = [];

    private function initParams($params) {

        $params = $this->initParam($params, 'from', null);
        $params = $this->initParam($params, 'fromName', '');
        $params = $this->initParam($params, 'to', null);
        $params = $this->initParam($params, 'subject', null);
        $params = $this->initParam($params, 'body', null);

        $params = $this->initParam($params, 'replyTo', '');
        $params = $this->initParam($params, 'cc', []);
        $params = $this->initParam($params, 'bcc', []);
        $params = $this->initParam($params, 'attachments', []);

        return $params;
    }

    private function initParam($params, $key, $default) {

        if (!isset($params[$key])) {
            if (is_null($default)) {
                $this->error("Missing Email param `$key`");
            } else {
                $params[$key] = $default;
            }
        }

        if (is_array($default) && !is_array($params[$key])) {
            $params[$key] = [$params[$key]];
        }

        return $params;
    }

    public function u_send ($params) {

        $this->ARGS('m', func_get_args());

        $params = $this->initParams($params);

        if (!$this->u_validate_email($params['from'])) {
            $this->error('Invalid `from` email address: `' . $params['from'] . '`');
        }

        if (!$this->u_validate_email($params['to'])) {
            return false;
        }

        $auth = $this->getAuth();

        $mailer = new \Snipworks\Smtp\Email ($auth['host'], $auth['port']);

        $mailer
            ->setProtocol(\Snipworks\Smtp\Email::TLS)
            ->setLogin($auth['user'], $auth['pass']);

        $mailer
            ->setFrom($params['from'], $params['fromName'] ? $params['fromName'] : null)
            ->addTo($params['to'])
            ->setSubject($params['subject']);

        $body = $params['body'];
        if (OTypeString::isa($body)) {
            $mailer->setHtmlMessage(v($body)->u_render_string());
        }
        else {
            $mailer->setTextMessage($body);
        }

        if ($params['replyTo']) {
            if (!$this->u_validate_email($params['replyTo'])) {
                $this->error('Invalid `replyTo` email address: `' . $params['replyTo'] . '`');
            }
            $mailer->addReplyTo($params['replyTo']);
        }

        foreach ($params['cc'] as $cc) {
            if (!$this->u_validate_email($cc)) { continue; }
            $mailer->addCc($cc);
        }

        foreach ($params['bcc'] as $bcc) {
            if (!$this->u_validate_email($bcc)) { continue; }
            $mailer->addCc($bcc);
        }

        // UNDOCUMENTED
        // TODO: validate
        foreach ($params['attachments'] as $att) {
            $mailer->addAttachment($att);
        }

        try {
            $status = $mailer->send();
            $this->lastLogs = $mailer->getLogs();
         } catch (Exception $e) {
            $this->lastLogs = $mailer->getLogs();
            if (Security::isDev()) {
                $this->error('Error sending email: ' . $e->getMessage());
            }
            else {
                return false;
            }
         }

         $responseData = $mailer->getLogs();
         if ($responseData) {
            foreach ($responseData as $d) {
                if (preg_match('/^5\d\d /', $d)) {
                    if (Security::isDev()) {
                        $this->error('Email Error: ' . $d);
                    }
                    else {
                        return false;
                    }
                }
            }
         }

        return $status;
    }

    public function u_get_last_logs() {

        $this->ARGS('', func_get_args());
        return v($this->lastLogs);
    }

    private function getAuth() {

        $config = $this->u_get_email_config();

        $config->u_validate([
            'host' => 's',
            'port' => ['u', self::$DEFAULT_PORT],
            'user' => 's',
            'password' => 's',
        ], 'Email config');

        return [
            'host'   => $config['host'],
            'port'   => $config['port'],
            'user'   => $config['user'],
            'pass'   => $config['password'],
        ];
    }

    function u_get_email_config() {
        $this->ARGS('s', func_get_args());
        return OMap::create(Tht::getTopConfig('email'));
    }

    public function u_validate_email($email) {

        $this->ARGS('s', func_get_args());

        $val = Tht::module('Input')->u_validate('test', $email, 'email');

        return $val['value'];
    }

}
