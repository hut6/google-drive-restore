# Google Drive Document Restoration

This will help us restore files from a shared drive where files have been removed by non-owners. For files that have been removed by owners, untrashing (or undeleting) the files is the better/only way.

## Background

In shared Google Drive folders, files aren't normally "deleted". They are removed from the shared folder and given back to the owner, as sad file orphans. Only the owner of a file can put it back (even though anyone with edit access can remove it). So normal Google Drive access isn't enough, even if it's run as the owner of the shared folder itself. You need a service account which can impersonate all the users who might have orphaned files and then put them back where they belong.

## Setup

1. You must add the Google Admin SDK and Google Drive APIs to a client in the [Google Developers Console](https://console.developers.google.com/). Download the JSON credentials file, and add it to the root directory as `credentials.json`.
2. Then you have turn that client into a _service account_ in Google Apps under [Manage OAuth2 Clients](https://admin.google.com/AdminHome?chromeless=1#OGX:ManageOauthClients) ([example](https://www.dropbox.com/s/invd8vv47ertobd/Screenshot%202015-10-30%2008.51.07.png?dl=0)) so it can operate as multiple users. You need to give it these scopes. The first two for finding the list of files removed and the last one to restore files. 
	- https://www.googleapis.com/auth/admin.reports.audit.readonly 
	- https://www.googleapis.com/auth/admin.reports.usage.readonly 
	- https://www.googleapis.com/auth/drive


## Usage

    $ composer install
    $ php app.php query admin@domain.com --event=remove_from_folder \
       --user=person@domain.com --start=2015-10-24T00:00:00.000Z > reports/output.csv
    
Check the output is what you want...
	
	$ php app.php restore reports/output.csv # pipe 'yes' into this if you get bored
    
## Google Reports API

Most of the information you want can by found in the audit report for Google Apps in the Drive area. However, it doesn't give you 
the parent folder ID items were removed from. You can either manually compile the "activity log" that generates the right hand activity bar in the Google Drive web interface (which actually gives a lot of info). Or you can use the Google Reports API.

 - [Google Admin SDK](https://developers.google.com/admin-sdk/reports/v1/reference/) ([Activities:list](https://developers.google.com/admin-sdk/reports/v1/reference/activities/list))

# Tasks

- [x] Finish switching it to a proper Symfony Console app
- [ ] Support untrashing documents trashed by their owner

## CRANAplus

There is a `credentials.json` file in 1Password named _CRANAplus IT Service Account_, which can be used.
