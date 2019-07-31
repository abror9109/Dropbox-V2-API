# Dropbox-V2-API
Drupal module for Dropbox V2 API
This module allow Drupal users to work with the Dropbox new V2 API. Module uses Dropbox for HTTP.
Module allows to enable single dropbox account for all users.

Installation
In order to start working, you have to Create an App in Dropbox. Get App key and App secret for your Dropbox App.
1. Enable the module
2. Go to www.example.com/admin/config/services/dropbox/settings
3. Enter App key and App secret.
4. Save and authorize your app. That's it.

Usage
You can use the useful functions to interact your site with Dropbox.
Here is the list of functions you can use:
* _dropbox_space_usage - get the space usage
* _dropbox_current_account - get the current account info
* _dropbox_upload - upload file
* _dropbox_create_folder - create folder
* _dropbox_delete - delete file or folder
* _dropbox_delete_multiple - batch delete files or folders
* _dropbox_delete_status - check batch delete status
* _dropbox_list_folder - list folder
* _dropbox_list_folder_next - Once a cursor has been retrieved from list_folder, use this to paginate through all files
* _dropbox_get_last_page_cursor - A way to quickly get a cursor for the folder's state
* _dropbox_move - move files
* _dropbox_copy - copy files
