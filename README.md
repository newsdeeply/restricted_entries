# Restricted Entries

Version: 0.2.x

> Restricts authors to selected entries only.

### SPECS ###

- This extensions offers real security, as any attempt to view or edit un-allowed entries will result in a security exception.
- This exception is also written in Symphony's log.

### REQUIREMENTS ###

- Symphony CMS version 2.7.x and up (as of the day of the last release of this extension)

### INSTALLATION ###

- `git clone` / download and unpack the tarball file
- Put into the extension directory
- Enable/install just like any other extension

You can also install it using the [extension downloader](http://symphonyextensions.com/extensions/extension_downloader/).
Just search for `restricted_entries`.

For more information, see <http://getsymphony.com/learn/tasks/view/install-an-extension/>

### HOW TO USE ###

- Create a section to hold your roles.
- Create at least one role in it.
- Open `manifest/config.php`.
- Add this entry, setting real values for ids.
```php
###### RESTRICTED_ENTRIES ######
'restricted_entries' => array(
    'roles_section_id' => <Section ID>,
    'roles_field_id' => <Field ID>,
),
########
```
- Go to the settings page of the section you want to restrict the entries in.
- Select which roles can be used in this field.
- With a developer or manager account, go set one or more roles for each entries in that section.

### AKNOWLEDGMENTS ###

This field would not have been created if people did not financed it. A big thanks to all the people at [News Deeply](http://www.newsdeeply.com/) to have put the money into creating this extension.

### LICENSE ###

[MIT](http://deuxhuithuit.mit-license.org)

Made with love in Montréal by [Deux Huit Huit](https://deuxhuithuit.com)

Copyright (c) 2015
