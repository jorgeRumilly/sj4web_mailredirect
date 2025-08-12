<?php
/**
 * 2025 SJ4WEB.FR
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@sj4web.fr so we can send you a copy immediately.
 *
 * @author    SJ4WEB.FR
 * @copyright 2025 SJ4WEB.FR
 * @license   https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */


if (!defined('_PS_VERSION_')) exit;

class Sj4web_MailRedirect extends Module
{
    public function __construct()
    {
        $this->name = 'sj4web_mailredirect';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'SJ4WEB.FR';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('SJ4WEB - Mail redirect', [], 'Modules.Sj4webmailredirect.Admin');
        $this->description = $this->trans('Redirect all outgoing emails to a single address (staging/dev).', [], 'Modules.Sj4webmailredirect.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionEmailSendBefore')
            && Configuration::updateValue('SJ4WEB_MAIL_REDIRECT_ENABLED', 1)
            && Configuration::updateValue('SJ4WEB_MAIL_REDIRECT_TO', Configuration::get('PS_SHOP_EMAIL'))
            && Configuration::updateValue('SJ4WEB_MR_WHITELIST_EMAILS', '')
            && Configuration::updateValue('SJ4WEB_MR_WHITELIST_DOMAINS', '')
            && Configuration::updateValue('SJ4WEB_MR_WHITELIST_TEMPLATES', '');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SJ4WEB_MAIL_REDIRECT_ENABLED');
        Configuration::deleteByName('SJ4WEB_MAIL_REDIRECT_TO');
        Configuration::deleteByName('SJ4WEB_MR_WHITELIST_EMAILS');
        Configuration::deleteByName('SJ4WEB_MR_WHITELIST_DOMAINS');
        Configuration::deleteByName('SJ4WEB_MR_WHITELIST_TEMPLATES');
        return parent::uninstall();
    }

    /**
     * Intercepte tous les envois et remplace les destinataires par l’adresse cible.
     * $params est passé par référence par PrestaShop → on peut le modifier.
     */
    public function hookActionEmailSendBefore(array &$params)
    {
        if (!Configuration::get('SJ4WEB_MAIL_REDIRECT_ENABLED')) {
            return;
        }

        $target = trim((string) Configuration::get('SJ4WEB_MAIL_REDIRECT_TO'));
        if ($target === '') return;

        $originalTo = $this->extractEmailsFromTo($params['to'] ?? []);

        // EXCLUSIONS paramétrées (emails, domaines, templates)
        $tpl = (string)($params['template'] ?? '');
        if ($this->shouldBypassRedirection($originalTo, $tpl)) {
            return; // on laisse passer tel quel
        }

        // ⇒ Redirection
        $params['to'] = [$target];
        $params['toName'] = [$target];
        $params['bcc'] = [];
        $params['replyTo'] = [];

        if (!empty($params['subject'])) {
            $prefix = $this->l('[REDIRECTED]', 'sj4web_mailredirect');
            $params['subject'] = $prefix.' '.$params['subject'];
        }

        if (!empty($params['templateVars']) && is_array($params['templateVars'])) {
            $params['templateVars']['{redirect_info}'] =
                $this->l('Email redirected by sj4web_mailredirect (originally for the customer).', 'sj4web_mailredirect');
        }
    }


    /** BO minimal (deux champs) */
    public function getContent()
    {
        if (Tools::isSubmit('submitSj4webMailRedirect')) {
            Configuration::updateValue('SJ4WEB_MAIL_REDIRECT_ENABLED', (int)Tools::getValue('SJ4WEB_MAIL_REDIRECT_ENABLED'));
            Configuration::updateValue('SJ4WEB_MAIL_REDIRECT_TO', trim((string)Tools::getValue('SJ4WEB_MAIL_REDIRECT_TO')));

            // on stocke brut, le parsing/normalisation est fait à l’usage
            Configuration::updateValue('SJ4WEB_MR_WHITELIST_EMAILS', (string)Tools::getValue('SJ4WEB_MR_WHITELIST_EMAILS'));
            Configuration::updateValue('SJ4WEB_MR_WHITELIST_DOMAINS', (string)Tools::getValue('SJ4WEB_MR_WHITELIST_DOMAINS'));
            Configuration::updateValue('SJ4WEB_MR_WHITELIST_TEMPLATES', (string)Tools::getValue('SJ4WEB_MR_WHITELIST_TEMPLATES'));

            $this->confirmations[] = $this->trans('Settings saved.', [], 'Admin.Notifications.Success');
        }

        $helper = new HelperForm();
        $helper->table = $this->name;
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSj4webMailRedirect';
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value = [
            'SJ4WEB_MAIL_REDIRECT_ENABLED' => Configuration::get('SJ4WEB_MAIL_REDIRECT_ENABLED'),
            'SJ4WEB_MAIL_REDIRECT_TO' => Configuration::get('SJ4WEB_MAIL_REDIRECT_TO'),
            'SJ4WEB_MR_WHITELIST_EMAILS' => Configuration::get('SJ4WEB_MR_WHITELIST_EMAILS'),
            'SJ4WEB_MR_WHITELIST_DOMAINS' => Configuration::get('SJ4WEB_MR_WHITELIST_DOMAINS'),
            'SJ4WEB_MR_WHITELIST_TEMPLATES' => Configuration::get('SJ4WEB_MR_WHITELIST_TEMPLATES'),
        ];

        $fields_form = [[
            'form' => [
                'legend' => ['title' => 'Mail redirect'],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => 'Enable redirection',
                        'name' => 'SJ4WEB_MAIL_REDIRECT_ENABLED',
                        'values' => [
                            ['id' => 'on',  'value' => 1, 'label' => 'Yes'],
                            ['id' => 'off', 'value' => 0, 'label' => 'No'],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Target email',
                        'name' => 'SJ4WEB_MAIL_REDIRECT_TO',
                        'desc' => 'All emails will be sent to this address.',
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => 'Whitelist • Emails (one per line)',
                        'name' => 'SJ4WEB_MR_WHITELIST_EMAILS',
                        'desc' => "Example:\nadmin@shop.tld\nsupport@shop.tld",
                        'rows' => 4,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => 'Whitelist • Domains (one per line)',
                        'name' => 'SJ4WEB_MR_WHITELIST_DOMAINS',
                        'desc' => "Example:\n@sj4web.fr\ncompagniedelagrandeourse.com",
                        'rows' => 3,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => 'Whitelist • Templates (one per line)',
                        'name' => 'SJ4WEB_MR_WHITELIST_TEMPLATES',
                        'desc' => "Example:\ncontact_form\ntest_email",
                        'rows' => 3,
                    ],
                ],
                'submit' => ['title' => 'Save'],
            ],
        ]];

        return $helper->generateForm($fields_form);
    }

    /**
     * Normalise la liste "to" (string|array) en array d’emails.
     */
    private function extractEmailsFromTo($to): array
    {
        $emails = [];

        if (is_string($to)) {
            $emails[] = $to;
        } elseif (is_array($to)) {
            // cas 1: ['john@doe.tld', 'jane@doe.tld']
            foreach ($to as $k => $v) {
                if (is_string($v)) {
                    $emails[] = $v;
                } elseif (is_array($v)) {
                    // cas 2: [['john@doe.tld' => 'John'], ['jane@doe.tld' => 'Jane']]
                    foreach ($v as $email => $name) {
                        if (is_string($email)) {
                            $emails[] = $email;
                        } elseif (is_string($name)) {
                            // cas rare: ['email' => 'john@doe.tld']
                            $emails[] = $name;
                        }
                    }
                }
            }
        }

        // nettoyage basique
        $emails = array_values(array_unique(array_filter(array_map('trim', $emails))));
        return $emails;
    }

    /**
     * Retourne true si on doit laisser passer ce mail (pas de redirection).
     */
    private function shouldBypassRedirection(array $originalTo, string $template): bool
    {
        $wlEmails   = $this->readListCfg('SJ4WEB_MR_WHITELIST_EMAILS');
        $wlDomains  = $this->readListCfg('SJ4WEB_MR_WHITELIST_DOMAINS');
        $wlTpls     = $this->readListCfg('SJ4WEB_MR_WHITELIST_TEMPLATES');

        // 1) Template whitelisted ?
        if ($template !== '' && in_array($template, $wlTpls, true)) {
            return true;
        }

        // 2) Email exact whitelisted ?
        foreach ($originalTo as $addr) {
            $addrLower = strtolower($addr);
            if (in_array($addrLower, $wlEmails, true)) {
                return true;
            }
            // 3) Domaine whitelisted ?
            $domain = strtolower($this->extractDomain($addrLower));
            foreach ($wlDomains as $dom) {
                // accepte "example.com" ou "@example.com"
                $dom = ltrim($dom, '@');
                if ($domain === $dom) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Lit une liste multi-ligne depuis Configuration et normalise (lowercase, trim, sans doublons).
     */
    private function readListCfg(string $key): array
    {
        $raw = (string) Configuration::get($key, '');
        if ($raw === '') return [];

        // split par \n, \r, ; ou ,  → une valeur par ligne c’est l’UX conseillée
        $parts = preg_split('/[\r\n;,]+/u', $raw) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static function ($v) { return $v !== ''; });

        // emails/domains en lowercase; templates tels quels mais on standardise en lowercase aussi
        $lower = array_map('strtolower', $parts);
        return array_values(array_unique($lower));
    }

    /**
     * Extrait le domaine d’un email. Si invalide, renvoie une chaîne vide.
     */
    private function extractDomain(string $email): string
    {
        $pos = strrpos($email, '@');
        if ($pos === false) return '';
        return substr($email, $pos + 1);
    }

}
