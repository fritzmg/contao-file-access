[![](https://img.shields.io/maintenance/yes/2019.svg)](https://github.com/fritzmg/contao-file-access)
[![](https://img.shields.io/packagist/v/fritzmg/contao-file-access.svg)](https://packagist.org/packages/fritzmg/contao-file-access)
[![](https://img.shields.io/packagist/dt/fritzmg/contao-file-access.svg)](https://packagist.org/packages/fritzmg/contao-file-access)

Contao File Access
=====================

Contao extension that allows file access restrictions for frontend users.

## Usage

After installing this extension, you will have the ability to allow members to access files, that are not made public. Simply edit a file or folder and enable the allowed member groups. If you select none, the file will not be accessible in general (but can still be accessed via the download content element for example).

Since version `1.1.0` the script generates a regular Contao 403 page when a file is accessed without sufficient permissions. Thus you are able to do the following:

- Create a page of the type `403 Access denied` in your site structure.
- Create a login module with no redirect setting.
- Add this login module to the `403 Access denied` page.

Now, when a user which has not logged in yet opens the link to a file, he will be presented with the login form instead. After he logged in, he will be "redirected back" to the file again (there is no redirect happening actually, the user stays on the same URL).

## Important Notes

Since this access restriction is done via PHP, the file is also sent to the client via PHP. This means that the `max_execution_time` needs to be sufficiently large, so that any file can be transferred to the client before the script is terminated. Thus you should be aware that problems can occur if a file is either very large or the client's connection to the server is very slow, or both. The script tries to disable the `max_execution_time`, though there is no guarantee that this will work. Also there can be other timeouts in the webserver.

Also currently any automatically generated images by Contao are __not__ protected. So if you use thumbnails of protected images, the URLs to these thumbnails can still be accessed by anyone. Though it is planned to also be able to protect those in a future version.
