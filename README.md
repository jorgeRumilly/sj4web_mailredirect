# SJ4WEB - Mail Redirect

## Description
This PrestaShop module intercepts **all outgoing emails** and redirects them to a single target email address.  
Useful for **staging** or **development environments** where you don't want real customers to receive transactional emails.

Tested on **PrestaShop 1.7.x** and **8.x**.

---

## Features
- Redirects **all emails** sent via `Mail::Send()` to a configured address.
- Configurable whitelist:
    - **Emails** allowed to bypass redirection.
    - **Domains** allowed to bypass redirection.
    - **Templates** allowed to bypass redirection.
- Optional subject prefix `[REDIRECTED]`.
- Optional redirect banner in email content.
- Enable/Disable directly from module configuration.
- Fully translatable (XLF, `en-US` / `fr-FR` provided).

---

## Installation
1. Copy the `sj4web_mailredirect` folder into `/modules/`.
2. Install from the **Back Office** > **Modules** > **Module Manager**.
3. Configure:
    - Enable or disable redirection.
    - Set the target email address.
    - Set the whitelist (emails, domains, templates).

---

## Excluding some emails
You can configure in the module settings:
- **Emails whitelist** → any email address listed will bypass redirection.
- **Domains whitelist** → any recipient email belonging to a listed domain will bypass redirection.
- **Templates whitelist** → any email template listed will bypass redirection.

---

## License
This module is distributed under the **GNU General Public License v3.0**.  
You should have received a copy of the license along with this module.  
If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).

---

## Author
- **SJ4WEB.FR**  
  Website: [https://sj4web.fr](https://sj4web.fr)
