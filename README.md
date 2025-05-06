# CrsGrpEnrollment

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

## Requirements

| Component | Version(s)                                                                                    | Link                      |
|-----------|-----------------------------------------------------------------------------------------------|---------------------------|
| PHP       | ![](https://img.shields.io/badge/8.1-blue.svg) ![](https://img.shields.io/badge/8.2-blue.svg) | [PHP](https://php.net)    |
| ILIAS     | ![](https://img.shields.io/badge/9-orange.svg)                                                | [ILIAS](https://ilias.de) |

* Permissions: In order to import course or group memberships, the active user MUST have the permission to manage members for courses/groups in ILIAS.
* SOAP administration MUST be enabled.
* CSV: The import file MUST be CSV and MUST contain 1 (one) user account name OR 1 (one) email address OR 1 (one) matriculation number per row. The file MUST contain 1 (one) column only.

---

## Table of contents

<!-- TOC -->
* [CrsGrpEnrollment](#crsgrpenrollment)
  * [Requirements](#requirements)
  * [Table of contents](#table-of-contents)
  * [CrsGrpEnrollment](#crsgrpenrollment-1)
  * [Installation](#installation)
  * [License](#license)
<!-- TOC -->

---

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

## Installation

1. Clone this repository to **Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpEnrollment**
2. Install the Composer dependencies
   ```bash
   cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CrsGrpEnrollment
   composer install --no-dev
   ```
   Developers **MUST** omit the `--no-dev` argument.
3. Run ``composer install --no-dev`` in the ilias root directory!
4. Login to ILIAS with an administrator account (e.g. root)
5. Select **Plugins** in **Extending ILIAS** inside the **Administration** main menu.
6. Search for the **CrsGrpEnrollment** plugin in the list of plugin and choose **Install** from the **Actions** drop-down.
7. Choose **Activate** from the **Actions** dropdown.

## License

See LICENSE file in this repository.
