# Skyline Default Setting Package
The setup package is a simple controller of one PDO table holding dynamical settings for your application.

### Installation
```bin
$ composer require skyline/default-setting
```

### Usage
Fist you need to create an SQL table containing at least the following fields:
1.  **id**  
    Identifies each entry uniquely
1.  **name**  
    the name of the setting
1.  **content**  
    The content of the setting
1.  **multiple**  
    Specifies, if the entry is multiple (contents of settings with the same name are sacked)
    
You can design the table how you want.

Now in PHP see below how you can access the settings:
```php
<?php
use Skyline\Setup\DefaultSetting;

$setup = DefaultSetting::getDefaultSetting();
echo $setup->getSetting('my-setting', /* default value */ 'not-available');

// Defining a setting, see below
$setup->setSetting('my-setting', /* value */ 13, /* temporary */ false, /* multiple */ false);
// Passing true to temporary will only update the value for the current request, while passing false writes the passed value into the database persistently.

// Removes the setting
$setup->removeSetting('my-setting', /* temporary */ false);
// Again passing false to temporary will remove the setting from the database as well.
```

See below how you can use a custom setting tool:
```php
<?php
use Skyline\Setup\AbstractSetting;

class MySetting extends AbstractSetting {
    // Adjust the sql table field names
    const RECORD_ID_KEY = 'customized_id';
    const RECORD_NAME_KEY = 'customized_name';
    const RECORD_CONTENT_KEY = 'customized_content';
    const RECORD_MULTIPLE_KEY = 'customized_multiple';
    
    protected function getTableName() : string{
        return "MY_TABLE_NAME";
    }
}

```