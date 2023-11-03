# CrsGrpEnrollment

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

**Table of Contents**

* [CrsGrpEnrollment](#crsgrpenrollment)
* [Requirements](#requirements)
* [Installation](#installation)
    * [Composer](#composer)
* [Know Issues](#known-issues)
* [License](#license)

## CrsGrpEnrollment
This plugin enables the import of course or group memberships via CSV file. User accounts are identified by either:
* user account name
* email address
* matriculation number

The user accounts MUST exist already. This plugin cannot be used to create user accounts.
The plugin will process files in a cronjob to enable administrators to better protect system resources. Users with the permission to manage course/group members (typically: course admin, group admin) can access a new tab: "Membership import" for courses and groups. There users can upload a CSV file containing the users to be added as course/group members. The plugin cannot be used to remove users from courses or groups.

The CrsGrpEnrollment plugin will create a report file (CSV) which is send to the in an e-mail to the user that started the import. This report will ONLY list user accounts that could not be added to the course/group and list the (potential) reason:
* no user account found matching a) user account name, b) email adress or c) matriculation number (please note: in case of the email address only the primary email address is checked)
* user account already assigned to course/group
* course/group no longer available (i.e. has been deleted before the import was run)

## Requirements

* PHP: [![Minimum PHP Version](https://img.shields.io/badge/Minimum_PHP-7.2.x-blue.svg)](https://php.net/) [![Maximum PHP Version](https://img.shields.io/badge/Maximum_PHP-7.4.x-blue.svg)](https://php.net/)
* ILIAS: [![Minimum ILIAS Version](https://img.shields.io/badge/Minimum_ILIAS-6.0-orange.svg)](https://ilias.de/) [![Maximum ILIAS Version](https://img.shields.io/badge/Maximum_ILIAS-7.999-orange.svg)](https://ilias.de/)
* Permissions: In order to import course or group memberships, the active user MUST have the permission to manage members for courses/groups in ILIAS.
* SOAP administration MUST be enabled.
* CSV: The import file MUST be CSV and MUST contain 1 (one) user account name OR 1 (one) email address OR 1 (one) matriculation number per row. The file MUST contain 1 (one) column only.
* In ILIAS 7 UserInterfaceHook plugins can't add cronjobs. The cronjob has to be manually started using the following bash command: 
  ```bash
  php <path to plugin>/cron.php <username> <password> <client-id>
  ```
  * This script can then be added to a normal linux cronjob for automatic execution.
  * In future versions of ILIAS (8+) this will no longer be required and the CronJob can be added to ILIAS directly and managed through it as well. 
## Installation

This plugin MUST be installed as a
[User Interface Plugin](https://www.ilias.de/docu/goto_docu_pg_39405_42.html).

The files MUST be saved in the following directory:

	<ILIAS>/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpEnrollment

Correct file and folder permissions MUST be
ensured by the responsible system administrator.

### Composer

After the plugin files have been installed as described above,
please install the [`composer`](https://getcomposer.org/) dependencies:

```bash
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpEnrollment
composer install --no-dev
```

## License

See LICENSE file in this repository.