Chapter 4 - Security
======================================

To prevent attackers from uploading and executing PHP scripts, you should add a **.htaccess**  to enforce security restrictions.
 
Example:
     
     // .htaccess
     
     ForceType application/octet-stream
     Header set Content-Disposition attachment
     <FilesMatch "(?i)\.(gif|jpe?g|png)$">
         ForceType none
         Header unset Content-Disposition
     </FilesMatch>
     Header set X-Content-Type-Options nosniff
     
*Source:*  https://github.com/blueimp/jQuery-File-Upload/wiki/Security

Note:
Since Apache version 2.3.9, .htaccess support is disabled by default and must be explicitly enabled with the AllowOverride directive.
Without the configuration in the .htaccess file, allowing uploads of all file types makes your site vulnerable to remote code execution attacks.
