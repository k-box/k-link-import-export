## K-Link Import/Export

A set of scripts for import/export document descriptors to/from a K-Link.


**requirements**

- [PHP](https://php.net/), `>= 7.1.3`
- [Composer](https://getcomposer.org/download/)
- A registered application with search and add permission for the specific K-Link


### Usage

Download the latest release (or clone the repository) and execute

```
composer install
```

Copy the `.env.example` file into `.export.env` and `.import.env`. 
The environment files contain the configuration to authenticate to a K-Link. The configuration
is split between export and import as it might be used to transfer data from one K-Link to another.

Insert the respective configuration options into the environment files

```conf
# The URL of the K-Link instance
KLINK_URL="https://some.klink"

# The URL of the application that will perform the requests
APP_URL="https://some.app"

# The token for the application used to perform requests
APP_TOKEN="123"
```



#### Import

```bash
php ./import.php
```

Import data descriptors into a K-Link.

Import the data from `data/publications.php` to the specified K-Link.

## License

This program is Free Software: You can use, study, share and improve it at your will. Specifically you can redistribute and/or modify it under the terms of the [GNU Affero General Public License](./LICENSE.txt) version 3 as published by the Free Software Foundation.

**Your contribution is very welcome**. Find more information in our [contribution guide](./contributing.md).
